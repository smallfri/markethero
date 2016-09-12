<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 7/22/16
 * Time: 7:27 AM
 */

namespace App\Console\Commands;


use App\Logger;
use App\Models\BlacklistModel;
use App\Models\BounceServer;
use App\Models\GroupEmailBounceLogModel;
use DateTime;
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

        $servers = BounceServer::where('customer_id', '=', 11)->where('status', '=', 'active')->get();

        require(__DIR__.'/../../ThirdParty/BounceHandler/BounceHandler.php');

        if (empty($servers))
        {
            $this->stdout('No bounce server found');

            return;
        }

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
                            'X-Mw-Email-Uid',
                            'X-Mw-Customer-Id'
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


                    $this->fixArrayKey($result['originalEmailHeadersArray']);

//                    print_r($result['originalEmailHeadersArray']['X-Mw-Group-Id']);

                    $groupId = trim($result['originalEmailHeadersArray']['X-Mw-Group-Id']);
                    $customerId = trim($result['originalEmailHeadersArray']['X-Mw-Customer-Id']);
                    $emailId = trim($result['originalEmailHeadersArray']['X-Mw-Email-Uid']);
                    $email = trim($result['originalEmailHeadersArray']['To']);

                    $code = 'unknown';
                    if(isset($result['originalEmailHeadersArray']['Diagnostic-Code']))
                    {
                        $code = trim($result['originalEmailHeadersArray']['Diagnostic-Code']);
                    }

                    $bounceLog = new GroupEmailBounceLogModel();
                    $bounceLog->group_id = $groupId;
                    $bounceLog->email_uid = $emailId;
                    $bounceLog->customer_id = $customerId;
                    $bounceLog->email = $email;
                    $bounceLog->message = $code;
                    $bounceLog->bounce_type
                        = $result['bounceType']==\BounceHandler::BOUNCE_HARD?GroupEmailBounceLogModel::BOUNCE_HARD:GroupEmailBounceLogModel::BOUNCE_SOFT;
                    $bounceLog->date_added = new DateTime();
                    $bounceLog->save();

//                    echo 'Saved';

                    if ($result['bounceType'])
                    {
                        $pattern = '/[A-Za-z0-9_-]+@[A-Za-z0-9_-]+\.([A-Za-z0-9_-][A-Za-z0-9_]+)/';

                        preg_match_all($pattern, $email, $matches);

                        $blacklist = BlacklistModel::where('email', '=', $matches[0][0])->get();

                        if (empty($blacklist))
                        {
                            $blacklist = new BlacklistModel();
                            $blacklist->email_id = $emailId;
                            $blacklist->email = $matches[0][0];
                            $blacklist->reason = $code;
                            $blacklist->save();
                        }

//                        echo 'Bounce Type Saved';

                    }

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

    function fixArrayKey(&$arr)
    {

        $arr = array_combine(
            array_map(
                function ($str)
                {

                    return str_replace(" ", "_", $str);
                },
                array_keys($arr)
            ),
            array_values($arr)
        );

        foreach ($arr as $key => $val)
        {
            if (is_array($val))
            {
                fixArrayKey($arr[$key]);
            }
        }
    }

}