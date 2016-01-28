<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * List_subscribersController
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */

class List_subscribersController extends Controller
{
    // access rules for this controller
    public function accessRules()
    {
        return array(
            // allow all authenticated users on all actions
            array('allow', 'users' => array('@')),
            // deny all rule.
            array('deny'),
        );
    }
    
    /**
     * Handles the listing of the email list subscribers.
     * The listing is based on page number and number of subscribers per page.
     * This action will produce a valid ETAG for caching purposes.
     */
    public function actionIndex($list_uid)
    {
        $request = Yii::app()->request;

        $criteria = new CDbCriteria();
        $criteria->compare('list_uid', $list_uid);
        $criteria->compare('customer_id', (int)Yii::app()->user->getId());
        $criteria->addNotInCondition('status', array(Lists::STATUS_PENDING_DELETE));
        $list = Lists::model()->find($criteria);
        
        if (empty($list)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscribers list does not exist2.')
            ), 404);
        }

        $criteria = new CDbCriteria();
        $criteria->compare('list_id', $list->list_id);
        $fields = ListField::model()->findAll($criteria);
        
        if (empty($fields)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscribers list does not have any custom field defined.')
            ), 404);
        }
        
        $perPage    = (int)$request->getQuery('per_page', 10);
        $page       = (int)$request->getQuery('page', 1);
        
        $maxPerPage = 50;
        $minPerPage = 10;
        
        if ($perPage < $minPerPage) {
            $perPage = $minPerPage;
        }
        
        if ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }
        
        if ($page < 1) {
            $page = 1;
        }
        
        $data = array(
            'count'         => null,
            'total_pages'   => null,
            'current_page'  => null,
            'next_page'     => null,
            'prev_page'     => null,
            'records'       => array(),
        );
        
        $criteria = new CDbCriteria();
        $criteria->select = 't.subscriber_id, t.subscriber_uid, t.status, t.source, t.ip_address';
        $criteria->compare('t.list_id', (int)$list->list_id);

        $count = ListSubscriber::model()->count($criteria);
        
        if ($count == 0) {
            return $this->renderJson(array(
                'status'    => 'success',
                'data'      => $data
            ), 200);
        }
        
        $totalPages = ceil($count / $perPage);
        
        $data['count']          = $count;
        $data['current_page']   = $page;
        $data['next_page']      = $page < $totalPages ? $page + 1 : null;
        $data['prev_page']      = $page > 1 ? $page - 1 : null;
        $data['total_pages']    = $totalPages;
        
        $criteria->order    = 't.subscriber_id DESC';
        $criteria->limit    = $perPage;
        $criteria->offset   = ($page - 1) * $perPage;
        
        $subscribers = ListSubscriber::model()->findAll($criteria);

        foreach ($subscribers as $subscriber) {
            $record = array('subscriber_uid' => null); // keep this first!
            foreach ($fields as $field) {
                $valueModel = ListFieldValue::model()->findByAttributes(array(
                    'field_id'      => $field->field_id,
                    'subscriber_id' => $subscriber->subscriber_id,
                ));
                $record[$field->tag] = !empty($valueModel->value) ? $valueModel->value : null;
            }
            
            $record['subscriber_uid']   = $subscriber->subscriber_uid;
            $record['status']           = $subscriber->status;
            $record['source']           = $subscriber->source;
            $record['ip_address']       = $subscriber->ip_address;
            
            $data['records'][] = $record;
        }

        return $this->renderJson(array(
            'status'    => 'success',
            'data'      => $data
        ), 200);
    }
    
    /**
     * Handles the listing of a single subscriber from a list.
     * This action will produce a valid ETAG for caching purposes.
     * 
     * @param $list_uid The list unique id
     * @param subscriber_uid The subscriber unique id
     */
    public function actionView($list_uid, $subscriber_uid)
    {
        $request = Yii::app()->request;

        if (!($list = $this->loadListByUid($list_uid))) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscribers list does not exist3.')
            ), 404);
        }
        
        $subscriber = ListSubscriber::model()->findByAttributes(array(
            'subscriber_uid'    => $subscriber_uid,
            'list_id'           => $list->list_id,
        ));
        if (empty($subscriber)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscriber does not exist in this list.')
            ), 404);
        }
        
        $fields = ListField::model()->findAllByAttributes(array(
            'list_id' => $list->list_id,
        ));
        
        if (empty($fields)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscribers list does not have any custom field defined.')
            ), 404);
        }
        
        $data = array(
            'record' => array(
                'subscriber_uid' => null, 
                'status'         => null,
                'source'         => null,
                'ip_address'     => null,
            ),
        );
        
        foreach ($fields as $field) {
            $valueModel = ListFieldValue::model()->findByAttributes(array(
                'field_id'      => $field->field_id,
                'subscriber_id' => $subscriber->subscriber_id,
            ));
            $data['record'][$field->tag] = !empty($valueModel->value) ? $valueModel->value : null;
        }

        $data['record']['subscriber_uid'] = $subscriber->subscriber_uid;
        $data['record']['status']         = $subscriber->status;
        $data['record']['source']         = $subscriber->source;
        $data['record']['ip_address']     = $subscriber->ip_address;
        
        return $this->renderJson(array(
            'status'    => 'success',
            'data'      => $data,
        ), 200);
    }
    
    /**
     * Handles the creation of a new subscriber for a certain email list.
     * 
     * @param $list_uid The list unique id where this subscriber should go
     */
    public function actionCreate($list_uid)
    {
        $request = Yii::app()->request;
        
        if (!$request->isPostRequest) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'Only POST requests allowed for this endpoint.')
            ), 400);
        }
        
        $email = $request->getPost('EMAIL');
        if (empty($email)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'Please provide the subscriber email address.')
            ), 422);
        }
        
        $validator = new CEmailValidator();
        $validator->allowEmpty = false;
        if (Yii::app()->options->get('system.common.dns_email_check', false)) {
            $validator->checkMX     = CommonHelper::functionExists('checkdnsrr');
            $validator->checkPort   = CommonHelper::functionExists('dns_get_record') && CommonHelper::functionExists('fsockopen');    
        }

        if (!$validator->validateValue($email)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'Please provide a valid email address.')
            ), 422);
        }
        
        if (!($list = $this->loadListByUid($list_uid))) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscribers list does not exist4.')
            ), 404);
        }
        
        $customer                = $list->customer;
        $maxSubscribersPerList   = (int)$customer->getGroupOption('lists.max_subscribers_per_list', -1);
        $maxSubscribers          = (int)$customer->getGroupOption('lists.max_subscribers', -1);
        
        if ($maxSubscribers > -1 || $maxSubscribersPerList > -1) {
            $criteria = new CDbCriteria();
            $criteria->select = 'COUNT(DISTINCT(t.email)) as counter';
            
            if ($maxSubscribers > -1 && ($listsIds = $customer->getAllListsIds())) {
                $criteria->addInCondition('t.list_id', $listsIds);
                $totalSubscribersCount = ListSubscriber::model()->count($criteria);
                if ($totalSubscribersCount >= $maxSubscribers) {
                    return $this->renderJson(array(
                        'status'    => 'error',
                        'error'     => Yii::t('lists', 'The maximum number of allowed subscribers has been reached.')
                    ), 409);
                }    
            }
            
            if ($maxSubscribersPerList > -1) {
                $criteria->compare('t.list_id', (int)$list->list_id);
                $listSubscribersCount = ListSubscriber::model()->count($criteria);
                if ($listSubscribersCount >= $maxSubscribersPerList) {
                    return $this->renderJson(array(
                        'status'    => 'error',
                        'error'     => Yii::t('lists', 'The maximum number of allowed subscribers for this list has been reached.')
                    ), 409);
                }
            }
        }
        
        $subscriber = ListSubscriber::model()->findByAttributes(array(
            'list_id'   => (int)$list->list_id,
            'email'     => $email,
        ));
        
        if (!empty($subscriber)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscriber already exists in this list.')
            ), 409);
        }
        
        $subscriber = new ListSubscriber();
        $subscriber->list_id    = $list->list_id;
        $subscriber->email      = $email;
        $subscriber->source     = ListSubscriber::SOURCE_API;
        $subscriber->ip_address = $request->getServer('HTTP_MW_REMOTE_ADDR', $request->getServer('REMOTE_ADDR'));
        
        if ($list->opt_in == Lists::OPT_IN_SINGLE) {
            $subscriber->status = ListSubscriber::STATUS_CONFIRMED;
        } else {
            $subscriber->status = ListSubscriber::STATUS_UNCONFIRMED;
        }

        $blacklisted = $subscriber->getIsBlacklisted();
        if (!empty($blacklisted)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'This email address is blacklisted.')
            ), 409);
        }
        
        $fields = ListField::model()->findAllByAttributes(array(
            'list_id' => $list->list_id,
        ));
        
        if (empty($fields)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscribers list does not have any custom field defined.')
            ), 404);
        }
        
        $errors = array();
        foreach ($fields as $field) {
            $value = $request->getPost($field->tag);
            if ($field->required == ListField::TEXT_YES && empty($value)) {
                $errors[$field->tag] = Yii::t('api', 'The field {field} is required by the list but it has not been provided!', array(
                    '{field}' => $field->tag
                ));
            }
            
            // note here to remove when we will support multiple values
            if (!empty($value) && !is_string($value)) {
                $errors[$field->tag] = Yii::t('api', 'The field {field} contains multiple values, which is not supported right now!', array(
                    '{field}' => $field->tag
                ));
            }
        }
        
        if (!empty($errors)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => $errors,
            ), 422);
        }

        // since 1.3.5.7
        $details = (array)$request->getPut('details', array());
        if (!empty($details)) {
            if (!empty($details['status']) && in_array($details['status'], array_keys($subscriber->getStatusesList()))) {
                $subscriber->status = $details['status'];
            }
            if (!empty($details['ip_address']) && filter_var($details['ip_address'], FILTER_VALIDATE_IP)) {
                $subscriber->ip_address = $details['ip_address'];
            }
            if (!empty($details['source']) && in_array($details['source'], array_keys($subscriber->getSourcesList()))) {
                $subscriber->source = $details['source'];
            }
        }

        if (!$subscriber->save()) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'Unable to save the subscriber!'),
            ), 422);
        }
        
        $substr = CommonHelper::functionExists('mb_substr') ? 'mb_substr' : 'substr';
        
        foreach ($fields as $field) {
            $valueModel = new ListFieldValue();
            $valueModel->field_id = $field->field_id;
            $valueModel->subscriber_id = $subscriber->subscriber_id;
            $valueModel->value = $substr($request->getPost($field->tag), 0, 255);
            $valueModel->save();
        }
        
        if ($list->opt_in == Lists::OPT_IN_DOUBLE) {
            $this->sendSubscribeConfirmationEmail($list, $subscriber);
        } else {
            // since 1.3.5 - this should be expanded in future
            $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_SUBSCRIBE);
            
            // since 1.3.5.4 - send the welcome email
            $this->sendSubscribeWelcomeEmail($list, $subscriber);
        }

        /*
         * FullContact Integration
         *
         */
        if(!empty($subscriber))
        {
            $url
                = 'http://deva.emailone.net/customer/full_contact/create/'.$list->list_uid.'/subscriber/'.$subscriber->subscriber_uid;
            mail('smallfriinc@gmail.com','URL',print_r($url,true));


            $params = [];
            Yii::app()->curl->get($url, $params);
        }
        
        return $this->renderJson(array(
            'status' => 'success',
        ), 201);
    }
    
    /**
     * Handles the updating of an list subscriber.
     * 
     * @param $list_uid The email list unique id.
     * @param $subscriber_uid The subscriber unique id
     */
    public function actionUpdate($list_uid, $subscriber_uid)
    {
        $request = Yii::app()->request;
        
        if (!$request->isPutRequest) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'Only PUT requests allowed for this endpoint.')
            ), 400);
        }
        
        $email = $request->getPut('EMAIL');
        if (empty($email)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'Please provide the subscriber email address.')
            ), 422);
        }
        
        $validator = new CEmailValidator();
        $validator->allowEmpty = false;
        if (Yii::app()->options->get('system.common.dns_email_check', false)) {
            $validator->checkMX     = CommonHelper::functionExists('checkdnsrr');
            $validator->checkPort   = CommonHelper::functionExists('dns_get_record') && CommonHelper::functionExists('fsockopen');    
        }
        
        if (!$validator->validateValue($email)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'Please provide a valid email address.')
            ), 422);
        }
        
        if (!($list = $this->loadListByUid($list_uid))) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscribers list does not exist5.')
            ), 404);
        }
        
        $subscriber = ListSubscriber::model()->findByAttributes(array(
            'subscriber_uid'    => $subscriber_uid,
            'list_id'           => $list->list_id,
        ));

        if (empty($subscriber)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscriber does not exist in this list.')
            ), 409);
        }

        $fields = ListField::model()->findAllByAttributes(array(
            'list_id'   => $list->list_id,
        ));
        
        if (empty($fields)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscribers list does not have any custom field defined.')
            ), 404);
        }
        
        $errors = array();
        foreach ($fields as $field) {
            $value = $request->getPut($field->tag);
            if ($field->required == ListField::TEXT_YES && empty($value)) {
                $errors[$field->tag] = Yii::t('api', 'The field {field} is required by the list but it has not been provided!', array(
                    '{field}' => $field->tag
                ));
            }
            
            // note here to remove when we will support multiple values
            if (!empty($value) && !is_string($value)) {
                $errors[$field->tag] = Yii::t('api', 'The field {field} contains multiple values, which is not supported right now!', array(
                    '{field}' => $field->tag
                ));
            }
        }
        
        if (!empty($errors)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => $errors,
            ), 422);
        }

        $criteria = new CDbCriteria();
        $criteria->condition = 't.list_id = :lid AND t.email = :email AND t.subscriber_id != :sid';
        $criteria->params = array(
            ':lid'      => $list->list_id,
            ':email'    => $email,
            ':sid'      => $subscriber->subscriber_id,
        );
        $duplicate = ListSubscriber::model()->find($criteria);
        if (!empty($duplicate)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'Another subscriber with this email address already exists in this list.')
            ), 409);
        }
        
        $subscriber->email = $email;
        $blacklisted = $subscriber->getIsBlacklisted();
        if (!empty($blacklisted)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'This email address is blacklisted.')
            ), 409);
        }
        
        // since 1.3.5.7
        $details = (array)$request->getPut('details', array());
        if (!empty($details)) {
            if (!empty($details['status']) && in_array($details['status'], array_keys($subscriber->getStatusesList()))) {
                $subscriber->status = $details['status'];
            }
            if (!empty($details['ip_address']) && filter_var($details['ip_address'], FILTER_VALIDATE_IP)) {
                $subscriber->ip_address = $details['ip_address'];
            }
            if (!empty($details['source']) && in_array($details['source'], array_keys($subscriber->getSourcesList()))) {
                $subscriber->source = $details['source'];
            }
        }
        
        if (!$subscriber->save()) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'Unable to save the subscriber!'),
            ), 422);
        }

        $substr = CommonHelper::functionExists('mb_substr') ? 'mb_substr' : 'substr';
        
        foreach ($fields as $field) {

            $valueModel = ListFieldValue::model()->findByAttributes(array(
                'field_id'        => $field->field_id,
                'subscriber_id'    => $subscriber->subscriber_id,
            ));
            
            if (empty($valueModel)) {
                $valueModel = new ListFieldValue();
                $valueModel->field_id = $field->field_id;
                $valueModel->subscriber_id = $subscriber->subscriber_id;
            }

            $valueModel->value = $substr($request->getPut($field->tag), 0, 255);
            $valueModel->save();
        }
        
        if ($logAction = Yii::app()->user->getModel()->asa('logAction')) {
            $logAction->subscriberUpdated($subscriber);    
        }
                    
        return $this->renderJson(array(
            'status'    => 'success',
        ), 200);
    }
    
    /**
     * Handles unsubscription of an existing email list subscriber.
     * 
     * @param $list_uid The email list unique id.
     * @param $subscriber_uid The subscriber unique id.
     */
    public function actionUnsubscribe($list_uid, $subscriber_uid)
    {
        $request = Yii::app()->request;
        
        if (!$request->isPutRequest) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'Only PUT requests allowed for this endpoint.')
            ), 400);
        }

        if (!($list = $this->loadListByUid($list_uid))) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscribers list does not exist6.')
            ), 404);
        }
        
        $subscriber = ListSubscriber::model()->findByAttributes(array(
            'subscriber_uid'    => $subscriber_uid,
            'list_id'           => $list->list_id,
        ));
        
        if (empty($subscriber)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscriber does not exist in this list.')
            ), 404);
        }
        
        $subscriber->status = ListSubscriber::STATUS_UNSUBSCRIBED;
        $saved = $subscriber->save(false);
        
        // since 1.3.5 - this should be expanded in future
        if ($saved) {
            $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_UNSUBSCRIBE);
        }
        
        if ($logAction = Yii::app()->user->getModel()->asa('logAction')) {
            $logAction->subscriberUnsubscribed($subscriber);    
        }
        
        return $this->renderJson(array(
            'status'    => 'success',
        ), 200);
    }
    
    /**
     * Handles deleting of an existing email list subscriber.
     * 
     * @param $list_uid The email list unique id.
     * @param $subscriber_uid The subscriber unique id.
     */
    public function actionDelete($list_uid, $subscriber_uid)
    {
        $request = Yii::app()->request;
        
        if (!$request->isDeleteRequest) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'Only DELETE requests allowed for this endpoint.')
            ), 400);
        }
        
        if (!($list = $this->loadListByUid($list_uid))) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscribers list does not exist7.')
            ), 404);
        }
        
        $subscriber = ListSubscriber::model()->findByAttributes(array(
            'subscriber_uid'    => $subscriber_uid,
            'list_id'           => $list->list_id,
        ));
        
        if (empty($subscriber)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscriber does not exist in this list.')
            ), 404);
        }
        
        $subscriber->delete();
        
        if ($logAction = Yii::app()->user->getModel()->asa('logAction')) {
            $logAction->subscriberDeleted($subscriber);    
        }
        
        return $this->renderJson(array(
            'status'    => 'success',
        ), 200);
    }
    
    /**
     * Search given list for a subscriber by the given email address
     * 
     * @param $list_uid The email list unique id.
     */
    public function actionSearch_by_email($list_uid)
    {
        $request = Yii::app()->request;
        
        $email = $request->getQuery('EMAIL');
        if (empty($email)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'Please provide the subscriber email address.')
            ), 422);
        }
        
        $validator = new CEmailValidator();
        $validator->allowEmpty = false;
        if (!$validator->validateValue($email)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'Please provide a valid email address.')
            ), 422);
        }
        
        if (!($list = $this->loadListByUid($list_uid))) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscribers list does not exist8.')
            ), 404);
        }
        
        $subscriber = ListSubscriber::model()->findByAttributes(array(
            'list_id'   => $list->list_id,
            'email'     => $email,
        ));
        
        if (empty($subscriber)) {
            return $this->renderJson(array(
                'status'    => 'error',
                'error'     => Yii::t('api', 'The subscriber does not exist in this list.')
            ), 404);
        }
        
        return $this->renderJson(array(
            'status'    => 'success',
            'data'      => $subscriber->getAttributes(array('subscriber_uid', 'status')),
        ), 200);
    }
    
    public function loadListByUid($list_uid)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('list_uid', $list_uid);
        $criteria->compare('customer_id', (int)Yii::app()->user->getId());
        $criteria->addNotInCondition('status', array(Lists::STATUS_PENDING_DELETE));
        return Lists::model()->find($criteria);
    }
    
    /**
     * It will generate the timestamp that will be used to generate the ETAG for GET requests.
     */
    public function generateLastModified()
    {
        static $lastModified;
        
        if ($lastModified !== null) {
            return $lastModified;
        }
        
        $request = Yii::app()->request;
        $row = array();
        
        if ($this->action->id == 'index') {

            $listUid    = $request->getQuery('list_uid');
            $perPage    = (int)$request->getQuery('per_page', 10);
            $page       = (int)$request->getQuery('page', 1);
            
            $maxPerPage = 50;
            $minPerPage = 10;
            
            if ($perPage < $minPerPage) {
                $perPage = $minPerPage;
            }
            
            if ($perPage > $maxPerPage) {
                $perPage = $maxPerPage;
            }
            
            if ($page < 1) {
                $page = 1;
            }

            $list = Lists::model()->findByAttributes(array(
                'list_uid'      => $listUid,
                'customer_id'   => (int)Yii::app()->user->getId(),
            ));
            
            if (empty($list)) {
                return $lastModified = parent::generateLastModified();
            }
    
            $limit  = $perPage;
            $offset = ($page - 1) * $perPage;

            $sql = '
                SELECT AVG(t.last_updated) as `timestamp`
                FROM (
                     SELECT `a`.`list_id`, `a`.`status`, UNIX_TIMESTAMP(`a`.`last_updated`) as `last_updated`
                     FROM `{{list_subscriber}}` `a` 
                     WHERE `a`.`list_id` = :lid 
                     ORDER BY a.`subscriber_id` DESC 
                     LIMIT :l OFFSET :o
                ) AS t 
                WHERE `t`.`list_id` = :lid
            ';
            
            $command = Yii::app()->getDb()->createCommand($sql);
            $command->bindValue(':lid', (int)$list->list_id, PDO::PARAM_INT);
            $command->bindValue(':l', (int)$limit, PDO::PARAM_INT);
            $command->bindValue(':o', (int)$offset, PDO::PARAM_INT);
            
            $row = $command->queryRow();

        } elseif ($this->action->id == 'view') {
            
            $listUid        = $request->getQuery('list_uid');
            $subscriberUid  = $request->getQuery('subscriber_uid');
            
            $list = Lists::model()->findByAttributes(array(
                'list_uid'    => $listUid,
                'customer_id' => (int)Yii::app()->user->getId(),
            ));
            
            if (empty($list)) {
                return $lastModified = parent::generateLastModified();
            }
            
            $subscriber = ListSubscriber::model()->findByAttributes(array(
                'subscriber_uid' => $subscriberUid,
                'list_id'        => $list->list_id,
            ));

            if (!empty($subscriber)) {
                $row['timestamp'] = strtotime($subscriber->last_updated);
            }
        }
        
        if (isset($row['timestamp'])) {
            $timestamp = round($row['timestamp']);
            if (preg_match('/\.(\d+)/', $row['timestamp'], $matches)) {
                $timestamp += (int)$matches[1];
            }
            return $lastModified = $timestamp;
        }
        
        return $lastModified = parent::generateLastModified();
    }
    
    protected function sendSubscribeConfirmationEmail($list, $subscriber)
    {
        if (!($server = DeliveryServer::pickServer(0, $list))) {
            return false;
        }

        $pageType = ListPageType::model()->findBySlug('subscribe-confirm-email');
        
        if (empty($pageType)) {
            return false;
        }
        
        $page = ListPage::model()->findByAttributes(array(
            'list_id'   => $list->list_id, 
            'type_id'   => $pageType->type_id
        ));
        
        $content = !empty($page->content) ? $page->content : $pageType->content;
        $options = Yii::app()->options;
        
        $subscribeUrl = $options->get('system.urls.frontend_absolute_url');
        $subscribeUrl .= 'lists/' . $list->list_uid . '/confirm-subscribe/' . $subscriber->subscriber_uid;
        
        $searchReplace = array(
            '[LIST_NAME]'       => $list->display_name,
            '[COMPANY_NAME]'    => !empty($list->company) ? $list->company->name : null,
            '[SUBSCRIBE_URL]'   => $subscribeUrl,
            '[CURRENT_YEAR]'    => date('Y'),
        );

        $content = str_replace(array_keys($searchReplace), array_values($searchReplace), $content);

        $params = array(
            'to'        => $subscriber->email,
            'fromName'  => $list->default->from_name,
            'subject'   => Yii::t('list_subscribers', 'Please confirm your subscription'),
            'body'      => $content,
        );
        
        $sent = false;
        for ($i = 0; $i < 3; ++$i) {
            if ($sent = $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_LIST)->setDeliveryObject($list)->sendEmail($params)) {
                break;
            }
            $server = DeliveryServer::pickServer($server->server_id, $list);    
        }

        return $sent;
    }
    
    protected function sendSubscribeWelcomeEmail($list, $subscriber)
    {
        if ($list->welcome_email != Lists::TEXT_YES) {
            return;
        }
        
        $pageType = ListPageType::model()->findBySlug('welcome-email');
        if (!($server = DeliveryServer::pickServer(0, $list))) {
            $pageType = null;
        }
        
        if (empty($pageType)) {
            return;
        }
        
        $page = ListPage::model()->findByAttributes(array(
            'list_id' => $list->list_id, 
            'type_id' => $pageType->type_id
        ));
        
        $options          = Yii::app()->options;
        $_content         = !empty($page->content) ? $page->content : $pageType->content;
        $updateProfileUrl = $options->get('system.urls.frontend_absolute_url') . 'lists/' . $list->list_uid . '/update-profile/' . $subscriber->subscriber_uid;
        $unsubscribeUrl   = $options->get('system.urls.frontend_absolute_url') . 'lists/' . $list->list_uid . '/unsubscribe/' . $subscriber->subscriber_uid;
        $searchReplace    = array(
            '[LIST_NAME]'           => $list->display_name,
            '[COMPANY_NAME]'        => !empty($list->company) ? $list->company->name : null,
            '[UPDATE_PROFILE_URL]'  => $updateProfileUrl,
            '[UNSUBSCRIBE_URL]'     => $unsubscribeUrl,
            '[COMPANY_FULL_ADDRESS]'=> !empty($list->company) ? nl2br($list->company->getFormattedAddress()) : null,
            '[CURRENT_YEAR]'        => date('Y'),
        );
        $_content = str_replace(array_keys($searchReplace), array_values($searchReplace), $_content);
        
        $params = array(
            'to'        => $subscriber->email,
            'fromName'  => $list->default->from_name,
            'subject'   => Yii::t('list_subscribers', 'Thank you for your subscription!'),
            'body'      => $_content,
        );
        
        for ($i = 0; $i < 3; ++$i) {
            if ($server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_LIST)->setDeliveryObject($list)->sendEmail($params)) {
                break;
            }
            $server = DeliveryServer::pickServer($server->server_id, $list);    
        }
    }
}
