<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */

namespace App\Console\Commands;


use App\DeliveryServerModel;
use App\GroupControlsModel;
use App\GroupEmailGroupsModel;
use App\GroupEmailLogModel;
use App\GroupEmailModel;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Console\Command;
use Mail;




class SendGroupsController extends Controller
{

    protected $signature = 'send-groups';

    protected $description = 'Sends Group Emails';


    // current campaign
      protected $_group;

      // flag
      protected $_restoreStates = true;

      // flag
      protected $_improperShutDown = false;

      // global command arguments

      // what type of campaigns this command is sending
      public $campaigns_type;

      // how many campaigns to process at once
      public $groups_limit = 0;

      // from where to start
      public $groups_offset = 0;

      // whether this should be verbose and output to console
      public $verbose = 1;

      // since 1.3.5.9 - whether we should send in parallel using pcntl, if available
      // this is a temporary flag that should be removed in future versions
      public $use_pcntl = false;

      // since 1.3.5.9 - if parallel sending, how many campaigns at same time
      // this is a temporary flag that should be removed in future versions
      public $campaigns_in_parallel = 1;

      // since 1.3.5.9 -  if parallel sending, how many subscriber batches at same time
      // this is a temporary flag that should be removed in future versions
      public $subscriber_batches_in_parallel = 3;

      public $options;

      public function init()
      {

          // this will catch exit signals and restore states
          if ($this->functionExists('pcntl_signal'))
          {
              declare(ticks = 1);
              pcntl_signal(SIGINT, array($this, '_handleExternalSignal'));
              pcntl_signal(SIGTERM, array($this, '_handleExternalSignal'));
              pcntl_signal(SIGHUP, array($this, '_handleExternalSignal'));
          }

          register_shutdown_function(array($this, '_restoreStates'));
//          Yii::app()->attachEventHandler('onError', array($this, '_restoreStates'));
//          Yii::app()->attachEventHandler('onException', array($this, '_restoreStates'));

          // if more than 1 hour then something is def. wrong?
          ini_set('max_execution_time', 3600);
          set_time_limit(3600);
      }

      public function _handleExternalSignal($signalNumber)
      {

          // this will trigger all the handlers attached via register_shutdown_function
          $this->_improperShutDown = true;
          exit;
      }

      public function _restoreStates($event = null)
      {

          if (!$this->_restoreStates)
          {
              return;
          }
          $this->_restoreStates = false;

          // called as a callback from register_shutdown_function
          // must pass only if improper shutdown in this case
          if ($event===null&&!$this->_improperShutDown)
          {
              return;
          }

//          if (!empty($this->_group)&&$this->_group instanceof CampaignController)
//          {
//              if ($this->_group->isProcessing)
//              {
//                  $this->_group->saveStatus(CampaignController::STATUS_SENDING);
//              }
//          }
      }

      public function handle()
      {

  //        // added in 1.3.4.7
  //        Yii::app()->hooks->doAction('console_command_send_groups_before_process', $this);

          $result = $this->process();

  //        // added in 1.3.4.7
  //        Yii::app()->hooks->doAction('console_command_send_groups_after_process', $this);

          return $result;
      }

      protected function process()
      {

          $options = $this->getOptions();

          $statuses = array(GroupEmailGroupsModel::STATUS_SENDING, GroupEmailGroupsModel::STATUS_PENDING_SENDING);
          $types = array(GroupEmailGroupsModel::TYPE_REGULAR, GroupEmailGroupsModel::TYPE_AUTORESPONDER);
          $limit = (int)$options['groups_at_once'];

          if ($this->campaigns_type!==null&&!in_array($this->campaigns_type, $types))
          {
              $this->campaigns_type = null;
          }

          if ((int)$this->groups_limit>0)
          {
              $limit = (int)$this->groups_limit;
          }

          $groups = GroupEmailGroupsModel::where('status', 'pending-sending')->take($limit)->skip($this->groups_offset)->get();

          $this->stdout(sprintf("Loading %d groups, starting with offset %d...", $limit, (int)$this->groups_offset));

          if (empty($groups))
          {
              $this->stdout("No Groups found, stopping.");
              return 0;
          }

          $this->stdout(sprintf("Found %d groups and now starting processing them...", count($groups)));
          if ($this->getCanUsePcntl())
          {
              $this->stdout(sprintf(
                  'Since PCNTL is active, we will send %d groups in parallel and for each group, %d batches of group emails in parallel.',
                  5,
                  4
              ));
          }

          $groupIds = array();
          foreach ($groups as $group)
          {
              $groupIds[] = $group['group_email_id'];
          }

          $this->sendCampaignStep0($groupIds);
          return 0;
      }

      protected function sendCampaignStep0(array $groupIds = array())
      {
          $handled = false;

          if ($this->getCanUsePcntl()&&$this->getCampaignsInParallel()>1)
          {
              $handled = true;

              // make sure we close the database connection
              Yii::app()->getDb()->setActive(false);

              $campaignChunks = array_chunk($groupIds, $this->getCampaignsInParallel());
              foreach ($campaignChunks as $index => $cids)
              {
                  $childs = array();
                  foreach ($cids as $cid)
                  {
                      $pid = pcntl_fork();
                      if ($pid==-1)
                      {
                          continue;
                      }

                      // Parent
                      if ($pid)
                      {
                          $childs[] = $pid;
                      }

                      // Child
                      if (!$pid)
                      {
                          $this->sendCampaignStep1($cid, $index+1);
                          exit;
                      }
                  }

                  while (count($childs)>0)
                  {
                      foreach ($childs as $key => $pid)
                      {
                          $res = pcntl_waitpid($pid, $status, WNOHANG);
                          if ($res==-1||$res>0)
                          {
                              unset($childs[$key]);
                          }
                      }
                      sleep(1);
                  }
              }
          }

          if (!$handled)
          {
              foreach ($groupIds as $groupId)
              {
//                  $this->sendCampaignStep1($groupId, 0);
                  $this->stdout('Sending Group '. $groupId.'...');

                  $this->direct_send($groupId);

              }

          }
      }

    public function direct_send($groupId)
    {
        $emails = GroupEmailModel::where('group_email_id','=',$groupId)->take(10)->get();

//        dd($emails[0]);
$index = 1;
        foreach ($emails AS $mail)
        {
            $this->stdout('Sending '. $index.' of '.count($emails).'...');
            $this->stdout('Sending email to '. $mail->to_email.'...');


//            $subject = $plugin->subject;
//
//                    $view = 'emails.ipn.send';
//
//                    $data = [
//                        'plugin_name' => $plugin->plugin_name,
//                        'license_key' => $key,
//                        'support_address' => $plugin->support_address,
//                        'receipt' => $key,
//                        'ccustname' => $name,
//                        'email_message' => $plugin->email_message,
//                        'wishlist_login_url' => $plugin->wishlist_login_url
//                    ];
//
//                    $this->mail->queue($view, $data, function ($message) use ($to_email, $plugin, $subject){
//
//                        $message->from($plugin->from_email, $plugin->from_name);
//
//                        $message->to($to_email)->subject($subject);
//                    });

            $data = ['body'=>$mail->body, 'subject'=>$mail->subject];


            Mail::queue('emails.main',$data, function ($message) use ($mail){

                $message->from($mail->from_email, $mail->from_name);
                $message->to($mail->to_email, $mail->name)->subject($mail->subject);

            });

            GroupEmailLogModel::insert(['email_id'=>$mail->email_id, 'message'=>'OK', 'date_added'=>new \DateTime()]);

            $index++;
        }
    }

      protected function sendCampaignStep1($groupId, $workerNumber = 0)
      {

          $this->stdout(sprintf("Group Worker #%d looking into the Group with ID: %d", $workerNumber, $groupId));

          $statuses = array(GroupEmailGroupsModel::STATUS_SENDING, GroupEmailGroupsModel::STATUS_PENDING_SENDING);

          $group = GroupEmailGroupsModel::find($groupId);

          $this->_group = $group;

          if (empty($group)||!in_array($group->status, $statuses))
          {
              $this->stdout(sprintf("The Group with ID: %d is not ready for processing.", $groupId));
              return 1;
          }

          $options = $this->options;

          if ($this->getCustomerStatus()=='inactive')
          {
              GroupEmailGroupsModel::where('email_id', $this->primaryKey)->update('status', GroupEmailGroupsModel::STATUS_PAUSED);

              $this->stdout("This customer is inactive!");
              return 1;
          }

          $dsParams = array('customerCheckQuota' => false, 'useFor' => array(DeliveryServerModel::USE_FOR_ALL));
          $server = DeliveryServerModel::find(5);
          if (empty($server))
          {
//              Yii::log(Yii::t('campaigns',
//                  'Cannot find a valid server to send the group email, aborting until a delivery server is available!'),
//                  CLogger::LEVEL_ERROR);
              $this->stdout('Cannot find a valid server to send the group email, aborting until a delivery server is available!');
              return 1;
          }

          $this->stdout('Changing the group status into PROCESSING!');

          // put proper status
          $group = GroupEmailGroupsModel::find($this->_group->group_email_id);

          $group->status = GroupEmailGroupsModel::STATUS_PROCESSING;
          $group->save();

          // find the subscribers limit
          $limit = (int)$options['emails_at_once'];

          $mailerPlugins = array(
              'loggerPlugin' => true,
          );

          $sendAtOnce = (int)$options['emails_at_once'];
          if (!empty($sendAtOnce))
          {
              $mailerPlugins['antiFloodPlugin'] = array(
                  'sendAtOnce' => $sendAtOnce,
                  'pause' => 1,
              );
          }

          $perMinute = (int)$options['emails_per_minute'];
          if (!empty($perMinute))
          {
              $mailerPlugins['throttlePlugin'] = array(
                  'perMinute' => $perMinute,
              );
          }

  //        $changeServerAt = (int)$customer->getGroupOption('campaigns.change_server_at', (int)$options->get('system.cron.send_groups.change_server_at', 0));
  //        $maxBounceRate  = (int)$customer->getGroupOption('campaigns.max_bounce_rate', (int)$options->get('system.cron.send_groups.max_bounce_rate', -1));

          $this->sendCampaignStep2(array(
              'group' => $group,
  //            'customer'                => $customer,
  //            'list'                    => $list,
              'server' => $server,
              'mailerPlugins' => $mailerPlugins,
              'limit' => $limit,
              'offset' => 0,
  //            'changeServerAt'          => $changeServerAt,
  //            'maxBounceRate'           => $maxBounceRate,
              'options' => $options,
              'canChangeCampaignStatus' => true,
  //            'attachments'             => $attachments,
          ));
      }

      protected function sendCampaignStep2(array $params = array())
      {

          $handled = false;
          if ($this->getCanUsePcntl()&&$this->getSubscriberBatchesInParallel()>1)
          {
              $handled = true;

              // make sure we close the database connection
              Yii::app()->getDb()->setActive(false);

              $childs = array();
              for ($i = 0;$i<$this->getSubscriberBatchesInParallel();++$i)
              {

                  $pid = pcntl_fork();
                  if ($pid==-1)
                  {
                      continue;
                  }

                  // Parent
                  if ($pid)
                  {
                      $childs[] = $pid;
                  }

                  // Child
                  if (!$pid)
                  {
                      $params['workerNumber'] = $i+1;
                      $params['offset'] = ($i*$params['limit']);
                      $params['canChangeCampaignStatus']
                          = ($i==($this->getSubscriberBatchesInParallel()-1)); // last call only
                      $this->sendCampaignStep3($params);
                      exit;
                  }
              }

              if (count($childs)==0)
              {
                  $handled = false;
              }

              while (count($childs)>0)
              {
                  foreach ($childs as $key => $pid)
                  {
                      $res = pcntl_waitpid($pid, $status, WNOHANG);
                      if ($res==-1||$res>0)
                      {
                          unset($childs[$key]);
                      }
                  }
                  sleep(1);
              }
          }

          if (!$handled)
          {
              $this->sendCampaignStep3($params);
          }

          return 0;
      }

      protected function sendCampaignStep3(array $params = array())
      {

          extract($params, EXTR_SKIP);

          $this->stdout(sprintf("Looking for emails for group with id %s...(This is email worker #%d)",
              $group->group_email_id, $workerNumber));

          $this->stdout('limit '.$limit.' offset '.$offset);


          $criteria = new CDbCriteria();
                $criteria->with['logs'] = array(
                    'select' => false,
                    'together' => true,
                    'joinType' => 'LEFT OUTER JOIN',
                    'on' => 'logs.email_id = t.email_id',
                    'condition' => '`status` = "pending-sending" AND group_email_id = :id AND logs.email_id IS NULL',
                    'limit' => $limit,
                    'offset' => $offset,
                    'params' => array(':id' => $campaign->group_email_id),
                );





  //        $criteria = new CDbCriteria();
  //        $criteria->select
  //            = 'from_email, from_name, email_id, to_email, to_name, subject, reply_to_email,body,plain_text';
  //        $criteria->limit = $limit;
  //        $criteria->offset = $offset;
  //        $criteria->addCondition('`status` = "pending-sending" AND group_email_id = '.$campaign->group_email_id);

          // and find them
          $subscribers = GroupEmail::model()->findAll($criteria);

  //        print_r($subscribers); exit;


          $this->stdout(sprintf("This emails worker(#%d) will process %d emails for this group...", $workerNumber,
              count($subscribers)));


          $processedCounter = 0;
          $serverHasChanged = false;

          //since 1.3.4.9
          $dsParams = array(
              'customerCheckQuota' => false,
              'serverCheckQuota' => false,
              'useFor' => array(DeliveryServer::USE_FOR_groupS),
          );

          // run some cleanup on subscribers
          $notAllowedEmailChars = array('-', '_');
          $subscribersQueue = array();

          $this->stdout("Running email cleanup...");

          foreach ($subscribers as $index => $subscriber)
          {
              if (isset($subscribersQueue[$subscriber->email_id]))
              {
                  unset($subscribers[$index]);
                  continue;
              }

              $containsNotAllowedEmailChars = false;
              $part = explode('@', $subscriber->to_email);
              $part = $part[0];
              foreach ($notAllowedEmailChars as $chr)
              {
                  if (strpos($part, $chr)===0||strrpos($part, $chr)===0)
                  {
                      $subscriber->addToBlacklist('Invalid email address format!');
                      $containsNotAllowedEmailChars = true;
                      break;
                  }
              }

              if ($containsNotAllowedEmailChars)
              {
                  unset($subscribers[$index]);
                  continue;
              }

              $subscribersQueue[$subscriber->email_id] = true;
          }
          unset($subscribersQueue);

          // reset the keys
          $subscribers = array_values($subscribers);
          $subscribersCount = count($subscribers);

          $this->stdout(sprintf("Checking emails count after cleanup: %d", $subscribersCount));

          // since 1.3.5.7
          if (empty($subscribers))
          {
              if ($canChangeCampaignStatus)
              {
                  $this->markCampaignSent();
              }
              return 0;
          }

          $this->stdout('Sorting emails...');

          // sort subscribers
  //        $subscribers = $this->sortSubscribers($subscribers);

          try
          {

              $this->stdout(sprintf('Entering the foreach processing loop for all %d emails...', $subscribersCount));

              foreach ($subscribers as $index => $subscriber)
              {
                  $this->stdout("", false);
                  $this->stdout(sprintf("%s - %d/%d", $subscriber->to_email, ($index+1), $subscribersCount));
                  $this->stdout(sprintf('Checking if we can send to domain of %s...', $subscriber->to_email));
                  // if this server is not allowed to send to this email domain, then just skip it.
                  if (!$server->canSendToDomainOf($subscriber->to_email))
                  {
                      continue;
                  }

                  $this->stdout(sprintf('Checking if %s is blacklisted...', $subscriber->to_email));
                  // if blacklisted, goodbye.
                  if ($subscriber->getIsBlacklisted())
                  {
                      $this->logDelivery($subscriber,
                          Yii::t('campaigns', 'This email is blacklisted. Sending is denied!'),
                          CampaignDeliveryLog::STATUS_BLACKLISTED);
                      continue;
                  }

                  $this->stdout('Checking if the server is over quota...');
                  // in case the server is over quota
                  if ($server->getIsOverQuota())
                  {
                      $this->stdout('Server is over quota, choosing another one.');
                      $currentServerId = $server->server_id;
                      if (!($server = DeliveryServer::pickServer($currentServerId, $campaign, $dsParams)))
                      {
                          throw new Exception(Yii::t('campaigns',
                              'Cannot find a valid server to send the campaign email, aborting until a delivery server is available!'),
                              99);
                      }
                  }

                  $this->stdout('Checking if the customer is over quota...');

                  // in case current customer is over quota
  //                if ($customer->getIsOverQuota()) {
  //                    throw new Exception(Yii::t('campaigns', 'This customer reached the assigned quota!'), 98);
  //                }
  //
  //                $this->stdout('Preparing the entire email...');
  //                $emailParams = $this->prepareEmail($subscriber);
  //
  //                if (empty($emailParams) || !is_array($emailParams)) {
  //                    $this->logDelivery($subscriber, Yii::t('campaigns', 'Unable to prepare the email content!'), CampaignDeliveryLog::STATUS_ERROR);
  //                    continue;
  //                }
  //
  //                if ($changeServerAt > 0 && $processedCounter >= $changeServerAt && !$serverHasChanged) {
  //                    $currentServerId = $server->server_id;
  //                    if ($newServer = DeliveryServer::pickServer($currentServerId, $campaign, $dsParams)) {
  //                        $server = $newServer;
  //                        unset($newServer);
  //                    }
  //
  //                    $processedCounter = 0;
  //                    $serverHasChanged = true;
  //                }
  //
  //                $listUnsubscribeHeaderValue = $options->get('system.urls.frontend_absolute_url');
  //                $listUnsubscribeHeaderValue .= 'lists/'.$list->list_uid.'/unsubscribe/'.$subscriber->subscriber_uid.'/'.$campaign->campaign_uid;
  //                $listUnsubscribeHeaderValue = '<'.$listUnsubscribeHeaderValue.'>';
  //
  //                $reportAbuseUrl = $options->get('system.urls.frontend_absolute_url');
  //                $reportAbuseUrl .= 'campaigns/'.$campaign->campaign_uid.'/report-abuse/'.$list->list_uid.'/'.$subscriber->subscriber_uid;

                  $headerPrefix = 'X-Mw-';
                  $emailParams = array(
                      'from' => array($subscriber['from_email'] => $subscriber['from_name']),
                      'fromName' => $subscriber['from_name'],
                      'message_id' => $subscriber['email_id'],
                      'from_email' => $subscriber['from_email'],
                      'return_path' => 'bounces@marketherobounce1.com',
                      'Return_Path' => 'bounces@marketherobounce1.com',
                      'from_name' => $subscriber['from_name'],
                      'to' => array($subscriber['to_email'] => $subscriber['to_name']),
                      'subject' => $subscriber['subject'],
                      'replyTo' => $subscriber['reply_to_email'],
                      'body' => $subscriber['body'],
                      'plainText' => $subscriber['plain_text'],
                  );

                  $emailParams['headers'] = array(
                      $headerPrefix.'Group-Uid' => $group->group_email_uid,
                      $headerPrefix.'Customer-Id' => $group->customer_id
                  );

                  $emailParams['mailerPlugins'] = $mailerPlugins;

  //                $processedCounter++;
  //                if ($processedCounter >= $changeServerAt) {
  //                    $serverHasChanged = false;
  //                }

                  // set delivery object
                  $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_GROUP)->setDeliveryObject($campaign);

                  // default status
                  $status = CampaignDeliveryLog::STATUS_SUCCESS;

                  $this->stdout(sprintf('Using delivery server: %s (ID: %d).', $server->hostname, $server->server_id));

                  // since 1.3.5 - try via queue
                  $sent = null;
                  if ($server->getUseQueue())
                  {
                      $this->stdout('Sending the email message using the QUEUE method.');
                      $sent = array('message_id' => $server->server_id.StringHelper::random(40));
                      $response = 'OK';
                      $allParams = array_merge(array(
                          'server_id' => $server->server_id,
                          'server_type' => $server->type,
                          'group_email_id' => $campaign->group_email_id,
                          'params' => $emailParams
                      ), $sent);

                      if ($server->getCampaignQueueEmailsChunkSize()>1)
                      {
                          if (!$server->pushEmailInCampaignQueue($allParams))
                          {
                              $sent = $response = null;
                          }
                          else
                          {
                              $server->logUsage();
                          }
                      }
                      else
                      {
                          if (!Yii::app()->queue->enqueue($server->getQueueName(), 'SendEmailFromQueue', $allParams))
                          {
                              $sent = $response = null;
                          }
                          else
                          {
                              $server->logUsage();
                          }
                      }

                      unset($allParams);
                  }

                  // if not via queue or queue failed
                  if (!$sent)
                  {
                      $this->stdout('Sending the email message using the DIRECT method.');
                      $sent = $server->sendEmail($emailParams);
                      $response = $server->getMailer()->getLog();
                  }

                  $messageId = null;

                  // make sure we're still connected to database...
                  Yii::app()->getDb()->setActive(true);

                  if (!$sent)
                  {
                      $status = $this->getFailStatusFromResponse($response);
                      $this->stdout(sprintf('Sending failed with: %s', $response));
                  }

                  if ($sent&&is_array($sent)&&!empty($sent['message_id']))
                  {
                      $messageId = $sent['message_id'];
                      $this->stdout('Sending OK.');

                  }
                  else
                  {
                      $this->stdout('Missing EMAIL ID !!!!!!.');

                  }

                  $this->stdout(sprintf('Done for %s, logging delivery...', $subscriber->to_email));

                  $this->logGroupEmailDelivery($sent, $server);


  //                // since 1.3.4.6
  //                Yii::app()->hooks->doAction('console_command_send_groups_after_send_to_subscriber', $campaign,
  //                    $subscriber, $customer, $server, $sent, $response, $status);
              }

          } catch (Exception $e)
          {

              $this->stdout(sprintf('Exception thrown: %s', $e->getMessage()));

              // exception code to be returned later
              $code = (int)$e->getCode();

              // make sure sending is resumed next time.
              $campaign->status = Campaign::STATUS_SENDING;

              // pause the campaigns of customers that reached the quota
              // they will only delay processing of other campaigns otherwise.
              if ($code==98)
              {
                  $campaign->status = Campaign::STATUS_PAUSED;
              }

              // log the error so we can reference it
              Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);

              // return the exception code
              return $code;
          }

          $this->stdout("", false);
          $this->stdout(sprintf('Done processing %d emails!', count($subscribers)));

          $this->stdout('Done processing the group.');


          return 0;
      }

      // since 1.3.5.9
      protected function checkCampaignOverMaxBounceRate($campaign, $maxBounceRate)
      {

          if ((int)$maxBounceRate<0)
          {
              return;
          }

          $criteria = new CDbCriteria();
          $criteria->compare('campaign_id', $campaign->campaign_id);

          $bouncesCount = (int)CampaignBounceLog::model()->count($criteria);
          $processedCount = (int)CampaignDeliveryLog::model()->count($criteria);
          $bouncesRate = -1;

          if ($processedCount>0)
          {
              $bouncesRate = ($bouncesCount*100)/$processedCount;
          }

          if ($bouncesRate>$maxBounceRate)
          {
              $campaign->block("Campaign bounce rate is higher than allowed!");
          }
      }

      // since 1.3.5.9
      protected function getCanUsePcntl()
      {
//return false;
          if (!$this->functionExists('pcntl_fork')||!$this->functionExists('pcntl_waitpid'))
          {
              return false;
          }
          return true;
      }

      // since 1.3.5.9
      protected function getCampaignsInParallel()
      {

          $options = $this->getOptions();

          return $options[0]->groups_in_parallel;
      }

      // since 1.3.5.9
      protected function getSubscriberBatchesInParallel()
      {

          $options = $this->getOptions();

          return $options[0]->group_emails_in_parallel;
      }

      // since 1.3.5.9
      protected function stdout($message, $timer = true, $separator = "\n")
      {

          if (!$this->verbose)
          {
              return;
          }

          $out = '';
          if ($timer)
          {
              $out .= '['.date('Y-m-d H:i:s').'] - ';
          }
          $out .= $message;
          if ($separator)
          {
              $out .= $separator;
          }

          echo '<br>'.$out;
      }

      protected function logDelivery(ListSubscriber $subscriber, $message, $status, $messageId = null)
      {

          $campaign = $this->_group;

          $deliveryLog = CampaignDeliveryLog::model()->findByAttributes(array(
              'campaign_id' => (int)$campaign->campaign_id,
              'subscriber_id' => (int)$subscriber->subscriber_id,
          ));

          if (empty($deliveryLog))
          {
              $deliveryLog = new CampaignDeliveryLog();
              $deliveryLog->campaign_id = $campaign->campaign_id;
              $deliveryLog->subscriber_id = $subscriber->subscriber_id;
          }

          $deliveryLog->email_message_id = $messageId;
          $deliveryLog->message = str_replace("\n\n", "\n", $message);
          $deliveryLog->status = $status;

          return $deliveryLog->save();
      }

      protected function countSubscribers()
      {

          $criteria = new CDbCriteria();
          $criteria->with['deliveryLogs'] = array(
              'select' => false,
              'together' => true,
              'joinType' => 'LEFT OUTER JOIN',
              'on' => 'deliveryLogs.campaign_id = :cid',
              'condition' => '(deliveryLogs.subscriber_id IS NULL OR deliveryLogs.`status` = :tstatus)',
              'params' => array(
                  ':cid' => $this->_group->campaign_id,
                  ':tstatus' => CampaignDeliveryLog::STATUS_TEMPORARY_ERROR
              ),
          );

          return $this->_group->countSubscribers($criteria);
      }

      // find subscribers
      protected function findSubscribers($offset = 0, $limit = 300)
      {

          $criteria = new CDbCriteria();
          $criteria->with['deliveryLogs'] = array(
              'select' => false,
              'together' => true,
              'joinType' => 'LEFT OUTER JOIN',
              'on' => 'deliveryLogs.campaign_id = :cid',
              'condition' => '(deliveryLogs.subscriber_id IS NULL OR deliveryLogs.`status` = :tstatus)',
              'params' => array(
                  ':cid' => $this->_group->campaign_id,
                  ':tstatus' => CampaignDeliveryLog::STATUS_TEMPORARY_ERROR
              ),
          );

          // and find them
          return $this->_group->findSubscribers($offset, $limit, $criteria);
      }

      /**
       * Tries to:
       * 1. Group the subscribers by domain
       * 2. Sort them so that we don't send to same domain two times in a row.
       */
      protected function sortSubscribers($subscribers)
      {

          $subscribersCount = count($subscribers);
          $_subscribers = array();
          foreach ($subscribers as $index => $subscriber)
          {
              $emailParts = explode('@', $subscriber->to_email);
              $domainName = $emailParts[1];
              if (!isset($_subscribers[$domainName]))
              {
                  $_subscribers[$domainName] = array();
              }
              $_subscribers[$domainName][] = $subscriber;
              unset($subscribers[$index]);
          }

          $subscribers = array();
          while ($subscribersCount>0)
          {
              foreach ($_subscribers as $domainName => $subs)
              {
                  foreach ($subs as $index => $sub)
                  {
                      $subscribers[] = $sub;
                      unset($_subscribers[$domainName][$index]);
                      break;
                  }
              }
              $subscribersCount--;
          }

          return $subscribers;
      }

      protected function prepareEmail($subscriber)
      {

          $campaign = $this->_group;

          // how come ?
          if (empty($campaign->template))
          {
              return false;
          }

          $list = $campaign->list;
          $customer = $list->customer;
          $emailContent = $campaign->template->content;
          $embedImages = array();
          $emailFooter = null;
          $onlyPlainText
              = !empty($campaign->template->only_plain_text)&&$campaign->template->only_plain_text===CampaignTemplate::TEXT_YES;
          $emailAddress = $subscriber->to_email;

          // since 1.3.5.9
          $fromEmailCustom = null;
          $fromNameCustom = null;
          $replyToCustom = null;

          // really blind check to see if it contains a tag
          if (strpos($campaign->from_email, '[')!==false||strpos($campaign->from_name,
                  '[')!==false||strpos($campaign->reply_to, '[')!==false
          )
          {
              $searchReplace = CampaignHelper::getSubscriberFieldsSearchReplace('', $campaign, $subscriber);
              if (strpos($campaign->from_email, '[')!==false)
              {
                  $fromEmailCustom = str_replace(array_keys($searchReplace), array_values($searchReplace),
                      $campaign->from_email);
                  if (!FilterVarHelper::email($fromEmailCustom))
                  {
                      $fromEmailCustom = null;
                  }
              }
              if (strpos($campaign->from_name, '[')!==false)
              {
                  $fromNameCustom = str_replace(array_keys($searchReplace), array_values($searchReplace),
                      $campaign->from_name);
              }
              if (strpos($campaign->reply_to, '[')!==false)
              {
                  $replyToCustom = str_replace(array_keys($searchReplace), array_values($searchReplace),
                      $campaign->reply_to);
                  if (!FilterVarHelper::email($replyToCustom))
                  {
                      $replyToCustom = null;
                  }
              }
          }

          if (!$onlyPlainText)
          {
              if (($emailFooter = $customer->getGroupOption('campaigns.email_footer'))&&strlen(trim($emailFooter))>5)
              {
                  $emailContent = CampaignHelper::injectEmailFooter($emailContent, $emailFooter, $campaign);
              }

              if (!empty($campaign->option)&&!empty($campaign->option->embed_images)&&$campaign->option->embed_images==CampaignOption::TEXT_YES)
              {
                  list($emailContent, $embedImages) = CampaignHelper::embedContentImages($emailContent, $campaign);
              }

              if (!empty($campaign->option)&&$campaign->option->xml_feed==CampaignOption::TEXT_YES)
              {
                  $emailContent = CampaignXmlFeedParser::parseContent($emailContent, $campaign, $subscriber, true);
              }

              if (!empty($campaign->option)&&$campaign->option->json_feed==CampaignOption::TEXT_YES)
              {
                  $emailContent = CampaignJsonFeedParser::parseContent($emailContent, $campaign, $subscriber, true);
              }

              if (!empty($campaign->option)&&$campaign->option->url_tracking==CampaignOption::TEXT_YES)
              {
                  $emailContent = CampaignHelper::transformLinksForTracking($emailContent, $campaign, $subscriber, true);
              }

              // since 1.3.5.9 - optional open tracking.
              $trackOpen = $campaign->option->open_tracking==CampaignOption::TEXT_YES;
              //
              $emailData = CampaignHelper::parseContent($emailContent, $campaign, $subscriber, $trackOpen);
              list($toName, $emailSubject, $emailContent) = $emailData;
          }

          // Plain TEXT only supports basic tags transform, no xml/json feeds nor tracking.
          $emailPlainText = null;
          if (!empty($campaign->option)&&$campaign->option->plain_text_email==CampaignOption::TEXT_YES)
          {
              if ($campaign->template->auto_plain_text===CampaignTemplate::TEXT_YES /* && empty($campaign->template->plain_text)*/)
              {
                  $emailPlainText = CampaignHelper::htmlToText($emailContent);
              }

              if (empty($emailPlainText)&&!empty($campaign->template->plain_text)&&!$onlyPlainText)
              {
                  $_emailData = CampaignHelper::parseContent($campaign->template->plain_text, $campaign, $subscriber,
                      false);
                  list(, , $emailPlainText) = $_emailData;
                  if (($emailFooter = $customer->getGroupOption('campaigns.email_footer'))&&strlen(trim($emailFooter))>5)
                  {
                      $emailPlainText .= "\n\n\n";
                      $emailPlainText .= strip_tags($emailFooter);
                  }
                  $emailPlainText = preg_replace('%<br(\s{0,}?/?)?>%i', "\n", $emailPlainText);
              }
          }

          if ($onlyPlainText)
          {
              $_emailData = CampaignHelper::parseContent($campaign->template->plain_text, $campaign, $subscriber, false);
              list($toName, $emailSubject, $emailPlainText) = $_emailData;
              if (($emailFooter = $customer->getGroupOption('campaigns.email_footer'))&&strlen(trim($emailFooter))>5)
              {
                  $emailPlainText .= "\n\n\n";
                  $emailPlainText .= strip_tags($emailFooter);
              }
              $emailPlainText = preg_replace('%<br(\s{0,}?/?)?>%i', "\n", $emailPlainText);
          }

          // since 1.3.5.3
          if (!empty($campaign->option)&&$campaign->option->xml_feed==CampaignOption::TEXT_YES)
          {
              $emailSubject = CampaignXmlFeedParser::parseContent($emailSubject, $campaign, $subscriber, true,
                  $campaign->subject);
          }

          if (!empty($campaign->option)&&$campaign->option->json_feed==CampaignOption::TEXT_YES)
          {
              $emailSubject = CampaignJsonFeedParser::parseContent($emailSubject, $campaign, $subscriber, true,
                  $campaign->subject);
          }

          return array(
              'to' => array($emailAddress => $toName),
              'subject' => $emailSubject,
              'body' => $emailContent,
              'plainText' => $emailPlainText,
              'embedImages' => $embedImages,
              'onlyPlainText' => $onlyPlainText,
              // below disabled since 1.3.5.3
              //'trackingEnabled' => !empty($campaign->option) && $campaign->option->url_tracking == CampaignOption::TEXT_YES,

              // since 1.3.5.9
              'fromEmailCustom' => $fromEmailCustom,
              'fromNameCustom' => $fromNameCustom,
              'replyToCustom' => $replyToCustom,
          );
      }

      protected function markCampaignSent()
      {

          $campaign = $this->_group;

  //        if ($campaign->isAutoresponder)
  //        {
  //            $campaign->saveStatus(Campaign::STATUS_SENDING);
  //            return;
  //        }

          $campaign->saveStatus(Campaign::STATUS_SENT);

  //        if (Yii::app()->options->get('system.customer.action_logging_enabled', true))
  //        {
  //            $list = $campaign->list;
  //            $customer = $list->customer;
  //            if (!($logAction = $customer->asa('logAction')))
  //            {
  //                $customer->attachBehavior('logAction', array(
  //                    'class' => 'customer.components.behaviors.CustomerActionLogBehavior',
  //                ));
  //                $logAction = $customer->asa('logAction');
  //            }
  //            $logAction->campaignSent($campaign);
  //        }
  //
  //        // since 1.3.4.6
  //        Yii::app()->hooks->doAction('console_command_send_groups_group_sent', $campaign);

  //        $this->sendCampaignStats();

          // since 1.3.5.3
  //        $campaign->tryReschedule(true);
      }

      protected function sendCampaignStats()
      {

          $campaign = $this->_group;
          if (empty($campaign->option->email_stats))
          {
              return $this;
          }

          if (!($server = DeliveryServer::pickServer(0, $campaign)))
          {
              return $this;
          }

          if (!$campaign->asa('stats'))
          {
              $campaign->attachBehavior('stats', array(
                  'class' => 'customer.components.behaviors.CampaignStatsProcessorBehavior',
              ));
          }
          $viewData = compact('campaign');

          // prepare and send the email.
          $emailTemplate = Yii::app()->options->get('system.email_templates.common');
          $emailBody = Yii::app()->command->renderFile(Yii::getPathOfAlias('console.views.campaign-stats').'.php',
              $viewData, true);
          $emailTemplate = str_replace('[CONTENT]', $emailBody, $emailTemplate);

          $recipients = explode(',', $campaign->option->email_stats);
          $recipients = array_map('trim', $recipients);

          // because we don't have what to parse here!
          $fromName = strpos($campaign->from_name, '[')!==false?$campaign->list->from_name:$campaign->from_name;

          $emailParams = array();
          $emailParams['fromName'] = $fromName;
          $emailParams['replyTo'] = array($campaign->reply_to => $fromName);
          $emailParams['subject'] = Yii::t('campaign_reports',
              'The campaign {name} has finished sending, here are the stats', array('{name}' => $campaign->name));
          $emailParams['body'] = $emailTemplate;

          foreach ($recipients as $recipient)
          {
              if (!FilterVarHelper::email($recipient))
              {
                  continue;
              }
              $emailParams['to'] = array($recipient => $fromName);
              $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_group)
                  ->setDeliveryObject($campaign)
                  ->sendEmail($emailParams);
          }

          return $this;
      }

      protected function getFailStatusFromResponse($response)
      {

          return CampaignDeliveryLog::STATUS_TEMPORARY_ERROR;

          if (empty($response)||strlen($response)<5)
          {
              return CampaignDeliveryLog::STATUS_ERROR;
          }

          $status = CampaignDeliveryLog::STATUS_TEMPORARY_ERROR;

          if (preg_match('/code\s"(\d+)"/ix', $response, $matches))
          {
              $code = (int)$matches[1];
              if ($code>=450&&!in_array($code, array(503)))
              {
                  $status = CampaignDeliveryLog::STATUS_FATAL_ERROR;
              }
          }

          $temporaryErrors = array(
              'graylist',
              'greylist',
              'nested mail command',
              'incorrect authentication',
              'failed',
              'timed out',
              'sending suspended'
          );

          foreach ($temporaryErrors as $error)
          {
              if (stripos($response, $error)!==false)
              {
                  $status = CampaignDeliveryLog::STATUS_TEMPORARY_ERROR;
                  break;
              }
          }

          return $status;
      }

      protected function getOptions()
      {

          $options = GroupControlsModel::where('id','=',1)->get();

          return $options[0];
      }

      protected function getCustomerStatus()
      {

          $customer = GroupEmailGroupsModel::select('c.status AS status')->where('group_email_id','=',$this->_group->group_email_id)->join('mw_customer AS c','c.customer_id','=','mw_group_email_groups.customer_id')->get();
          return $customer[0]['status'];

      }

      public function logGroupEmailDelivery($sent, $server)
      {

          $log = new GroupEmailLog();
          $log->email_id = $sent['message_id'];
          $log->message = $server->getMailer()->getLog();
          $log->save(false);
      }


/**
     * CommonHelper::functionExists()
     *
     * @param string $name
     * @return bool
     */
    public static function functionExists($name)
    {
        static $_exists     = array();
        static $_disabled   = null;
        static $_shDisabled = null;

        if (isset($_exists[$name]) || array_key_exists($name, $_exists)) {
            return $_exists[$name];
        }

        if (!function_exists($name)) {
            return $_exists[$name] = false;
        }

        if ($_disabled === null) {
            $_disabled = ini_get('disable_functions');
            $_disabled = explode(',', $_disabled);
            $_disabled = array_map('trim', $_disabled);
        }

        if (is_array($_disabled) && in_array($name, $_disabled)) {
            return $_exists[$name] = false;
        }

        if ($_shDisabled === null) {
            $_shDisabled = ini_get('suhosin.executor.func.blacklist');
            $_shDisabled = explode(',', $_shDisabled);
            $_shDisabled = array_map('trim', $_shDisabled);
        }

        if (is_array($_shDisabled) && in_array($name, $_shDisabled)) {
            return $_exists[$name] = false;
        }

        return $_exists[$name] = true;
    }

}