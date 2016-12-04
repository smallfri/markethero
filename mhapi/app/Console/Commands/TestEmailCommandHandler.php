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
use DB;
use Exception;
use Illuminate\Console\Command;
use PDO;

class TestEmailCommandHandler extends Command
{

    protected $signature = 'test-email-handler';

    protected $description = 'Gets Test Emails';

    public $verbose = 1;

    public $_server;

    // imap server connection
    public $conn;

    // inbox storage and inbox message count
    private $inbox;

    private $msg_cnt;

    // email login credentials
    private $server = 'mail.smallfriinc.com';

    private $user = 'mhtestemails@smallfriinc.com';

    private $pass = 'yourpassword';

    private $port = 143; // adjust according to server settings

    public function handle()
    {

        $this->stdout('Starting...');

        $servers = BounceServer::find(11);

        $this->_server = $servers['hostname'];

        $this->user = $servers['username'];

        $this->pass = $servers['password'];

        $this->connect();


        $this->inbox();

        foreach ($this->inbox AS $row)
        {
            preg_match('/UniqueID:(.*)/', $row['body'], $matches);

            if (!empty($matches))
            {
                if (array_key_exists(1, $matches))
                {
                    DB::reconnect('mysql');
                    $pdo = DB::connection()->getPdo();
                    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                    DB::select(DB::raw('UPDATE mw_test_emails SET received = 1, status = "received", last_updated = now(), email_uid = "'.$matches[1].'"'));
                    DB::disconnect('mysql');

                }
            }
            imap_delete($this->conn, $row['index']);

        }

    }

    // close the server connection
    function close()
    {

        $this->inbox = array();
        $this->msg_cnt = 0;

        imap_close($this->conn);
    }

    // open the server connection
    // the imap_open function parameters will need to be changed for the particular server
    // these are laid out to connect to a Dreamhost IMAP server
    function connect()
    {

        $this->conn = imap_open('{'.$this->server.':143/notls}', $this->user, $this->pass);
    }

    // move the message to a new folder
    function move($msg_index, $folder = 'INBOX.Processed')
    {

        // move on server
        imap_mail_move($this->conn, $msg_index, $folder);
        imap_expunge($this->conn);

        // re-read the inbox
        $this->inbox();
    }

    // get a specific message (1 = first email, 2 = second email, etc.)
    function get($msg_index = null)
    {

        if (count($this->inbox)<=0)
        {
            return array();
        }
        elseif (!is_null($msg_index)&&isset($this->inbox[$msg_index]))
        {
            return $this->inbox[$msg_index];
        }

        return $this->inbox[0];
    }

    // read the inbox
    function inbox()
    {

        $this->msg_cnt = imap_num_msg($this->conn);

        $in = array();
        for ($i = 1;$i<=$this->msg_cnt;$i++)
        {
            $in[] = array(
                'index' => $i,
                'header' => imap_headerinfo($this->conn, $i),
                'body' => imap_body($this->conn, $i),
                'structure' => imap_fetchstructure($this->conn, $i)
            );
        }

        $this->inbox = $in;
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

}