<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/28/16
 * Time: 11:08 AM
 */

namespace App\Http\Controllers;


use App\Models\CampaignModel;
use App\Models\CampaignOptionsModel;
use App\Models\CampaignTemplateModel;
use App\Models\Lists;
use App\Models\Segment;

class CampaignController extends ApiController
{

    public $endpoint;

    const STATUS_DRAFT = 'draft';

        const STATUS_PENDING_SENDING = 'pending-sending';

        const STATUS_SENDING = 'sending';

        const STATUS_SENT = 'sent';

        const STATUS_PROCESSING = 'processing';

        const STATUS_PAUSED = 'paused';

        const STATUS_PENDING_DELETE = 'pending-delete';

        const STATUS_BLOCKED = 'blocked';

        const TYPE_REGULAR = 'regular';

        const TYPE_AUTORESPONDER = 'autoresponder';

        const BULK_ACTION_PAUSE_UNPAUSE = 'pause-unpause';

        const BULK_ACTION_MARK_SENT = 'mark-sent';

    function __construct()
    {

        $this->endpoint = new \EmailOneApi_Endpoint_Campaigns();

        $this->middleware('auth.basic');

    }

    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        $expected_input = [
            'type',
            'autoresponder_event',
            'autoresponder_time_unit',
            'autoresponder_time_value',
            'name',
            'fromname',
            'from_email',
            'subject',
            'reply_to',
            'send_at',
            'list_uid',
            'segment_uid',
            'url_tracking',
            'json_feed',
            'xml_feed',
            'plain_text_email',
            'email_stats_address',
            'template_url',
            'inline_css'
        ];

        $missing_fields = array();

        foreach($expected_input AS $input)
        {
            if(!isset($data[$input]))
            {
                $missing_fields[$input] = 'Input field not found.';
            }

        }

        if(!empty($missing_fields))
        {
            return $this->respondWithError($missing_fields);
        }

        $Campaign = new CampaignModel();

        $uid = uniqid();

        $List = Lists::where('list_uid', '=', $data['list_uid'])->get();
        $list_id = $List[0]->list_id;

        $segment_id = null;
        if(isset($data['segment_uid']) AND $data['segment_uid']!='')
        {
            $Segment = Segment::where('segment_uid', '=', $data['segment_uid'])->get();
            $segment_id = $Segment[0]->segment_id;
        }

        $Campaign->name = $data['name']; // required
        $Campaign->campaign_uid = $uid; // required
        $Campaign->type = $data['type']; // optional: regular or autoresponder
        $Campaign->from_name = $data['fromname']; // required
        $Campaign->from_email = $data['from_email']; // required
        $Campaign->subject = $data['subject']; // required
        $Campaign->reply_to = $data['reply_to']; // required
        $Campaign->send_at = $data['send_at']; // required ; this will use the timezone which customer selected
        $Campaign->list_id = $list_id; // required
        $Campaign->segment_id = $segment_id;// optional ; only to narrow down
        $Campaign->customer_id = $List[0]->customer_id;// optional ; only to narrow down
        $Campaign->save();

        $CampaignOptions = new CampaignOptionsModel();
        $CampaignOptions->url_tracking = $data['url_tracking'];
        $CampaignOptions->json_feed = $data['json_feed'];
        $CampaignOptions->xml_feed = $data['xml_feed'];
        $CampaignOptions->plain_text_email = $data['plain_text_email'];
        $CampaignOptions->email_stats = $data['email_stats_address'];
        $CampaignOptions->campaign_id = $Campaign->campaign_id;
        if($data['type']=='autoresponder')
        {
            $CampaignOptions->autoresponder_event = $data['autoresponder_event'];
            $CampaignOptions->autoresponder_time_unit = $data['autoresponder_time_unit'];
            $CampaignOptions->autoresponder_time_value = $data['autoresponder_time_value'];
            $CampaignOptions->autoresponder_open_campaign_id = null;
        }
        $CampaignOptions->save();

        $CampaignTemplate = new CampaignTemplateModel();
        $CampaignTemplate->content = $data['template_url'];
        $CampaignTemplate->inline_css = $data['inline_css'];
        $CampaignTemplate->plain_text = null;
        $CampaignTemplate->auto_plain_text = 'yes';
        $CampaignTemplate->campaign_id = $Campaign->campaign_id;
        $CampaignTemplate->save();

        if($Campaign->campaign_id<1)
                {
                    return $this->respondWithError('There was an error, the campaign was not created.');
                }
        return $this->respond(['campaign_uid' => $uid, 'campaign_id'=>$Campaign->campaign_id]);
    }

}