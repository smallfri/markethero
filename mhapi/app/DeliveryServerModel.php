<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class DeliveryServerModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_delivery_server';
    protected $primaryKey = "server_id";

    const TRANSPORT_SMTP = 'smtp';

       const TRANSPORT_SMTP_AMAZON = 'smtp-amazon';

       const TRANSPORT_SENDMAIL = 'sendmail';

       const TRANSPORT_PHP_MAIL = 'php-mail';

       const TRANSPORT_PICKUP_DIRECTORY = 'pickup-directory';

       const TRANSPORT_TCP_STREAM = 'tcp-stream';

       const TRANSPORT_MANDRILL_WEB_API = 'mandrill-web-api';

       const TRANSPORT_AMAZON_SES_WEB_API = 'amazon-ses-web-api';

       const TRANSPORT_MAILGUN_WEB_API = 'mailgun-web-api';

       const TRANSPORT_SENDGRID_WEB_API = 'sendgrid-web-api';

       const TRANSPORT_LEADERSEND_WEB_API = 'leadersend-web-api';

       const TRANSPORT_ELASTICEMAIL_WEB_API = 'elasticemail-web-api';

       const TRANSPORT_DYN_WEB_API = 'dyn-web-api';

       const TRANSPORT_SPARKPOST_WEB_API = 'sparkpost-web-api';

       const DELIVERY_FOR_SYSTEM = 'system';

       const DELIVERY_FOR_CAMPAIGN_TEST = 'campaign-test';

       const DELIVERY_FOR_TEMPLATE_TEST = 'template-test';

       const DELIVERY_FOR_CAMPAIGN = 'campaign';

       const DELIVERY_FOR_GROUP = 'groups';

       const DELIVERY_FOR_LIST = 'list';

       const DELIVERY_FOR_TRANSACTIONAL = 'transactional';

       const USE_FOR_ALL = 'all';

       const USE_FOR_GROUPS = 'groups';

       const USE_FOR_TRANSACTIONAL = 'transactional';

       const USE_FOR_CAMPAIGNS = 'campaigns';

       const STATUS_IN_USE = 'in-use';

       const STATUS_HIDDEN = 'hidden';

       const STATUS_DISABLED = 'disabled';

       const TEXT_NO = 'no';

       const TEXT_YES = 'yes';

       const DEFAULT_QUEUE_NAME = 'emails-queue';

       const FORCE_FROM_WHEN_NO_SIGNING_DOMAIN = 'when no valid signing domain';

       const FORCE_FROM_ALWAYS = 'always';

       const FORCE_FROM_NEVER = 'never';

       const FORCE_REPLY_TO_ALWAYS = 'always';

       const FORCE_REPLY_TO_NEVER = 'never';

       protected $serverType = 'smtp';

       // flag to mark what kind of delivery we are making
       protected $_deliveryFor = 'system';

       // what do we deliver
       protected $_deliveryObject;

       // mailer object
       protected $_mailer;

       // list of additional headers to send for this server
       public $additional_headers = array();

       // since 1.3.4.9
       protected $_hourlySendingsLeft;

       // since 1.3.5 - flag to determine if logging usage
       protected $_logUsage = true;

       // since 1.3.5, store campaign emails in queue and flush at __destruct
       protected $_campaignQueueEmails = array();

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id', 'group_email_uid'

    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];

}
