<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 7/22/16
 * Time: 7:27 AM
 */

namespace App\Console\Commands;


use App\Models\BlacklistModel;
use App\Models\BounceServer;
use App\Models\GroupEmailBounceLogModel;
use Illuminate\Console\Command;

class GroupBounceHandlerCommand extends Command
{

    protected $signature = 'bounce-handler';

    protected $description = 'Gets Bounces';

    public $verbose = 1;

    public $_server;

    public function handle()
    {

        $this->stdout('Starting...');


        $this->process();
    }

    protected function process()
    {

        $servers = BounceServer::where('customer_id', '=', 1)->where('status', '=', 'active')->get();

        require(__DIR__.'/../../ThirdParty/BounceHandler/BounceHandler.php');

        if (empty($servers))
        {
            $this->stdout('No bounce server found');
            return;
        }

        //        print_r($servers);

        $serversIds = array();
        foreach ($servers as $server)
        {
            $serversIds[] = $server->server_id;
        }
        unset($servers, $server);

        $processLimit = 500;

        try
        {
            foreach ($serversIds as $serverId)
            {
                $this->_server = BounceServer::find((int)$serverId);

                if (empty($this->_server)||$this->_server->status!=BounceServer::STATUS_ACTIVE)
                {
                    $this->_server = null;
                    continue;
                }
                $this->_server->status = BounceServer::STATUS_CRON_RUNNING;
                $this->_server->save();

                $headerPrefix = 'X-Mw-';
                $headerPrefixUp = strtoupper($headerPrefix);

                $bounceHandler = new \BounceHandler($this->_server->getConnectionString(), $this->_server->username,
                    $this->_server->password, array(
                        'deleteMessages' => true,
                        'deleteAllMessages' => $this->_server->getDeleteAllMessages(),
                        'processLimit' => $processLimit,
                        'searchCharset' => $this->_server->getSearchCharset(),
                        'imapOpenParams' => $this->_server->getImapOpenParams(),
                        'requiredHeaders' => array(
                            $headerPrefix.'Email-Uid',
                            $headerPrefix.'Customer-Id'
                        ),
                    ));


                $results = $bounceHandler->getResults();
                if (empty($results))
                {
                    $this->_server = BounceServer::find((int)$this->_server->server_id);
                    if (empty($this->_server))
                    {
                        continue;
                    }
                    if ($this->_server->status==BounceServer::STATUS_CRON_RUNNING)
                    {
                        $this->_server->status = BounceServer::STATUS_ACTIVE;
                        $this->_server->save();
                    }
                    continue;
                }
                foreach ($results as $result)
                {
                    if (!isset(
                        $result['originalEmailHeadersArray']['X-Mw-Group-Id'],
                        $result['originalEmailHeadersArray']['X-Mw-Email-Uid'],
                        $result['originalEmailHeadersArray']['To']
                    )
                    )
                    {
                        continue;
                    }

                    $groupId = trim($result['originalEmailHeadersArray']['X-Mw-Group-Id']);
                    $customerId = trim($result['originalEmailHeadersArray']['X-Mw-Customer-Id']);
                    $emailId = trim($result['originalEmailHeadersArray']['X-Mw-Email-Uid']);
                    $email = trim($result['originalEmailHeadersArray']['To']);

                    $bounceLog = new GroupEmailBounceLogModel();
                    $bounceLog->group_id = $groupId;
                    $bounceLog->email_uid = $emailId;
                    $bounceLog->customer_id = $customerId;
                    $bounceLog->email = $email;
                    $bounceLog->message = trim($result['originalEmailHeadersArray']['Diagnostic-Code']);
                    $bounceLog->bounce_type
                        = $result['bounceType']==\BounceHandler::BOUNCE_HARD?GroupEmailBounceLogModel::BOUNCE_HARD:GroupEmailBounceLogModel::BOUNCE_SOFT;
                    $bounceLog->save();

                    if ($result['bounceType'])
                    {
                        $pattern = '/[A-Za-z0-9_-]+@[A-Za-z0-9_-]+\.([A-Za-z0-9_-][A-Za-z0-9_]+)/';

                        preg_match_all($pattern, $email, $matches);

                        $blacklist = new BlacklistModel();
                        $blacklist->email_id = $emailId;
                        $blacklist->email = $matches[0][0];
                        $blacklist->reason = trim($result['originalEmailHeadersArray']['Diagnostic-Code']);
                        $blacklist->save();

                    }

                    echo 'Saved';

                }

                $this->_server = BounceServer::find((int)$this->_server->server_id);
                if (empty($this->_server))
                {
                    continue;
                }

                if ($this->_server->status==BounceServer::STATUS_CRON_RUNNING)
                {
                    $this->_server->status = BounceServer::STATUS_ACTIVE;
                    $this->_server->save();
                }

                // sleep
                sleep(5);

                // open db connection
            }
        } catch (Exception $e)
        {
            if (!empty($this->_server))
            {
                $this->_server = BounceServer::find((int)$this->_server->server_id);
                if (!empty($this->_server)&&$this->_server->status==BounceServer::STATUS_CRON_RUNNING)
                {
                    $this->_server->status = BounceServer::STATUS_ACTIVE;
                    $this->_server->save();
                }
            }
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        $this->_server = null;

        return;
    }

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

        echo $out;
    }

    function microtime_float()
    {

        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec+(float)$sec);
    }

}