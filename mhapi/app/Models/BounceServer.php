<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class BounceServer extends Authenticatable
{

    public $timestamps = false;

    protected $table = 'mw_bounce_server';

    protected $primaryKey = "server_id";

    const STATUS_CRON_RUNNING = 'cron-running';

    const STATUS_HIDDEN = 'hidden';

    const STATUS_DISABLED = 'disabled';

    const STATUS_ACTIVE = 'active';

    const TEXT_NO = 'no';

    const TEXT_YES = 'yes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable
        = [

        ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden
        = [

        ];

    public function getConnectionString()
       {
           $searchReplace = array(
               '[HOSTNAME]'        => $this->hostname,
               '[PORT]'            => $this->port,
               '[SERVICE]'         => $this->service,
               '[PROTOCOL]'        => $this->protocol,
               '[MAILBOX]'         => $this->mailBox,
               '[/VALIDATE_CERT]'  => '',
           );

           if (($this->protocol == 'ssl' || $this->protocol == 'tls') && $this->validate_ssl == self::TEXT_NO) {
               $searchReplace['[/VALIDATE_CERT]'] = '/novalidate-cert';
           }

           $connectionString = '{[HOSTNAME]:[PORT]/[SERVICE]/[PROTOCOL][/VALIDATE_CERT]}[MAILBOX]';
           $connectionString = str_replace(array_keys($searchReplace), array_values($searchReplace), $connectionString);

           return $connectionString;
       }

    public function getDeleteAllMessages()
       {
           return (bool)(!empty($this->delete_all_messages) && $this->delete_all_messages == self::TEXT_YES);
       }

    public function getSearchCharset()
       {
           return !empty($this->search_charset) ? strtoupper($this->search_charset) : null;
       }

    public function getImapOpenParams()
       {
           $params = array();
           if (!empty($this->disable_authenticator)) {
               $params['DISABLE_AUTHENTICATOR'] = $this->disable_authenticator;
           }
           return $params;
       }
}
