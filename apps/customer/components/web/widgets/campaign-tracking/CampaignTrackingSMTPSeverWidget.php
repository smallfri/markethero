<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * CampaignTrackingSubstribersWithMostOpensWidget
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */
 
class CampaignTrackingSMTPSeverWidget extends CWidget
{
    public $campaign;

    public $showDetailLinks = false;
    
    public function run() 
    {
        $campaign = $this->campaign;
        if ($campaign->status == Campaign::STATUS_DRAFT) {
            return;
        }

        // remove for production!!!!
//        $campaign->customer_id = 1;
        $data = Yii::app()->db->createCommand()
            ->select('count(*) AS count, c.name')
            ->from('mw_campaign_track_open o')
            ->join('mw_campaign c','c.campaign_id=o.campaign_id')
            ->join('mw_campaign_to_delivery_server cds','cds.campaign_id=c.campaign_id')
            ->join('mw_delivery_server ds','ds.server_id = cds.server_id ')
            ->group('o.campaign_id')
            ->order('count DESC')
            ->where('c.customer_id ="'.$campaign->customer_id.'"')
            ->queryAll();


        $this->render('open-by-smtp-server', array('data'=>$data, 'campaign'=>$campaign));
    }
}