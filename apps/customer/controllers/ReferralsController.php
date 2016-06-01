<?php defined('MW_PATH')||exit('No direct script access allowed');

/**
 * CampaignsController
 *
 * Handles the actions for campaigns related tasks
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */
class ReferralsController extends Controller
{

    public function init()
    {

        $this->getData('pageStyles')
            ->add(array('src' => AssetsUrl::js('datetimepicker/css/bootstrap-datetimepicker.min.css')));
        $this->getData('pageScripts')
            ->add(array('src' => AssetsUrl::js('datetimepicker/js/bootstrap-datetimepicker.min.js')));

        $languageCode = LanguageHelper::getAppLanguageCode();
        if(Yii::app()->language!=Yii::app()->sourceLanguage&&is_file(AssetsPath::js($languageFile
                = 'datetimepicker/js/locales/bootstrap-datetimepicker.'.$languageCode.'.js'))
        )
        {
            $this->getData('pageScripts')->add(array('src' => AssetsUrl::js($languageFile)));
        }

        if(MW_COMPOSER_SUPPORT)
        {
            $this->getData('pageStyles')
                ->add(array('src' => Yii::app()->apps->getBaseUrl('assets/js/jqcron/jqCron.css')));
            $this->getData('pageScripts')
                ->add(array('src' => Yii::app()->apps->getBaseUrl('assets/js/jqcron/jqCron.js')));
            if(is_file(Yii::getPathOfAlias('root.assets.js').'/jqcron/jqCron.'.$languageCode.'.js'))
            {
                $this->getData('pageScripts')
                    ->add(array('src' => Yii::app()->apps->getBaseUrl('assets/js/jqcron/jqCron.'.$languageCode.'.js')));
                $this->setData('jqCronLanguage',$languageCode);
            }
            else
            {
                $this->getData('pageScripts')
                    ->add(array('src' => Yii::app()->apps->getBaseUrl('assets/js/jqcron/jqCron.en.js')));
                $this->setData('jqCronLanguage','en');
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
        ),parent::filters());
    }

    /**
     * List available campaigns
     */
    public function actionIndex()
    {

        $customer_id = (int)Yii::app()->customer->getId();

        $referral_url = null;

        $criteria = new CDbCriteria();
        $criteria->compare('customer_id',$customer_id);
        $Referral = Referral::model()->find($criteria);

        $this->setData(array(
            'pageMetaTitle' => $this->data->pageMetaTitle.' | '.Yii::t('campaigns','Your Referral Settings'),
            'pageHeading' => Yii::t('campaigns','Your Referral Settings'),
            'pageBreadcrumbs' => array(
                Yii::t('app','View all')
            )
        ));

        if(isset($_POST['referral_url']))
        {
            $notify = Yii::app()->notify;

            if(empty($Referral))
            {
                $Referral = new Referral();

                $Referral->jvz_referral_url = $_POST['referral_url'];
                $Referral->customer_id = $customer_id;

            }
            else
            {
                $Referral->jvz_referral_url = $_POST['referral_url'];
                $Referral->customer_id = $customer_id;
            }


            if(!$Referral->save())
            {
//                var_dump($Referral->getErrors());
                $notify->addError(Yii::t('app','Your form has a few errors, please fix them and try again!'));
            }
            else
            {
                $notify->addSuccess(Yii::t('app','Your form has been successfully saved!'));
            }

            Yii::app()->hooks->doAction('controller_action_save_data',$collection = new CAttributeCollection(array(
                'controller' => $this,
                'success' => $notify->hasSuccess
            )));

            if($collection->success)
            {
                $this->redirect(array('referrals/index'));
            }
        }

        $this->render('index',compact('campaign'));
    }

}