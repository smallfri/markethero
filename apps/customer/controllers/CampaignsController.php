<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * CampaignsController
 *
 * Handles the actions for campaigns related tasks
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */

class CampaignsController extends Controller
{
    public function init()
    {
        $this->getData('pageStyles')->add(array('src' => AssetsUrl::js('datetimepicker/css/bootstrap-datetimepicker.min.css')));
        $this->getData('pageScripts')->add(array('src' => AssetsUrl::js('datetimepicker/js/bootstrap-datetimepicker.min.js')));

        $languageCode = LanguageHelper::getAppLanguageCode();
        if (Yii::app()->language != Yii::app()->sourceLanguage && is_file(AssetsPath::js($languageFile = 'datetimepicker/js/locales/bootstrap-datetimepicker.'.$languageCode.'.js'))) {
            $this->getData('pageScripts')->add(array('src' => AssetsUrl::js($languageFile)));
        }

        if (MW_COMPOSER_SUPPORT) {
            $this->getData('pageStyles')->add(array('src' => Yii::app()->apps->getBaseUrl('assets/js/jqcron/jqCron.css')));
            $this->getData('pageScripts')->add(array('src' => Yii::app()->apps->getBaseUrl('assets/js/jqcron/jqCron.js')));
            if (is_file(Yii::getPathOfAlias('root.assets.js') . '/jqcron/jqCron.'.$languageCode.'.js')) {
                $this->getData('pageScripts')->add(array('src' => Yii::app()->apps->getBaseUrl('assets/js/jqcron/jqCron.'.$languageCode.'.js')));
                $this->setData('jqCronLanguage', $languageCode);
            } else {
                $this->getData('pageScripts')->add(array('src' => Yii::app()->apps->getBaseUrl('assets/js/jqcron/jqCron.en.js')));
                $this->setData('jqCronLanguage', 'en');
            }
        }

        $this->getData('pageScripts')->add(array('src' => AssetsUrl::js('campaigns.js')));
        $this->getData('pageStyles')->add(array('src' => AssetsUrl::css('wizard.css')));

        parent::init();
    }

    /**
     * Define the filters for various controller actions
     * Merge the filters with the ones from parent implementation
     */
    public function filters()
    {
        return CMap::mergeArray(array(
            'postOnly + delete, pause_unpause, copy, spamcheck, resume_sending, remove_attachment',
        ), parent::filters());
    }

    /**
     * List available campaigns
     */
    public function actionIndex()
    {
        $request = Yii::app()->request;
        $campaign = new Campaign('search');
        $campaign->unsetAttributes();

        $campaign->attributes  = (array)$request->getQuery($campaign->modelName, array());
        $campaign->customer_id = (int)Yii::app()->customer->getId();

        $this->setData(array(
            'pageMetaTitle'     => $this->data->pageMetaTitle . ' | '. Yii::t('campaigns', 'Your campaigns'),
            'pageHeading'       => Yii::t('campaigns', 'Your campaigns'),
            'pageBreadcrumbs'   => array(
                Yii::t('campaigns', 'Campaigns') => $this->createUrl('campaigns/index'),
                Yii::t('app', 'View all')
            )
        ));

        $this->render('index', compact('campaign'));
    }

    /**
     * Show the overview for a campaign
     */
    public function actionOverview($campaign_uid)
    {
        $campaign = $this->loadCampaignModel($campaign_uid);
        $request = Yii::app()->request;

        if (!$campaign->accessOverview) {
            $this->redirect(array('campaigns/setup', 'campaign_uid' => $campaign->campaign_uid));
        }

        $campaign->attachBehavior('stats', array(
            'class' => 'customer.components.behaviors.CampaignStatsProcessorBehavior',
        ));

        if ($recurring = $campaign->isRecurring) {
            Yii::import('common.vendors.JQCron.*');
            $cron = new JQCron($recurring);
            $this->setData('recurringInfo', $cron->getText(LanguageHelper::getAppLanguageCode()));
        }

        $options        = Yii::app()->options;
        $webVersionUrl  = $options->get('system.urls.frontend_absolute_url');
        $webVersionUrl .= 'campaigns/' . $campaign->campaign_uid;

        $this->setData(array(
            'pageMetaTitle'     => $this->data->pageMetaTitle . ' | '. Yii::t('campaigns', 'Campaign overview'),
            'pageHeading'       => Yii::t('campaigns', 'Campaign overview'),
            'pageBreadcrumbs'   => array(
                Yii::t('campaigns', 'Campaigns') => $this->createUrl('campaigns/index'),
                $campaign->name => $this->createUrl('campaigns/overview', array('campaign_uid' => $campaign_uid)),
                Yii::t('campaigns', 'Overview')
            )
        ));

        // since 1.3.4.6
        $this->getData('pageStyles')->add(array('src' => AssetsUrl::js('circliful/css/jquery.circliful.css')));
        $this->getData('pageScripts')->add(array('src' => AssetsUrl::js('circliful/js/jquery.circliful.min.js')));

        // 1.3.5.9
        $this->setData('canExportStats', (Yii::app()->customer->getModel()->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes'));

        // render
        $this->render('overview', compact('campaign', 'webVersionUrl'));
    }

    /**
     * Create a new campaign
     */
    public function actionCreate()
    {
        $request    = Yii::app()->request;
        $notify     = Yii::app()->notify;
        $customer   = Yii::app()->customer->getModel();

        $campaign = new Campaign('step-name');
        $campaign->customer_id = (int)$customer->customer_id;

        if (($maxCampaigns = (int)$customer->getGroupOption('campaigns.max_campaigns', -1)) > -1) {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$customer->customer_id);
            $criteria->addNotInCondition('status', array(Campaign::STATUS_PENDING_DELETE));
            $campaignsCount = Campaign::model()->count($criteria);
            if ($campaignsCount >= $maxCampaigns) {
                $notify->addWarning(Yii::t('lists', 'You have reached the maximum number of allowed campaigns.'));
                $this->redirect(array('campaigns/index'));
            }
        }

        $campaignTempSource = new CampaignTemporarySource();
        $temporarySources   = array();
        $multiListsAllowed  = $customer->getGroupOption('campaigns.send_to_multiple_lists', 'no') == 'yes';

        if ($request->isPostRequest && ($attributes = (array)$request->getPost($campaign->modelName, array()))) {
            $campaign->attributes = $attributes;
            if ($campaign->save()) {
                if ($logAction = Yii::app()->customer->getModel()->asa('logAction')) {
                    $logAction->campaignCreated($campaign);
                }

                $option = new CampaignOption();
                $option->campaign_id = $campaign->campaign_id;
                $option->save();

                if ($multiListsAllowed && ($attributes = (array)$request->getPost($campaignTempSource->modelName, array()))) {
                    foreach ($attributes as $attrs) {
                        $tempModel = new CampaignTemporarySource();
                        $tempModel->attributes  = $attrs;
                        $tempModel->campaign_id = $campaign->campaign_id;
                        $tempModel->save();
                    }
                }

                $notify->addSuccess(Yii::t('app', 'Your form has been successfully saved!'));
            } else {
                $notify->addError(Yii::t('app', 'Your form has a few errors, please fix them and try again!'));
            }

            Yii::app()->hooks->doAction('controller_action_save_data', $collection = new CAttributeCollection(array(
                'controller'=> $this,
                'success'   => $notify->hasSuccess,
                'campaign'  => $campaign,
            )));

            if ($collection->success) {
                $this->redirect(array('campaigns/setup', 'campaign_uid' => $campaign->campaign_uid));
            }
        }

        $listsArray      = CMap::mergeArray(array('' => Yii::t('app', 'Choose')), $campaign->getListsDropDownArray());
        $segmentsArray   = CMap::mergeArray(array('' => Yii::t('app', 'Choose')), $campaign->getSegmentsDropDownArray());
        $groupsArray     = CMap::mergeArray(array('' => Yii::t('app', 'Choose')), $campaign->getGroupsDropDownArray());
        $canSegmentLists = $customer->getGroupOption('lists.can_segment_lists', 'yes') == 'yes';

        $this->setData(array(
            'pageMetaTitle'     => $this->data->pageMetaTitle . ' | '. Yii::t('campaigns', 'Create new campaign'),
            'pageHeading'       => Yii::t('campaigns', 'Create new campaign'),
            'pageBreadcrumbs'   => array(
                Yii::t('campaigns', 'Campaigns') => $this->createUrl('campaigns/index'),
                Yii::t('app', 'Create new')
            )
        ));

        $this->render('step-name', compact('campaign', 'listsArray', 'segmentsArray', 'groupsArray', 'campaignTempSource', 'temporarySources', 'multiListsAllowed', 'canSegmentLists'));
    }

    /**
     * Update existing campaign
     */
    public function actionUpdate($campaign_uid)
    {
        $request    = Yii::app()->request;
        $notify     = Yii::app()->notify;
        $campaign   = $this->loadCampaignModel($campaign_uid);
        $customer   = Yii::app()->customer->getModel();

        if (!$campaign->editable) {
            $this->redirect(array('campaigns/overview', 'campaign_uid' => $campaign->campaign_uid));
        }
        $campaign->scenario = 'step-name';

        $campaignTempSource = new CampaignTemporarySource();
        $temporarySources   = CampaignTemporarySource::model()->findAllByAttributes(array('campaign_id' => $campaign->campaign_id));
        $multiListsAllowed  = $customer->getGroupOption('campaigns.send_to_multiple_lists', 'no') == 'yes';

        if ($request->isPostRequest && ($attributes = (array)$request->getPost($campaign->modelName, array()))) {
            // since 1.3.4.2 we don't allow changing the list/segment if the campaign is paused.
            if ($campaign->getIsPaused()) {
                $this->redirect(array('campaigns/setup', 'campaign_uid' => $campaign->campaign_uid));
            }
            $campaign->attributes = $attributes;
            if ($campaign->save()) {
                if ($logAction = Yii::app()->customer->getModel()->asa('logAction')) {
                    $logAction->campaignUpdated($campaign);
                }
                CampaignTemporarySource::model()->deleteAllByAttributes(array('campaign_id' => $campaign->campaign_id));
                if ($multiListsAllowed && ($attributes = (array)$request->getPost($campaignTempSource->modelName, array()))) {
                    foreach ($attributes as $attrs) {
                        $tempModel = new CampaignTemporarySource();
                        $tempModel->attributes  = $attrs;
                        $tempModel->campaign_id = $campaign->campaign_id;
                        $tempModel->save();
                    }
                }
                $notify->addSuccess(Yii::t('app', 'Your form has been successfully saved!'));
            } else {
                $notify->addError(Yii::t('app', 'Your form has a few errors, please fix them and try again!'));
            }

            Yii::app()->hooks->doAction('controller_action_save_data', $collection = new CAttributeCollection(array(
                'controller'=> $this,
                'success'   => $notify->hasSuccess,
                'campaign'  => $campaign,
            )));

            if ($collection->success) {
                $this->redirect(array('campaigns/setup', 'campaign_uid' => $campaign->campaign_uid));
            }
        }

        $listsArray      = CMap::mergeArray(array('' => Yii::t('app', 'Choose')), $campaign->getListsDropDownArray());
        $segmentsArray   = CMap::mergeArray(array('' => Yii::t('app', 'Choose')), $campaign->getSegmentsDropDownArray());
        $groupsArray     = CMap::mergeArray(array('' => Yii::t('app', 'Choose')), $campaign->getGroupsDropDownArray());
        $canSegmentLists = $customer->getGroupOption('lists.can_segment_lists', 'yes') == 'yes';

        $this->setData(array(
            'pageMetaTitle'     => $this->data->pageMetaTitle . ' | '. Yii::t('campaigns', 'Update campaign'),
            'pageHeading'       => Yii::t('campaigns', 'Update campaign'),
            'pageBreadcrumbs'   => array(
                Yii::t('campaigns', 'Campaigns') => $this->createUrl('campaigns/index'),
                $campaign->name => $this->createUrl('campaigns/update', array('campaign_uid' => $campaign_uid)),
                Yii::t('app', 'Update')
            )
        ));

        $this->render('step-name', compact('campaign', 'listsArray', 'segmentsArray', 'groupsArray', 'campaignTempSource', 'temporarySources', 'multiListsAllowed', 'canSegmentLists'));
    }

    /**
     * Setup campaign
     */
    public function actionSetup($campaign_uid)
    {
        $request    = Yii::app()->request;
        $notify     = Yii::app()->notify;
        $options    = Yii::app()->options;
        $campaign   = $this->loadCampaignModel($campaign_uid);

        if (!$campaign->editable) {
            $this->redirect(array('campaigns/overview', 'campaign_uid' => $campaign->campaign_uid));
        }

        $campaign->scenario = 'step-setup';
        $default = $campaign->list->default;
        $sameFields = array('from_name', 'from_email', 'subject', 'reply_to');

        if (!empty($default)) {
            foreach ($sameFields as $attribute) {
                if (empty($campaign->$attribute)) {
                    $campaign->$attribute = $default->$attribute;
                }
            }
        }

        // customer reference
        $customer = $campaign->list->customer;

        // delivery servers for this campaign - start
        $canSelectDeliveryServers       = $customer->getGroupOption('servers.can_select_delivery_servers_for_campaign', 'no') == 'yes';
        $campaignDeliveryServersArray   = array();
        $campaignToDeliveryServers      = CampaignToDeliveryServer::model();
        if ($canSelectDeliveryServers) {
            $deliveryServers = $customer->getAvailableDeliveryServers();

            $campaignDeliveryServers = $campaignToDeliveryServers->findAllByAttributes(array(
                'campaign_id' => $campaign->campaign_id,
            ));

            foreach ($campaignDeliveryServers as $srv) {
                $campaignDeliveryServersArray[] = $srv->server_id;
            }
        }
        // delivery servers for this campaign - end

        // attachments - start
        $canAddAttachments = $options->get('system.campaign.attachments.enabled', 'no') == 'yes';
        $attachment        = null;
        if ($canAddAttachments) {
            $attachment = new CampaignAttachment('multi-upload');
            $attachment->campaign_id = $campaign->campaign_id;
        }
        // attachments - end

        // actions upon open - start
        $openAction = new CampaignOpenActionSubscriber();
        $openAction->campaign_id = $campaign->campaign_id;
        $openActionLists    = array();
        $openActions        = array();
        $openAllowedActions = CMap::mergeArray(array('' => Yii::t('app', 'Choose')), $openAction->getActions());
        $openActionLists    = $campaign->getListsDropDownArray();
        foreach ($openActionLists as $list_id => $name) {
            if ($list_id == $campaign->list_id) {
                unset($openActionLists[$list_id]);
                break;
            }
        }
        $canShowOpenActions = !empty($openActionLists);
        $openActionLists    = CMap::mergeArray(array('' => Yii::t('app', 'Choose')), $openActionLists);
        $openActions        = CampaignOpenActionSubscriber::model()->findAllByAttributes(array(
            'campaign_id' => $campaign->campaign_id,
        ));
        // actions upon open - end

        // populate list custom field upon open - start
        $openListFieldAction = new CampaignOpenActionListField();
        $openListFieldAction->campaign_id = $campaign->campaign_id;
        $openListFieldAction->list_id     = $campaign->list_id;
        $openListFieldActionOptions       = $openListFieldAction->getTextFieldsAsDropDownOptions();
        $canShowOpenListFieldActions      = !empty($openListFieldActionOptions);
        $openListFieldActionOptions       = CMap::mergeArray(array('' => Yii::t('app', 'Choose')), $openListFieldActionOptions);
        $openListFieldActions = CampaignOpenActionListField::model()->findAllByAttributes(array(
            'campaign_id' => $campaign->campaign_id,
        ));
        // populate list custom field upon open - end

        if ($request->isPostRequest && ($attributes = (array)$request->getPost($campaign->modelName, array()))) {
            $campaign->attributes = $attributes;
            $campaign->option->attributes = (array)$request->getPost($campaign->option->modelName, array());
            $unfiltered = Yii::app()->request->getOriginalPost($campaign->modelName, array());
            if (isset($unfiltered['subject'])) {
                $campaign->subject = CHtml::decode(strip_tags(Yii::app()->ioFilter->purify(CHtml::decode($unfiltered['subject']))));
            }
            if ($campaign->save() && $campaign->option->save()) {

                // actions upon open against subscriber
                CampaignOpenActionSubscriber::model()->deleteAllByAttributes(array(
                    'campaign_id' => $campaign->campaign_id
                ));
                if ($postAttributes = (array)$request->getPost($openAction->modelName, array())) {
                    foreach ($postAttributes as $index => $attributes) {
                        $openAct = new CampaignOpenActionSubscriber();
                        $openAct->attributes = $attributes;
                        $openAct->campaign_id = $campaign->campaign_id;
                        $openAct->save();
                    }
                }

                // action upon open against subscriber custom fields.
                CampaignOpenActionListField::model()->deleteAllByAttributes(array(
                    'campaign_id' => $campaign->campaign_id
                ));
                if ($postAttributes = (array)$request->getPost($openListFieldAction->modelName, array())) {
                    foreach ($postAttributes as $index => $attributes) {
                        $openListFieldAct = new CampaignOpenActionListField();
                        $openListFieldAct->attributes  = $attributes;
                        $openListFieldAct->campaign_id = $campaign->campaign_id;
                        $openListFieldAct->list_id     = $campaign->list_id;
                        $openListFieldAct->save();
                    }
                }

                $campaignToDeliveryServers->deleteAllByAttributes(array(
                    'campaign_id' => $campaign->campaign_id,
                ));
                if ($canSelectDeliveryServers && ($attributes = (array)$request->getPost($campaignToDeliveryServers->modelName, array()))) {
                    foreach ($attributes as $serverId) {
                        $relation = new CampaignToDeliveryServer();
                        $relation->campaign_id = $campaign->campaign_id;
                        $relation->server_id = (int)$serverId;
                        $relation->save();
                    }
                }

                if ($canAddAttachments && $attachments = CUploadedFile::getInstances($attachment, 'file')) {
                    $attachment->file = $attachments;
                    $attachment->validateAndSave();
                }

                // since 1.3.5.9
                $showSuccess = true;
                $emailParts  = explode('@', $campaign->from_email);
                $emailDomain = strtolower($emailParts[1]);
                $notAllowedFromDomains = CommonHelper::getArrayFromString(strtolower($options->get('system.campaign.misc.not_allowed_from_domains', '')));
                if (!empty($notAllowedFromDomains) && in_array($emailDomain, $notAllowedFromDomains)) {
                    $notify->addWarning(Yii::t('campaigns', 'You are not allowed to use "{domain}" domain in your "From email" field!', array(
                        '{domain}' => CHtml::tag('strong', array(), $emailDomain),
                    )));
                    $campaign->from_email = '';
                    $campaign->save(false);
                    $campaign->from_email = implode('@', $emailParts);
                    $showSuccess = false;
                    $hasError    = true;
                }
                //

                // since 1.3.4.7 - whether must validate sending domain - start
                if (empty($hasError) && !SendingDomain::model()->getRequirementsErrors() && $customer->getGroupOption('campaigns.must_verify_sending_domain', 'no') == 'yes') {
                    $sendingDomain = SendingDomain::model()->findVerifiedByEmail($campaign->from_email, $campaign->customer_id);
                    if (empty($sendingDomain)) {
                        $notify->addWarning(Yii::t('campaigns', 'You are required to verify your sending domain({domain}) in order to be able to send this campaign!', array(
                            '{domain}' => CHtml::tag('strong', array(), $emailDomain),
                        )));
                        $notify->addWarning(Yii::t('campaigns', 'Please click {link} to add and verify {domain} domain name. After verification, you can send your campaign.', array(
                            '{link}'   => CHtml::link(Yii::t('app', 'here'), array('sending_domains/create')),
                            '{domain}' => CHtml::tag('strong', array(), $emailDomain),
                        )));
                    }
                }
                // whether must validate sending domain - end

                if ($showSuccess) {
                    $notify->addSuccess(Yii::t('app', 'Your form has been successfully saved!'));
                }
            } else {
                $notify->addError(Yii::t('app', 'Your form has a few errors, please fix them and try again!'));
            }

            Yii::app()->hooks->doAction('controller_action_save_data', $collection = new CAttributeCollection(array(
                'controller'=> $this,
                'success'   => $notify->hasSuccess,
                'campaign'  => $campaign,
            )));

            if ($collection->success) {
                $this->redirect(array('campaigns/template', 'campaign_uid' => $campaign->campaign_uid));
            }
        }

        $this->setData(array(
            'pageMetaTitle'     => $this->data->pageMetaTitle . ' | '. Yii::t('campaigns', 'Setup campaign'),
            'pageHeading'       => Yii::t('campaigns', 'Campaign setup'),
            'pageBreadcrumbs'   => array(
                Yii::t('campaigns', 'Campaigns') => $this->createUrl('campaigns/index'),
                $campaign->name => $this->createUrl('campaigns/update', array('campaign_uid' => $campaign_uid)),
                Yii::t('campaigns', 'Setup')
            )
        ));

        $this->render('step-setup', compact(
            'campaign',
            'canSelectDeliveryServers',
            'campaignToDeliveryServers',
            'deliveryServers',
            'campaignDeliveryServersArray',
            'canAddAttachments',
            'attachment',
            'canShowOpenActions',
            'openAction',
            'openActions',
            'openAllowedActions',
            'openActionLists',
            'openListFieldAction',
            'openListFieldActions',
            'openListFieldActionOptions',
            'canShowOpenListFieldActions'
        ));
    }

    /**
     * Choose or create campaign template
     */
    public function actionTemplate($campaign_uid, $do = 'create')
    {
        $request = Yii::app()->request;
        $campaign = $this->loadCampaignModel($campaign_uid);

        if (!$campaign->editable) {
            $this->redirect(array('campaigns/overview', 'campaign_uid' => $campaign->campaign_uid));
        }

        if ($do === 'select') {

            if ($template_uid = $request->getQuery('template_uid')) {
                $campaignTemplate = $campaign->template;
                if (empty($campaignTemplate)) {
                    $campaignTemplate = new CampaignTemplate();
                }
                $campaignTemplate->setScenario('copy');

                if (!empty($campaignTemplate->template_id)) {
                    CampaignTemplateUrlActionSubscriber::model()->deleteAllByAttributes(array(
                        'template_id' => $campaignTemplate->template_id
                    ));
                }

                $selectedTemplate = CustomerEmailTemplate::model()->findByAttributes(array(
                    'template_uid'  => $template_uid,
                    'customer_id'   => (int)Yii::app()->customer->getId(),
                ));

                $redirect = array('campaigns/template', 'campaign_uid' => $campaign_uid, 'do' => 'create');

                if (!empty($selectedTemplate)) {

                    $campaignTemplate->campaign_id           = $campaign->campaign_id;
                    $campaignTemplate->customer_template_id  = $selectedTemplate->template_id;
                    $campaignTemplate->content               = $selectedTemplate->content;
                    $campaignTemplate->inline_css            = $selectedTemplate->inline_css;
                    $campaignTemplate->minify                = $selectedTemplate->minify;

                    if (!empty($campaign->option) && $campaign->option->plain_text_email == CampaignOption::TEXT_YES && $campaignTemplate->auto_plain_text === CampaignTemplate::TEXT_YES) {
                        $campaignTemplate->plain_text = CampaignHelper::htmlToText($selectedTemplate->content);
                    }

                    $campaignTemplate->save();

                    /**
                     * We also need to create a copy of the template files.
                     * This avoids the scenario where a campaign based on a uploaded template is sent
                     * then after a while the template is deleted.
                     * In this scenario, the campaign will remain without images.
                     */

                    $storagePath = Yii::getPathOfAlias('root.frontend.assets.gallery');

                    // make sure the new template has images, otherwise don't bother.
                    $filesPath = $storagePath.'/'.$selectedTemplate->template_uid;
                    if (!file_exists($filesPath) || !is_dir($filesPath)) {
                        $this->redirect($redirect);
                    }

                    // check if there's already a copy if this campaign template. if so, remove it, we don't want a folder with 1000 images.
                    $campaignFiles = $storagePath.'/cmp'.$campaign->campaign_uid;
                    if (file_exists($campaignFiles) && is_dir($campaignFiles)) {
                        FileSystemHelper::deleteDirectoryContents($campaignFiles, true, 1);
                    }

                    // copy the template folder to the campaign folder.
                    if (!FileSystemHelper::copyOnlyDirectoryContents($filesPath, $campaignFiles)) {
                        $this->redirect($redirect);
                    }

                    $search = array (
                        'frontend/assets/gallery/cmp'.$campaign->campaign_uid,
                        'frontend/assets/gallery/'.$selectedTemplate->template_uid
                    );
                    $replace = 'frontend/assets/gallery/cmp'.$campaign->campaign_uid;
                    $campaignTemplate->content = str_ireplace($search, $replace, $campaignTemplate->content);

                    if (!empty($campaign->option) && $campaign->option->plain_text_email == CampaignOption::TEXT_YES && $campaignTemplate->auto_plain_text === CampaignTemplate::TEXT_YES) {
                        $campaignTemplate->plain_text = CampaignHelper::htmlToText($campaignTemplate->content);
                    }

                    $campaignTemplate->save(false);
                }
                $this->redirect($redirect);
            }

            $criteria = new CDbCriteria();
            $criteria->compare('t.customer_id', (int)Yii::app()->customer->getId());
            $criteria->order = 't.template_id DESC';
            $count = CustomerEmailTemplate::model()->count($criteria);

            $pages = new CPagination($count);
            $pages->pageSize = 30;
            $pages->applyLimit($criteria);

            $this->data->templates = CustomerEmailTemplate::model()->findAll($criteria);
            $this->data->pages = $pages;

            $viewFile = 'step-template-select';

        } elseif ($do === 'create' || $do === 'from-url') {

            $template = $campaign->template;
            if (empty($template)) {
                $template = new CampaignTemplate();
            }
            $template->fieldDecorator->onHtmlOptionsSetup = array($this, '_setEditorOptions');
            $template->campaign_id = $campaign->campaign_id;
            $this->data->template = $template;

            if ($request->getQuery('prev') == 'upload' && !empty($template->template_id)) {
                CampaignTemplateUrlActionSubscriber::model()->deleteAllByAttributes(array(
                    'template_id' => $template->template_id
                ));
                CampaignTemplateUrlActionListField::model()->deleteAllByAttributes(array(
                    'template_id' => $template->template_id
                ));
                $this->redirect(array('campaigns/template', 'campaign_uid' => $campaign_uid, 'do' => 'create'));
            }

            if ($request->isPostRequest && ($attributes = (array)$request->getPost($template->modelName, array()))) {
                $template->attributes = $attributes;

                if (isset(Yii::app()->params['POST'][$template->modelName]['content'])) {
                    $template->content = Yii::app()->params['POST'][$template->modelName]['content'];
                } else {
                    $template->content = null;
                }

                if ($campaign->option->plain_text_email != CampaignOption::TEXT_YES) {
                    $template->only_plain_text = CampaignTemplate::TEXT_NO;
                    $template->auto_plain_text = CampaignTemplate::TEXT_NO;
                    $template->plain_text      = null;
                }

                $template->campaign_id = $campaign->campaign_id;
                $parser = new EmailTemplateParser();
                $parser->inlineCss = $template->inline_css === CampaignTemplate::TEXT_YES;
                $parser->minify    = $template->minify === CampaignTemplate::TEXT_YES;

                // since 1.3.4.2, allow content fetched from url
                // TO DO: Add an option in backend to enable/disable this feature!
                $errors = array();
                if ($do === 'from-url' && isset($attributes['from_url'])) {
                    if (!FilterVarHelper::url($attributes['from_url'])) {
                        $errors[] = Yii::t('campaigns', 'The provided url does not seem to be valid!');
                    } else {
                        $response = AppInitHelper::simpleCurlGet($attributes['from_url']);
                        if ($response['status'] == 'error') {
                            $errors[] = $response['message'];
                        } else {
                            // do a blind search after some common html elements
                            $elements = array('<div', '<table', '<a', '<p', '<br', 'style=');
                            $found = false;
                            foreach ($elements as $elem) {
                                if (stripos($response['message'], $elem) !== false) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $errors[] = Yii::t('campaigns', 'The provided url does not seem to contain valid html!');
                            } else {
                                $template->content = $response['message'];
                            }
                        }
                    }
                }

                if (!$template->isOnlyPlainText) {
                    $template->content = $parser->setContent($template->content)->getContent();
                } else {
                    $template->content    = Yii::app()->ioFilter->purify($template->plain_text);
                    $template->plain_text = $template->content;
                }

                $isNext = $request->getPost('is_next', 0);

                if (!empty($template->content) && !empty($campaign->option) && $campaign->option->plain_text_email == CampaignOption::TEXT_YES && $template->auto_plain_text === CampaignTemplate::TEXT_YES && empty($template->plain_text)) {
                    $template->plain_text = CampaignHelper::htmlToText($template->content);
                }

                if (empty($errors) && $template->save()) {
                    Yii::app()->notify->addSuccess(Yii::t('app', 'Your form has been successfully saved!'));
                    if ($isNext) {
                        $redirect = array('campaigns/confirm', 'campaign_uid' => $campaign_uid);
                    }
                    // since 1.3.4.3
                    CampaignTemplateUrlActionSubscriber::model()->deleteAllByAttributes(array(
                        'template_id' => $template->template_id
                    ));
                    if ($postAttributes = (array)$request->getPost('CampaignTemplateUrlActionSubscriber', array())) {
                        foreach ($postAttributes as $index => $attributes) {
                            $templateUrlActionSubscriber = new CampaignTemplateUrlActionSubscriber();
                            $templateUrlActionSubscriber->attributes = $attributes;
                            $templateUrlActionSubscriber->template_id = $template->template_id;
                            $templateUrlActionSubscriber->campaign_id = $campaign->campaign_id;
                            $templateUrlActionSubscriber->save();
                        }
                    }
                    // since 1.3.4.5
                    CampaignTemplateUrlActionListField::model()->deleteAllByAttributes(array(
                        'template_id' => $template->template_id
                    ));
                    if ($postAttributes = (array)$request->getPost('CampaignTemplateUrlActionListField', array())) {
                        foreach ($postAttributes as $index => $attributes) {
                            $templateUrlActionListField = new CampaignTemplateUrlActionListField();
                            $templateUrlActionListField->attributes = $attributes;
                            $templateUrlActionListField->template_id = $template->template_id;
                            $templateUrlActionListField->campaign_id = $campaign->campaign_id;
                            $templateUrlActionListField->list_id     = $campaign->list_id;
                            $templateUrlActionListField->save();
                        }
                    }
                } else {
                    Yii::app()->notify->addError(Yii::t('app', 'Your form has a few errors, please fix them and try again!'));
                    if (!empty($errors)) {
                        Yii::app()->notify->addError($errors);
                    }
                }

                Yii::app()->hooks->doAction('controller_action_save_data', $collection = new CAttributeCollection(array(
                    'controller'=> $this,
                    'success'   => Yii::app()->notify->hasSuccess,
                    'do'        => $do,
                    'campaign'  => $campaign,
                    'template'  => $template,
                )));

                if ($collection->success) {
                    if (!empty($redirect)) {
                        $this->redirect($redirect);
                    }
                }
            }

            // since 1.3.4.3
            if ($campaign->option->url_tracking === CampaignOption::TEXT_YES && !empty($template->content)) {
                $contentUrls = $template->getContentUrls();
                if (!empty($contentUrls)) {
                    $templateListsArray = $campaign->getListsDropDownArray();
                    foreach ($templateListsArray as $list_id => $name) {
                        if ($list_id == $campaign->list_id) {
                            unset($templateListsArray[$list_id]);
                            break;
                        }
                    }
                    $templateUrlActionSubscriber = new CampaignTemplateUrlActionSubscriber();
                    $templateUrlActionSubscriber->campaign_id = $campaign->campaign_id;
                    $this->setData(array(
                        'templateListsArray'                => !empty($templateListsArray) ? CMap::mergeArray(array('' => Yii::t('app', 'Choose')), $templateListsArray) : array(),
                        'templateContentUrls'               => CMap::mergeArray(array('' => Yii::t('app', 'Choose')), array_combine($contentUrls, $contentUrls)),
                        'clickAllowedActions'               => CMap::mergeArray(array('' => Yii::t('app', 'Choose')), $templateUrlActionSubscriber->getActions()),
                        'templateUrlActionSubscriber'       => $templateUrlActionSubscriber,
                        'templateUrlActionSubscriberModels' => $templateUrlActionSubscriber->findAllByAttributes(array('template_id' => $template->template_id)),
                    ));

                    // since 1.3.4.5
                    $templateUrlActionListField = new CampaignTemplateUrlActionListField();
                    $templateUrlActionListField->campaign_id = $campaign->campaign_id;
                    $templateUrlActionListField->list_id     = $campaign->list_id;
                    $this->setData(array(
                        'templateUrlActionListField'  => $templateUrlActionListField,
                        'templateUrlActionListFields' => $templateUrlActionListField->findAllByAttributes(array('template_id' => $template->template_id)),
                    ));
                }
            }

            $this->data->templateUp = new CampaignEmailTemplateUpload('upload');
            $viewFile = 'step-template-create';

        } elseif ($do == 'upload') {

            if ($request->isPostRequest && $request->getPost('is_next', 0)) {
                $this->redirect(array('campaigns/confirm', 'campaign_uid' => $campaign_uid));
            }

            $template = $campaign->template;
            if (empty($template)) {
                $template = new CampaignTemplate();
            }
            $template->fieldDecorator->onHtmlOptionsSetup = array($this, '_setEditorOptions');
            $template->campaign_id = $campaign->campaign_id;

            $templateUp = new CampaignEmailTemplateUpload('upload');
            $templateUp->customer_id = (int)Yii::app()->customer->getId();
            $templateUp->campaign    = $campaign;

            $redirect = array('campaigns/template', 'campaign_uid' => $campaign_uid, 'do' => 'create', 'prev' => 'upload');

            if ($request->isPostRequest && ($attributes = (array)$request->getPost($templateUp->modelName, array()))) {
                $templateUp->attributes = $attributes;
                $templateUp->archive = CUploadedFile::getInstance($templateUp, 'archive');
                if (!$templateUp->validate() || !$templateUp->uploader->handleUpload()) {
                    Yii::app()->notify->addError($templateUp->shortErrors->getAllAsString());
                } else {
                    $template->content      = $templateUp->content;
                    $template->inline_css   = $templateUp->inline_css;
                    $template->minify       = $templateUp->minify;

                    if (!empty($campaign->option) && $campaign->option->plain_text_email == CampaignOption::TEXT_YES && $templateUp->auto_plain_text === CampaignTemplate::TEXT_YES && empty($templateUp->plain_text)) {
                        $template->plain_text = CampaignHelper::htmlToText($templateUp->content);
                    }

                    if ($template->save()) {
                        Yii::app()->notify->addSuccess(Yii::t('app', 'Your file has been successfully uploaded!'));
                    } else {
                        Yii::app()->notify->addError($template->shortErrors->getAllAsString());
                    }
                }

                Yii::app()->hooks->doAction('controller_action_save_data', $collection = new CAttributeCollection(array(
                    'controller'=> $this,
                    'success'   => Yii::app()->notify->hasSuccess,
                    'do'        => $do,
                    'campaign'  => $campaign,
                    'template'  => $template,
                    'templateUp'=> $templateUp,
                )));

                if ($collection->success) {
                    $this->redirect($redirect);
                }
            }

            $this->data->templateUp = $templateUp;
            $this->data->template   = $template;
            $viewFile = 'step-template-create';

        } else {

            $this->redirect(array('campaigns/template', 'campaign_uid' => $campaign_uid, 'do' => 'create'));

        }

        // since 1.3.4.2, add a warning if the campaign is paused and template changed
        if ($campaign->getIsPaused()) {
            Yii::app()->notify->addWarning(Yii::t('campaigns', 'This campaign is paused, please have this in mind if you are going to change the template, it will affect subscribers that already received the current template!'));
        }

        $this->setData(array(
            'pageMetaTitle'     => $this->data->pageMetaTitle . ' | '. Yii::t('campaigns', 'Campaign template'),
            'pageHeading'       => Yii::t('campaigns', 'Campaign template'),
            'pageBreadcrumbs'   => array(
                Yii::t('campaigns', 'Campaigns') => $this->createUrl('campaigns/index'),
                $campaign->name => $this->createUrl('campaigns/update', array('campaign_uid' => $campaign_uid)),
                Yii::t('campaigns', 'Template')
            )
        ));

        $this->render($viewFile, compact('campaign'));
    }

    /**
     * Confirm the campaign and schedule it for sending
     */
    public function actionConfirm($campaign_uid)
    {
        $request    = Yii::app()->request;
        $notify     = Yii::app()->notify;
        $campaign   = $this->loadCampaignModel($campaign_uid);

        if (!$campaign->editable) {
            $this->redirect(array('campaigns/overview', 'campaign_uid' => $campaign->campaign_uid));
        }

        $customer = Yii::app()->customer->getModel();
        $campaign->scenario = 'step-confirm';

        if ($campaign->isAutoresponder) {
            $campaign->option->setScenario('step-confirm-ar');
        }

        $segmentSubscribers = 0;
        $listSubscribers = 0;
        $hasError = false;

        if (empty($campaign->template->content)) {
            $hasError = true;
            $notify->addError(Yii::t('campaigns', 'Missing campaign template!'));
        }

        // since 1.3.4.7 - whether must validate sending domain - start
        if (!SendingDomain::model()->getRequirementsErrors() && $customer->getGroupOption('campaigns.must_verify_sending_domain', 'no') == 'yes') {
            $sendingDomain = SendingDomain::model()->findVerifiedByEmail($campaign->from_email, $campaign->customer_id);
            if (empty($sendingDomain)) {
                $emailParts = explode('@', $campaign->from_email);
                $domain = $emailParts[1];
                $notify->addError(Yii::t('campaigns', 'You are required to verify your sending domain({domain}) in order to be able to send this campaign!', array(
                    '{domain}' => CHtml::tag('strong', array(), $domain),
                )));
                $notify->addError(Yii::t('campaigns', 'Please click {link} to add and verify {domain} domain name. After verification, you can send your campaign.', array(
                    '{link}'   => CHtml::link(Yii::t('app', 'here'), array('sending_domains/create')),
                    '{domain}' => CHtml::tag('strong', array(), $domain),
                )));
                $hasError = true;
            }
        }
        // whether must validate sending domain - end

        if (!$hasError && $request->isPostRequest) {
            $campaign->attributes = (array)$request->getPost($campaign->modelName, array());
            $campaign->status     = Campaign::STATUS_PENDING_SENDING;

            // since 1.3.4.2, we allow paused campaigns to be edited.
            if ($campaign->getIsPaused()) {
                $campaign->status = Campaign::STATUS_PAUSED;
            }

            $transaction = Yii::app()->getDb()->beginTransaction();
            $redirect    = array('campaigns/index');
            $saved       = false;

            if (!empty($campaign->temporarySources)) {
                $redirect = array('campaigns/merge_lists', 'campaign_uid' => $campaign_uid);
                $campaign->status = Campaign::STATUS_DRAFT;
            }

            if ($campaign->save()) {
                $saved = true;
                if ($campaign->isAutoresponder || $campaign->isRegular) {
                    $campaign->option->attributes = (array)$request->getPost($campaign->option->modelName, array());
                    if (!$campaign->option->save()) {
                        $saved = false;
                        $notify->addError(Yii::t('app', 'Your form has a few errors, please fix them and try again!'));
                    }
                }

                if ($saved) {
                    if (!empty($campaign->temporarySources)) {
                        $notify->addSuccess(Yii::t('campaigns', 'Please wait while all selected lists for this campaigns are merged. You will be redirected back once everything is done.'));
                    } else {
                        if ($logAction = Yii::app()->customer->getModel()->asa('logAction')) {
                            $logAction->campaignScheduled($campaign);
                        }

                        // since 1.3.5.9
                        $hasAddedSuccessMessage = false;
                        if (($sbw = $campaign->subjectBlacklistWords) || ($cbw = $campaign->contentBlacklistWords)) {
                            $hasAddedSuccessMessage = true;
                            $reason = array();
                            if (!empty($sbw)) {
                                $reason[] = 'Contains blacklisted words in campaign subject!';
                            }
                            if (!empty($cbw)) {
                                $reason[] = 'Contains blacklisted words in campaign body!';
                            }
                            $campaign->block($reason);

                            $notify->addSuccess(Yii::t('campaigns', 'Your campaign({type}) named "{campaignName}" has been successfully saved but it will be blocked from sending until it is reviewed by one of our administrators!', array(
                                '{campaignName}' => $campaign->name,
                                '{type}'         => Yii::t('campaigns', $campaign->type),
                            )));
                        }
                        //

                        if (!$hasAddedSuccessMessage) {
                            $notify->addSuccess(Yii::t('campaigns', 'Your campaign({type}) named "{campaignName}" has been successfully saved and will start sending at {sendDateTime}!', array(
                                '{campaignName}'    => $campaign->name,
                                '{sendDateTime}'    => $campaign->getSendAt(),
                                '{type}'            => Yii::t('campaigns', $campaign->type),
                            )));
                        }
                    }
                }
            } else {
                $notify->addError(Yii::t('app', 'Your form has a few errors, please fix them and try again!'));
            }

            if ($saved) {
                $transaction->commit();
            } else {
                $transaction->rollBack();
            }

            Yii::app()->hooks->doAction('controller_action_save_data', $collection = new CAttributeCollection(array(
                'controller'=> $this,
                'success'   => $notify->hasSuccess,
                'campaign'  => $campaign,
            )));

            if ($collection->success) {
                if (!empty($redirect)) {
                    $this->redirect($redirect);
                }
            }
        }

        $this->setData(array(
            'pageMetaTitle'     => $this->data->pageMetaTitle . ' | '. Yii::t('campaigns', 'Campaign overview'),
            'pageHeading'       => Yii::t('campaigns', 'Campaign confirmation'),
            'pageBreadcrumbs'   => array(
                Yii::t('campaigns', 'Campaigns') => $this->createUrl('campaigns/index'),
                CHtml::encode($campaign->name) => $this->createUrl('campaigns/update', array('campaign_uid' => $campaign_uid)),
                Yii::t('campaigns', 'Confirmation')
            )
        ));

        $this->render('step-confirm', compact('campaign', 'listSubscribers', 'segmentSubscribers'));
    }

    /**
     * Merge multiple lists into one for this campaign
     */
    public function actionMerge_lists($campaign_uid)
    {
        $request    = Yii::app()->request;
        $notify     = Yii::app()->notify;
        $campaign   = $this->loadCampaignModel($campaign_uid);
        $customer   = Yii::app()->customer->getModel();

        if ($customer->getGroupOption('campaigns.send_to_multiple_lists', 'no') != 'yes') {
            if ($request->isAjaxRequest) {
                return $this->renderJson(array(
                    'finished'      => true,
                    'progress_text' => Yii::t('campaigns', 'Your don\'t have enough priviledges to access this feature!'),
                ));
            }
            $notify->addError(Yii::t('campaigns', 'Your don\'t have enough priviledges to access this feature!'));
            $this->redirect(array('campaigns/confirm', 'campaign_uid' => $campaign_uid));
        }

        if (empty($campaign->temporarySources)) {
            if ($request->isAjaxRequest) {
                return $this->renderJson(array(
                    'finished'      => true,
                    'progress_text' => Yii::t('campaigns', 'This campaign does not support sending to multiple lists!'),
                ));
            }
            $notify->addError(Yii::t('campaigns', 'This campaign does not support sending to multiple lists!'));
            $this->redirect(array('campaigns/confirm', 'campaign_uid' => $campaign_uid));
        }

        if (!Yii::app()->mutex->acquire('mergeListsFor' . $campaign_uid)) {
            if ($request->isAjaxRequest) {
                return $this->renderJson(array(
                    'finished'      => true,
                    'progress_text' => Yii::t('campaigns', 'Unable to acquire lock!'),
                ));
            }
            $notify->addError(Yii::t('campaigns', 'Unable to acquire lock!'));
            $this->redirect(array('campaigns/confirm', 'campaign_uid' => $campaign_uid));
        }

        $listId           = (int)$request->getPost('list_id', $campaign->list_id);
        $segmentId        = (int)$request->getPost('segment_id', $campaign->segment_id);
        $sourceId         = (int)$request->getPost('source_id');
        $clid             = (int)$request->getPost('clid');
        $processedTotal   = (int)$request->getPost('processed_total', 0);
        $processedSuccess = (int)$request->getPost('processed_success', 0);
        $processedError   = (int)$request->getPost('processed_error', 0);
        $progressText     = Yii::t('campaigns', 'The merging process is running, please wait...');
        $finished         = false;

        if ($memoryLimit = $customer->getGroupOption('lists.copy_subscribers_memory_limit')) {
            ini_set('memory_limit', $memoryLimit);
        }

        $criteria = new CDbCriteria();
        $criteria->compare('list_id', $listId);
        $criteria->compare('customer_id', (int)Yii::app()->customer->getId());
        $criteria->addNotInCondition('status', array(Lists::STATUS_PENDING_DELETE));
        $fromList = Lists::model()->find($criteria);

        if (empty($fromList)) {
            if ($request->isAjaxRequest) {
                return $this->renderJson(array(
                    'finished'      => true,
                    'progress_text' => Yii::t('campaigns', 'Unable to load the list!'),
                ));
            }
            $this->redirect(array('campaigns/confirm', 'campaign_uid' => $campaign_uid));
        }

        $fromSegment = null;
        if (!empty($segmentId)) {
            $fromSegment = ListSegment::model()->findByAttributes(array(
                'list_id'    => $fromList->list_id,
                'segment_id' => $segmentId,
            ));

            if (empty($fromSegment)) {
                if ($request->isAjaxRequest) {
                    return $this->renderJson(array(
                        'finished'      => true,
                        'progress_text' => Yii::t('campaigns', 'Unable to load the segment!'),
                    ));
                }
                $this->redirect(array('campaigns/confirm', 'campaign_uid' => $campaign_uid));
            }
        }

        if (!empty($fromSegment)) {
            $count = $fromSegment->countSubscribers();
        } else {
            $count = $fromList->confirmedSubscribersCount;
        }

        $limit  = (int)$customer->getGroupOption('lists.copy_subscribers_at_once', 100);
        $pages  = $count <= $limit ? 1 : ceil($count / $limit);
        $page   = (int)$request->getPost('page', 1);
        $page   = $page < 1 ? 1 : $page;
        $offset = ($page - 1) * $limit;

        $attributes = array(
            'total'             => $count,
            'processed_total'   => $processedTotal,
            'processed_success' => $processedSuccess,
            'processed_error'   => $processedError,
            'percentage'        => 0,
            'progress_text'     => Yii::t('campaigns', 'The merging process is starting, please wait...'),
            'post_url'          => $this->createUrl('campaigns/merge_lists', array('campaign_uid' => $campaign_uid)),
            'list_id'           => (int)$listId,
            'segment_id'        => (int)$segmentId,
            'source_id'         => (int)$sourceId,
            'clid'              => (int)$clid,
            'page'              => (int)$page,
        );

        $jsonAttributes = CJSON::encode($attributes);

        if (!$request->isAjaxRequest) {
            $this->getData('pageScripts')->add(array('src' => AssetsUrl::js('campaign-lists-merge.js')));
            $this->setData(array(
                'pageMetaTitle'     => $this->data->pageMetaTitle.' | '.Yii::t('campaigns', 'Merge lists'),
                'pageHeading'       => Yii::t('campaigns', 'Merge lists'),
                'pageBreadcrumbs'   => array(
                    Yii::t('campaigns', 'Campaigns') => $this->createUrl('campaigns/index'),
                    CHtml::encode($campaign->name) => $this->createUrl('campaigns/update', array('campaign_uid' => $campaign_uid)),
                    Yii::t('campaigns', 'Merge lists')
                )
            ));
            return $this->render('merge-lists', compact('campaign', 'jsonAttributes'));
        }

        if (empty($clid)) {
            if (!($list = $fromList->copy())) {
                return $this->renderJson(array(
                    'finished'      => true,
                    'progress_text' => Yii::t('campaigns', 'Unable to copy the campaign initial list.'),
                ));
            }
            $listName = array('MERGED - ' . $list->name);
            foreach ($campaign->temporarySources as $source) {
                $listName[] = empty($source->segment_id) ? $source->list->name : $source->list->name . '/' . $source->segment->name;
            }
            $list->name   = implode(', ', $listName);
            $list->name   = StringHelper::truncateLength($list->name, 100);
            $list->merged = Lists::TEXT_YES;
            $list->save(false);
            $clid = $list->list_id;
            $attributes['clid'] = (int)$clid;

            // since 1.3.5.4, make sure we unsubscribe from all lists.
            $sourceLists = array($campaign->list_id);
            foreach ($campaign->temporarySources as $source) {
                $sourceLists[] = $source->list_id;
            }
            foreach ($sourceLists as $sourceListID) {
                $action = new ListSubscriberAction();
                $action->source_list_id = $list->list_id;
                $action->source_action  = ListSubscriberAction::ACTION_UNSUBSCRIBE;
                $action->target_list_id = $sourceListID;
                $action->target_action  = ListSubscriberAction::ACTION_UNSUBSCRIBE;
                $action->save(false);
            }
        }

        if (empty($list)) {
            $criteria = new CDbCriteria();
            $criteria->compare('list_id', (int)$clid);
            $criteria->compare('customer_id', (int)Yii::app()->customer->getId());
            $criteria->addNotInCondition('status', array(Lists::STATUS_PENDING_DELETE));
            $list = Lists::model()->find($criteria);

            if (empty($list)) {
                return $this->renderJson(array(
                    'finished'      => true,
                    'progress_text' => Yii::t('campaigns', 'Unable to copy the campaign initial list.'),
                ));
            }
        }

        if (!empty($fromSegment)) {
            $criteria = new CDbCriteria;
            $criteria->select = 't.*';
            $subscribers = $fromSegment->findSubscribers($offset, $limit, $criteria);
        } else {
            $criteria = new CDbCriteria();
            $criteria->compare('list_id', (int)$listId);
            $criteria->compare('status', ListSubscriber::STATUS_CONFIRMED);
            $criteria->limit  = $limit;
            $criteria->offset = $offset;
            $subscribers = ListSubscriber::model()->findAll($criteria);
        }

        if (empty($subscribers)) {
            if (!empty($campaign->temporarySources)) {
                $sources = $campaign->temporarySources;
                foreach ($sources as $index => $source) {
                    if ($source->source_id == $sourceId) {
                        $source->delete();
                        unset($sources[$index]);
                        break;
                    }
                }
                if (!empty($sources)) {
                    $source  = array_shift($sources);
                    return $this->renderJson(array_merge($attributes, array(
                        'total'             => 0,
                        'processed_total'   => 0,
                        'processed_success' => 0,
                        'processed_error'   => 0,
                        'percentage'        => 0,
                        'page'              => 1,
                        'reset_counters'    => true,
                        'progress_text'     => Yii::t('campaigns', 'Now merging the list/segment "{sourceName}"', array('{sourceName}' => $source->name)),
                        'list_id'           => (int)$source->list_id,
                        'segment_id'        => (int)$source->segment_id,
                        'source_id'         => (int)$source->source_id,
                        'clid'              => (int)$clid,
                    )));
                }
            }

            // update the list id for custom fields action so that it properly loads the custom fields.
            $openActionListFields = CampaignOpenActionListField::model()->findAllByAttributes(array(
                'campaign_id' => $campaign->campaign_id,
            ));
            if (!empty($openActionListFields)) {
                foreach ($openActionListFields as $openActionListField) {
                    $theOtherListField = ListField::model()->findByAttributes(array(
                        'list_id' => $list->list_id,
                        'tag'     => $openActionListField->field->tag
                    ));
                    if (empty($theOtherListField)) {
                        continue;
                    }
                    $openActionListField->field_id = $theOtherListField->field_id;
                    $openActionListField->list_id  = $list->list_id;
                    $openActionListField->save(false);
                }
            }

            // delete, just in case...
            CampaignTemporarySource::model()->deleteAllByAttributes(array(
                'campaign_id' => (int)$campaign->campaign_id,
            ));
            $campaign->segment_id = null;
            $campaign->list_id    = $list->list_id;
            $campaign->status     = Campaign::STATUS_PENDING_SENDING;
            $campaign->save(false);
            if ($logAction = Yii::app()->customer->getModel()->asa('logAction')) {
                $logAction->campaignScheduled($campaign);
            }
            $notify->addSuccess(Yii::t('campaigns', 'Your campaign({type}) named "{campaignName}" has been successfully saved and will start sending at {sendDateTime}!', array(
                '{campaignName}'    => $campaign->name,
                '{sendDateTime}'    => $campaign->getSendAt(),
                '{type}'            => Yii::t('campaigns', $campaign->type),
            )));
            //

            return $this->renderJson(array(
                'finished'      => true,
                'progress_text' => Yii::t('campaigns', 'The merging process is done, your merged list for this campaign is {list}. Please wait to be redirected...', array('{list}' => $list->name)),
                'redirect'      => $this->createUrl('campaigns/index'),
                'timeout'       => 5000,
            ));
        }

        $transaction = Yii::app()->getDb()->beginTransaction();

        try {
            foreach ($subscribers as $subscriber) {
                $processedTotal++;
                if ($newSubscriber = $subscriber->copyToList($list->list_id, false)) {
                    $processedSuccess++;
                } else {
                    $processedError++;
                }
            }
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
        }

        $percentage = round((($processedTotal / $count) * 100), 2);

        return $this->renderJson(array_merge($attributes, array(
            'processed_total'   => $processedTotal,
            'processed_success' => $processedSuccess,
            'processed_error'   => $processedError,
            'percentage'        => $percentage,
            'page'              => $page + 1,
            'progress_text'     => $progressText,
            'finished'          => $finished,
            'clid'              => (int)$clid,
        )));
    }

    /**
     * Test the campaign email template by sending it to desired email addressed
     */
    public function actionTest($campaign_uid)
    {
        $request    = Yii::app()->request;
        $notify     = Yii::app()->notify;
        $campaign   = $this->loadCampaignModel($campaign_uid);
        $template   = $campaign->template;

        if ($campaign->pendingDelete) {
            $this->redirect(array('campaigns/index'));
        }

        if (!$campaign->editable) {
            $this->redirect(array('campaigns/overview', 'campaign_uid' => $campaign->campaign_uid));
        }

        if (!$request->getPost('email')) {
            $notify->addError(Yii::t('campaigns', 'Please specify the email address to where we should send the test email.'));
            $this->redirect(array('campaigns/template', 'campaign_uid' => $campaign_uid));
        }

        $emails = explode(',', $request->getPost('email'));
        $emails = array_map('trim', $emails);
        $emails = array_unique($emails);
        $emails = array_slice($emails, 0, 10);

        $server = DeliveryServer::pickServer(0, $campaign);
        if (empty($server)) {
            $notify->addError(Yii::t('app', 'Email delivery is temporary disabled.'));
            $this->redirect(array('campaigns/template', 'campaign_uid' => $campaign_uid));
        }

        foreach ($emails as $index => $email) {
            if (!FilterVarHelper::email($email)) {
                $notify->addError(Yii::t('email_templates',  'The email address {email} does not seem to be valid!', array('{email}' => CHtml::encode($email))));
                unset($emails[$index]);
                continue;
            }
        }

        if (empty($emails)) {
            $notify->addError(Yii::t('campaigns', 'Cannot send using provided email address(es)!'));
            $this->redirect(array('campaigns/template', 'campaign_uid' => $campaign_uid));
        }

        $subscriber = ListSubscriber::model()->findByAttributes(array(
            'list_id'   => $campaign->list->list_id,
            'status'    => ListSubscriber::STATUS_CONFIRMED,
        ));

        // $emailSubject   = '['. strtoupper(Yii::t('app', 'Test')) .'] '.$campaign->name;
        $emailSubject   = $campaign->subject;
        $onlyPlainText  = !empty($template->only_plain_text) && $template->only_plain_text === CampaignTemplate::TEXT_YES;
        $emailContent   = !$onlyPlainText ? $template->content : $template->plain_text;
        $embedImages    = array();

        if (!$onlyPlainText && !empty($campaign->option) && !empty($campaign->option->embed_images) && $campaign->option->embed_images == CampaignOption::TEXT_YES) {
            list($emailContent, $embedImages) = CampaignHelper::embedContentImages($emailContent, $campaign);
        }

        // since 1.3.5.9
        $fromEmailCustom = null;
        $fromNameCustom  = null;

        if (!empty($subscriber)) {

            // since 1.3.5.9
            // really blind check to see if it contains a tag
            if (strpos($campaign->from_email, '[') !== false || strpos($campaign->from_name, '[') !== false) {
                $searchReplace = CampaignHelper::getSubscriberFieldsSearchReplace($campaign, $subscriber);
                if (strpos($campaign->from_email, '[') !== false) {
                    $fromEmailCustom = str_replace(array_keys($searchReplace), array_values($searchReplace), $campaign->from_email);
                    if (!FilterVarHelper::email($fromEmailCustom)) {
                        $fromEmailCustom = null;
                    }
                }
                if (strpos($campaign->from_name, '[') !== false) {
                    $fromNameCustom = str_replace(array_keys($searchReplace), array_values($searchReplace), $campaign->from_name);
                }
            }
            //

            if (!$onlyPlainText && !empty($campaign->option) && $campaign->option->xml_feed == CampaignOption::TEXT_YES) {
                $emailContent = CampaignXmlFeedParser::parseContent($emailContent, $campaign, $subscriber, false);
            }

            if (!$onlyPlainText && !empty($campaign->option) && $campaign->option->json_feed == CampaignOption::TEXT_YES) {
                $emailContent = CampaignJsonFeedParser::parseContent($emailContent, $campaign, $subscriber, false);
            }

            $emailData  = CampaignHelper::parseContent($emailContent, $campaign, $subscriber, false);
            list(, $_emailSubject, $emailContent) = $emailData;

            // since 1.3.5.3
            if (!empty($campaign->option) && $campaign->option->xml_feed == CampaignOption::TEXT_YES) {
                $_emailSubject = CampaignXmlFeedParser::parseContent($_emailSubject, $campaign, $subscriber, false, $emailSubject);
            }

            if (!empty($campaign->option) && $campaign->option->json_feed == CampaignOption::TEXT_YES) {
                $_emailSubject = CampaignJsonFeedParser::parseContent($_emailSubject, $campaign, $subscriber, false, $emailSubject);
            }

            if (!empty($_emailSubject)) {
                $emailSubject = $_emailSubject;
            }
        }

        if (empty($emailSubject)) {
            $emailSubject   = '['. strtoupper(Yii::t('app', 'Test')) .'] ' . $campaign->name;
        }

        if ($onlyPlainText) {
            $emailContent = preg_replace('%<br(\s{0,}?/?)?>%i', "\n", $emailContent);
        }

        $customer = Yii::app()->customer->getModel();
        $fromName = !empty($fromNameCustom) ? $fromNameCustom : $campaign->from_name;

        if (empty($fromName)) {
            $fromName = $customer->getFullName();
            if (!empty($customer->company)) {
                $fromName = $customer->company->name;
            }
            if (empty($fromName)) {
                $fromName = $customer->email;
            }
        }

        $fromEmail = $request->getPost('from_email');
        if (!empty($fromEmail) && !FilterVarHelper::email($fromEmail)) {
            $fromEmail = null;
        }

        if (empty($fromEmail) && !empty($fromEmailCustom)) {
            $fromEmail = $fromEmailCustom;
        }

        foreach ($emails as $email) {

            $params = array(
                'to'            => $email,
                'fromName'      => $fromName,
                'subject'       => $emailSubject,
                'body'          => $onlyPlainText ? null : $emailContent,
                'plainText'     => $onlyPlainText ? $emailContent : null,
                'embedImages'   => $embedImages,
                'onlyPlainText' => $onlyPlainText,
            );

            if ($fromEmail) {
                $params['from'] = array($fromEmail => $fromName);
            }

            $serverLog = null;
            $sent = false;
            for ($i = 0; $i < 3; ++$i) {
                if ($sent = $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_CAMPAIGN_TEST)->setDeliveryObject($campaign)->sendEmail($params)) {
                    break;
                }
                $serverLog = $server->getMailer()->getLog();
                sleep(1);
                $server = DeliveryServer::pickServer($server->server_id, $campaign);
            }

            if (!$sent) {
                $notify->addError(Yii::t('campaigns', 'Unable to send the test email to {email}!', array(
                    '{email}' => CHtml::encode($email),
                )) . (!empty($serverLog) ? sprintf(' (%s)', $serverLog) : ''));
            } else {
                $notify->addSuccess(Yii::t('campaigns', 'Test email successfully sent to {email}!', array(
                    '{email}' => CHtml::encode($email),
                )));
            }
        }

        $this->redirect(array('campaigns/template', 'campaign_uid' => $campaign_uid));
    }

    /**
     * List available list segments when choosing a list for a campaign
     */
    public function actionList_segments($list_id)
    {
        $request = Yii::app()->request;
        if (!$request->isAjaxRequest) {
            $this->redirect(array('campaigns/index'));
        }

        $criteria = new CDbCriteria();
        $criteria->compare('list_id', (int)$list_id);
        $criteria->compare('customer_id', (int)Yii::app()->customer->getId());
        $criteria->addNotInCondition('status', array(Lists::STATUS_PENDING_DELETE));

        $list = Lists::model()->find($criteria);
        if (empty($list)){
            return $this->renderJson(array('segments' => array()));
        }

        $campaign = new Campaign();
        $campaign->list_id = $list->list_id;
        $segments = $campaign->getSegmentsDropDownArray();

        $json = array();
        $json[] = array(
            'segment_id'    => '',
            'name'          => Yii::t('app', 'Choose')
        );

        foreach ($segments as $segment_id => $name) {
            $json[] = array(
                'segment_id' => $segment_id,
                'name'       => CHtml::encode($name)
            );
        }

        return $this->renderJson(array('segments' => $json));
    }

    /**
     * Copy campaign
     */
    public function actionCopy($campaign_uid)
    {
        $campaign = $this->loadCampaignModel($campaign_uid);
        $list     = $campaign->list;
        $customer = $list->customer;
        $canCopy  = true;
        $request  = Yii::app()->request;
        $notify   = Yii::app()->notify;

        if (($maxCampaigns = (int)$customer->getGroupOption('campaigns.max_campaigns', -1)) > -1) {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$customer->customer_id);
            $criteria->addNotInCondition('status', array(Campaign::STATUS_PENDING_DELETE));
            $campaignsCount = Campaign::model()->count($criteria);
            if ($campaignsCount >= $maxCampaigns) {
                $notify->addWarning(Yii::t('lists', 'You have reached the maximum number of allowed campaigns.'));
                $canCopy = false;
            }
        }

        $copied = false;
        if ($canCopy) {
            $copied = $campaign->copy();
        }

        if ($copied) {
            $notify->addSuccess(Yii::t('campaigns', 'Your campaign was successfully copied!'));
        }

        if (!$request->isAjaxRequest) {
            $this->redirect($request->getPost('returnUrl', array('campaigns/index')));
        }

        return $this->renderJson(array(
            'next' => !empty($copied) ? $this->createUrl('campaigns/update', array('campaign_uid' => $copied->campaign_uid)) : '',
        ));
    }

    /**
     * Delete campaign, will remove all campaign related data
     */
    public function actionDelete($campaign_uid)
    {
        $campaign = $this->loadCampaignModel($campaign_uid);

        if ($campaign->removable) {

            $campaign->delete();

            if ($logAction = Yii::app()->customer->getModel()->asa('logAction')) {
                $logAction->campaignDeleted($campaign);
            }
        }

        $request = Yii::app()->request;
        $notify = Yii::app()->notify;

        $redirect = null;
        if (!$request->getQuery('ajax')) {
            $notify->addSuccess(Yii::t('campaigns', 'Your campaign was successfully deleted!'));
            $redirect = $request->getPost('returnUrl', array('campaigns/index'));
        }

        // since 1.3.5.9
        Yii::app()->hooks->doAction('controller_action_delete_data', $collection = new CAttributeCollection(array(
            'controller' => $this,
            'model'      => $campaign,
            'redirect'   => $redirect,
        )));

        if ($collection->redirect) {
            $this->redirect($collection->redirect);
        }
    }

    /**
     * Allows to pause/unpause the sending of a campaign
     */
    public function actionPause_unpause($campaign_uid)
    {
        $campaign = $this->loadCampaignModel($campaign_uid);

        $campaign->pauseUnpause();

        $request = Yii::app()->request;
        $notify = Yii::app()->notify;

        if (!$request->getQuery('ajax')) {
            $notify->addSuccess(Yii::t('campaigns', 'Your campaign was successfully changed!'));
            $this->redirect($request->getPost('returnUrl', array('campaigns/index')));
        }
    }

    /**
     * Allows to resume sending of a stuck campaign
     */
    public function actionResume_sending($campaign_uid)
    {
        $campaign = $this->loadCampaignModel($campaign_uid);

        if ($campaign->isProcessing) {
            $campaign->saveStatus(Campaign::STATUS_SENDING);
        }

        $request = Yii::app()->request;
        $notify = Yii::app()->notify;

        if (!$request->isAjaxRequest) {
            $notify->addSuccess(Yii::t('campaigns', 'Your campaign was successfully changed!'));
            $this->redirect($request->getPost('returnUrl', array('campaigns/index')));
        }
    }

    /**
     * Allows to mark a campaign as sent
     */
    public function actionMarksent($campaign_uid)
    {
        $campaign = $this->loadCampaignModel($campaign_uid);

        if ($campaign->getCanBeMarkedAsSent()) {
            $campaign->saveStatus(Campaign::STATUS_SENT);
        }

        $request = Yii::app()->request;
        $notify = Yii::app()->notify;

        if (!$request->isAjaxRequest) {
            $notify->addSuccess(Yii::t('campaigns', 'Your campaign was successfully changed!'));
            $this->redirect($request->getPost('returnUrl', array('campaigns/index')));
        }
    }

    /**
     * Run a bulk action against the campaigns
     */
    public function actionBulk_action()
    {
        $request = Yii::app()->request;
        $notify  = Yii::app()->notify;

        $action = $request->getPost('bulk_action');
        $items  = array_unique((array)$request->getPost('bulk_item', array()));

        if ($action == Campaign::BULK_ACTION_DELETE && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignModel($item))) {
                    continue;
                }
                if (!$campaign->removable) {
                    continue;
                }
                $campaign->delete();
                $affected++;
                if ($logAction = Yii::app()->customer->getModel()->asa('logAction')) {
                    $logAction->campaignDeleted($campaign);
                }
            }
            if ($affected) {
                $notify->addSuccess(Yii::t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == Campaign::BULK_ACTION_COPY && count($items)) {
            $customer = Yii::app()->customer->getModel();
            $affected = 0;
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignModel($item))) {
                    continue;
                }
                if (($maxCampaigns = (int)$customer->getGroupOption('campaigns.max_campaigns', -1)) > -1) {
                    $criteria = new CDbCriteria();
                    $criteria->compare('customer_id', (int)$customer->customer_id);
                    $criteria->addNotInCondition('status', array(Campaign::STATUS_PENDING_DELETE));
                    $campaignsCount = Campaign::model()->count($criteria);
                    if ($campaignsCount >= $maxCampaigns) {
                        continue;
                    }
                }
                if (!$campaign->copy()) {
                    continue;
                }
                $affected++;
            }
            if ($affected) {
                $notify->addSuccess(Yii::t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == Campaign::BULK_ACTION_PAUSE_UNPAUSE && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignModel($item))) {
                    continue;
                }
                $campaign->pauseUnpause();
                $affected++;
            }
            if ($affected) {
                $notify->addSuccess(Yii::t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == Campaign::BULK_ACTION_MARK_SENT && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignModel($item))) {
                    continue;
                }
                if (!$campaign->getCanBeMarkedAsSent()) {
                    continue;
                }
                $campaign->saveStatus(Campaign::STATUS_SENT);
                $affected++;
            }
            if ($affected) {
                $notify->addSuccess(Yii::t('app', 'The action has been successfully completed!'));
            }
        }

        $defaultReturn = $request->getServer('HTTP_REFERER', array('campaigns/index'));
        $this->redirect($request->getPost('returnUrl', $defaultReturn));
    }


    /**
     * Tries to get the spam score for campaign.
     * For now this is in beta usage, need to see how it behaves.
     *
     */
    public function actionSpamcheck($campaign_uid)
    {
        $campaign = $this->loadCampaignModel($campaign_uid);

        $score = CampaignHelper::getSpamScore($campaign);

        if (!is_array($score) || !isset($score['score'])) {
            return $this->renderJson(array(
                'result' => 'error',
                'message'=> Yii::t('campaigns', 'Unable to get the spam score for your campaign!')
            ));
        }

        $message = Yii::t('campaigns', 'Between 0 and 5, your campaign spam score is {score}.', array(
            '{score}' => $score['score'],
        ));

        return $this->renderJson(array(
            'result'    => 'success',
            'message'   => $message,
            'response'  => $score,
        ));
    }

    /**
     * Remove certain campaign attachment
     */
    public function actionRemove_attachment($campaign_uid, $attachment_id)
    {
        $campaign = $this->loadCampaignModel($campaign_uid);
        $attachment = CampaignAttachment::model()->findByAttributes(array(
            'attachment_id' => (int)$attachment_id,
            'campaign_id'   => (int)$campaign->campaign_id,
        ));

        if (!empty($attachment)) {
            $attachment->delete();
        }

        $request = Yii::app()->request;
        $notify = Yii::app()->notify;

        if (!$request->isAjaxRequest) {
            $notify->addSuccess(Yii::t('campaigns', 'Your campaign attachment was successfully removed!'));
            $this->redirect($request->getPost('returnUrl', array('campaigns/index')));
        }
    }

    public function actionSync_datetime()
    {
        $customer   = Yii::app()->customer->getModel();
        $request    = Yii::app()->request;

        $timeZoneDateTime   = date('Y-m-d H:i:s', strtotime($request->getQuery('date', date('Y-m-d H:i:s'))));
        $timeZoneTimestamp  = strtotime($timeZoneDateTime);
        $localeDateTime     = Yii::app()->dateFormatter->formatDateTime($timeZoneTimestamp, 'short', 'short');

        // since the date is already in customer timezone we need to convert it back to utc
        $sourceTimeZone      = new DateTimeZone($customer->timezone);
        $destinationTimeZone = new DateTimeZone(Yii::app()->timeZone);
        $dateTime            = new DateTime($timeZoneDateTime, $sourceTimeZone);
        $dateTime->setTimezone($destinationTimeZone);
        $utcDateTime = $dateTime->format('Y-m-d H:i:s');

        return $this->renderJson(array(
            'localeDateTime'  => $localeDateTime,
            'utcDateTime'     => $utcDateTime,
        ));
    }

    public function actionGoogle_utm_tags($campaign_uid)
    {
        $campaign = $this->loadCampaignModel($campaign_uid);
        $request  = Yii::app()->request;
        $notify   = Yii::app()->notify;

        if (empty($campaign->template) || empty($campaign->template->content)) {
            $notify->addError(Yii::t('campaigns', 'Please use a template for this campaign in order to insert the google utm tags!'));
            $this->redirect(array('campaigns/template', 'campaign_uid' => $campaign->campaign_uid));
        }

        $pattern = $request->getPost('google_utm_pattern');
        if (empty($pattern)) {
            $notify->addError(Yii::t('campaigns', 'Please specify a pattern in order to insert the google utm tags!'));
            $this->redirect(array('campaigns/template', 'campaign_uid' => $campaign->campaign_uid));
        }

        $campaign->template->content = CampaignHelper::injectGoogleUtmTagsIntoTemplate($campaign->template->content, $pattern);
        $campaign->template->save(false);

        $notify->addSuccess(Yii::t('campaigns', 'The google utm tags were successfully inserted into your template!'));
        $this->redirect(array('campaigns/template', 'campaign_uid' => $campaign->campaign_uid));
    }

    /**
     * Helper method to load the campaign AR model
     */
    public function loadCampaignModel($campaign_uid)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)Yii::app()->customer->getId());
        $criteria->compare('campaign_uid', $campaign_uid);
        $criteria->addNotInCondition('status', array(Campaign::STATUS_PENDING_DELETE));

        $model = Campaign::model()->find($criteria);

        if($model === null) {
            throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
        }

        if ($model->pendingDelete) {
            $this->redirect(array('campaigns/index'));
        }

        return $model;
    }

    /**
     * Callback method to setup the editor for the template step
     */
    public function _setEditorOptions(CEvent $event)
    {
        if ($event->params['attribute'] == 'content') {
            $options = array();
            if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
                $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
            }
            $options['id'] = CHtml::activeId($event->sender->owner, 'content');
            $options['fullPage'] = true;
            $options['allowedContent'] = true;
            $options['contentsCss'] = array();
            $options['height'] = 800;

            $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
        }
    }
}
