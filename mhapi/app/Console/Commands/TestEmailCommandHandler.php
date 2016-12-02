<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 7/22/16
 * Time: 7:27 AM
 */

namespace App\Console\Commands;

use App\Models\BounceServer;
use App\Models\GroupEmailBounceModel;
use DateTime;
use Exception;
use Illuminate\Console\Command;

class TestEmailCommandHandler extends Command
{

    protected $signature = 'test-email-handler';

    protected $description = 'Gets Test Emails';

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

        print_r($serversIds);

        try
        {

            foreach ($serversIds as $serverId)
            {
                $this->_server = BounceServer::find((int)$serverId);

                if (empty($this->_server)||$this->_server->status!=BounceServer::STATUS_ACTIVE)
                {
                    $this->_server = null;
                    $this->stdout('null');
                    continue;
                }
                $this->_server->status = BounceServer::STATUS_CRON_RUNNING;
                $this->_server->save();

                $bounceHandler = new \BounceHandler($this->_server->getConnectionString(), $this->_server->username,
                    $this->_server->password, array(
                        'deleteMessages' => true,
                        'deleteAllMessages' => $this->_server->getDeleteAllMessages(),
                        'processLimit' => $processLimit,
                        'searchCharset' => $this->_server->getSearchCharset(),
                        'imapOpenParams' => $this->_server->getImapOpenParams(),

                    ));



                $results = $bounceHandler->getResults();
                if (empty($results))
                {
                    $this->_server = BounceServer::find((int)$this->_server->server_id);
                    if (empty($this->_server))
                    {
                        $this->stdout('continue3');

                        continue;
                    }
                    if ($this->_server->status==BounceServer::STATUS_CRON_RUNNING)
                    {
                        $this->_server->status = BounceServer::STATUS_ACTIVE;
                        $this->_server->save();
                    }
                    $this->stdout('continue2');

                    continue;
                }
                foreach ($results as $result)
                {
                    if (!isset(
                        $result['originalEmailHeadersArray']['X-Mw-Test-Id'],
                        $result['originalEmailHeadersArray']['To']
                    )
                    )
                    {
                        $this->stdout('continue');
                        continue;
                    }


                    $this->fixArrayKey($result['originalEmailHeadersArray']);



                    echo 'Saved '.$result['originalEmailHeadersArray']['X-Mw-Test-Id'];

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
           $this->stdout($e);
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