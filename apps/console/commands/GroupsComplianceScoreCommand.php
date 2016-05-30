<?php use App\Bounce;

defined('MW_PATH')||exit('No direct script access allowed');

/**
 * BounceHandlerCommand
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */
class GroupsComplianceScoreCommand extends CConsoleCommand
{

    public $verbose = 0;

    public $automate = 0;

    public function init()
    {

        parent::init();

    }

    public function actionIndex()
    {

        /*
         * this cron runs every day to calculate the average compliance scores
         *
         */

        $normal = Yii::app()->db->createCommand()
            ->select('AVG (bounce_report) as bounce_report,
                            AVG (abuse_report) as abuse_report,
                            AVG (unsubscribe_report) as unsubscribe_report,
                            AVG (score) as score')
            ->from('mw_group_email_compliance_score')
            ->queryAll();

        $normal = $normal[0];

        $sql
            = '
              UPDATE mw_group_email_compliance_average
                SET
                bounce_report = '.$normal["bounce_report"].',
                abuse_report = '.$normal["abuse_report"].',
                unsubscribe_report = '.$normal["unsubscribe_report"].',
                score = '.$normal["score"].',
                date_added = "NOW()"
              WHERE id = 1
              ';

        $normal = Yii::app()->db->createCommand($sql)->query();

    }

}