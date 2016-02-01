<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/28/16
 * Time: 11:08 AM
 */

namespace App\Http\Controllers;


class CampaignController extends ApiController
{

    public $endpoint;

    function __construct()
    {

        $this->endpoint = new \EmailOneApi_Endpoint_Campaigns();

        $this->middleware('auth.basic');

    }

    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        $autoresponder = null;

        if($data['type']=='autoresponder')
        {
            $autoresponder
                = <<<END
            'autoresponder_event'            => {$data['autoresponder_event']}, // AFTER-SUBSCRIBE or AFTER-CAMPAIGN-OPEN
            'autoresponder_time_unit'        => {$data['autoresponder_time_unit']}, // minute, hour, day, week, month, year
            'autoresponder_time_value'       => {$data['autoresponder_time_value']}, // 1 hour after event
            'autoresponder_open_campaign_id' => {$data['autoresponder_open_campaign_id']}, // INT id of campaign, only if event is AFTER-CAMPAIGN-OPEN,
END;
        }


        // CREATE CAMPAIGN
        $response = $this->endpoint->create(array(
            'name' => $data['name'], // required
            'type' => $data['type'], // optional: regular or autoresponder
            'from_name' => $data['fromname'], // required
            'from_email' => $data['from_email'], // required
            'subject' => $data['subject'], // required
            'reply_to' => $data['reply_to'], // required
            'send_at' => $data['send_at'], // required, this will use the timezone which customer selected
            'list_uid' => $data['list_uid'], // required
            'segment_uid' => $data['segment_uid'],// optional, only to narrow down

            // optional block, defaults are shown
            'options' => array(
                'url_tracking' => $data['url_tracking'],
                // yes | no
                'json_feed' => $data['json_feed'],
                // yes | no
                'xml_feed' => $data['xml_feed'],
                // yes | no
                'plain_text_email' => $data['plain_text_email'],
                // yes | no
                'email_stats' => $data['email_stats_address'],
                // a valid email address where we should send the stats after campaign done
                $autoresponder

            ),

            // required block, archive or template_uid or content => required.
            'template' => array(
                //'archive'         => file_get_contents(dirname(__FILE__) . '/template-example.zip'),
                //'template_uid'    => 'TEMPLATE-UNIQUE-ID',
                'content' => file_get_contents($data['template_url']),
                'inline_css' => $data['inline_css'], // yes | no
                'plain_text' => null, // leave empty to auto generate
                'auto_plain_text' => 'yes', // yes | no
            ),
        ));

        if($response->body['status']=='error')
        {
            $msg = $response->body['error']['general'];
            return $this->respondWithError($msg);
        }
        return $this->respond(['campaign_uid' => $response->body['campaign_uid']]);
    }

}