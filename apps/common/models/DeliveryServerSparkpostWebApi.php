<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * DeliveryServerSparkpostWebApi
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.5.6
 *
 */

class DeliveryServerSparkpostWebApi extends DeliveryServer
{
    protected $serverType = 'sparkpost-web-api';

    protected $_initStatus;

    protected $_preCheckError;

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        $rules = array(
            array('password', 'required'),
            array('password', 'length', 'max' => 255),
        );
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        $labels = array(
            'password'   => Yii::t('servers', 'Api key'),
        );
        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    public function attributeHelpTexts()
    {
        $texts = array(
            'password' => Yii::t('servers', 'One of your sparkpost api keys.'),
        );

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    public function attributePlaceholders()
    {
        $placeholders = array(
            'password'  => Yii::t('servers', 'Api key'),
        );

        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServer the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function sendEmail(array $params = array())
    {
        $params = (array)Yii::app()->hooks->applyFilters('delivery_server_before_send_email', $this->getParamsArray($params), $this);

        if (!isset($params['from'], $params['to'], $params['subject'], $params['body'])) {
            return false;
        }

        list($toEmail, $toName)     = $this->getMailer()->findEmailAndName($params['to']);
        list($fromEmail, $fromName) = $this->getMailer()->findEmailAndName($params['from']);
        $fromEmail = $returnPathEmail = $this->from_email; // force this

        if (!empty($params['fromName'])) {
            $fromName = $params['fromName'];
        }

        $replyToEmail = $replyToName = null;
        if (!empty($params['replyTo'])) {
            list($replyToEmail, $replyToName) = $this->getMailer()->findEmailAndName($params['replyTo']);
        }

        $headerPrefix = Yii::app()->params['email.custom.header.prefix'];
        $headers = array();
        if (!empty($params['headers'])) {
            $headers = $this->parseHeadersIntoKeyValue($params['headers']);
        }
        $headers['X-Sender']   = $fromEmail;
        $headers['X-Receiver'] = $toEmail;
        $headers[$headerPrefix . 'Mailer'] = 'Sparkpost Web API';

        $campaignId = StringHelper::random(40);
        $metaData   = array();
        if (isset($headers[$headerPrefix . 'Campaign-Uid'])) {
            $metaData['campaign_uid'] = $campaignId = $headers[$headerPrefix . 'Campaign-Uid'];
        }
        if (isset($headers[$headerPrefix . 'Subscriber-Uid'])) {
            $metaData['subscriber_uid'] = $headers[$headerPrefix . 'Subscriber-Uid'];
        }

        $sent = false;

        try {
            if (!$this->preCheckWebHook()) {
                throw new Exception($this->_preCheckError);
            }

            $sendParams = array(
                'options' => array(
                    'open_tracking'  => false,
                    'click_tracking' => false,
                ),
                'campaign_id' => $campaignId,
                'return_path' => $returnPathEmail,
                'metadata'    => (object)$metaData,
                'recipients'  => array(
                    array(
                        'return_path' => $returnPathEmail,
                        'address' => array(
                            'email' => $toEmail,
                            'name'  => sprintf('=?%s?B?%s?=', strtolower(Yii::app()->charset), base64_encode($toName)),
                        ),
                        'metadata'  => (object)$metaData,
                    ),
                ),
                'content'   => array(
                    'from' => array(
                        'email' => $fromEmail,
                        'name'  => sprintf('=?%s?B?%s?=', strtolower(Yii::app()->charset), base64_encode($fromName)),
                    ),
                    'subject'  => sprintf('=?%s?B?%s?=', strtolower(Yii::app()->charset), base64_encode($params['subject'])),
                    'reply_to' => !empty($replyToEmail) ? $replyToEmail : $fromEmail,
                    'headers'  => $headers,
                    'text'     => !empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']),
                    'html'     => $params['body'],
                ),
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.sparkpost.com/api/v1/transmissions");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, CJSON::encode($sendParams));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                sprintf("Authorization: %s", $this->password),
                "Accept: application/json"
            ));

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception($error);
            }
            curl_close($ch);

            $response = CJSON::decode($response, false);
            if (!empty($response->errors)) {
                $errors = array();
                foreach ($response->errors as $error) {
                    $errors[] = $error->message . (!empty($error->description) ? ' - ' . $error->description : '');
                }
                throw new Exception(implode("<br />", $errors));
            }

            $this->getMailer()->addLog('OK');
            $sent = array('message_id' => $response->results->id);
        } catch (Exception $e) {
            $this->getMailer()->addLog($e->getMessage());
        }

        if ($sent) {
            $this->logUsage();
        }

        Yii::app()->hooks->doAction('delivery_server_after_send_email', $params, $this, $sent);

        return $sent;
    }

    public function getParamsArray(array $params = array())
    {
        $params['transport'] = self::TRANSPORT_SPARKPOST_WEB_API;
        return parent::getParamsArray($params);
    }

    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->_initStatus = $this->status;
        $this->hostname    = 'web-api.sparkpost.com';
    }

    protected function afterFind()
    {
        $this->_initStatus = $this->status;
        parent::afterFind();
    }

    protected function preCheckWebHook()
    {
        if (MW_IS_CLI || $this->isNewRecord || $this->_initStatus !== self::STATUS_INACTIVE) {
            return true;
        }

        $options = Yii::app()->options;
        $url     = $options->get('system.urls.frontend_absolute_url') . sprintf('dswh/%d', $this->server_id);

        try {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.sparkpost.com/api/v1/webhooks");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                sprintf("Authorization: %s", $this->password),
                "Accept: application/json"
            ));

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception($error);
            }
            curl_close($ch);

            $ids = array();
            $response = CJSON::decode($response, false);
            if (!empty($response->results)) {
                foreach ($response->results as $result) {
                    if ($result->target == $url) {
                        $ids[] = $result->id;
                    }
                }
            }

            foreach ($ids as $id) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api.sparkpost.com/api/v1/webhooks/" . $id);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Content-Type: application/json",
                    sprintf("Authorization: %s", $this->password),
                ));

                curl_exec($ch);
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                    curl_close($ch);
                    throw new Exception($error);
                }
                curl_close($ch);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.sparkpost.com/api/v1/webhooks");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, CJSON::encode(array(
                'name'       => 'MWZWEBHOOKHANDLER',
                'target'     => $url,
                'auth_token' => $this->password,
                'events'     => array('bounce', 'spam_complaint', 'list_unsubscribe', 'link_unsubscribe')
            )));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                sprintf("Authorization: %s", $this->password),
            ));

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception($error);
            }
            curl_close($ch);

            $response = CJSON::decode($response, false);
            if (!empty($response->errors)) {
                $errors = array();
                foreach ($response->errors as $error) {
                    $errors[] = $error->message . (!empty($error->description) ? ' - ' . $error->description : '');
                }
                throw new Exception(implode("<br />", $errors));
            }

        } catch (Exception $e) {
            $this->_preCheckError = $e->getMessage();
        }

        if ($this->_preCheckError) {
            return false;
        }

        return $this->save(false);
    }
}
