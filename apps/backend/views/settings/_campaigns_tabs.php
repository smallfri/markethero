<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.4
 */

?>
<ul class="nav nav-tabs" style="border-bottom: 0px;">
    <li class="<?php echo $this->getAction()->getId() == 'campaign_attachments' ? 'active' : 'inactive';?>">
        <a href="<?php echo $this->createUrl('settings/campaign_attachments')?>">
            <?php echo Yii::t('settings', 'Attachments');?>
        </a>
    </li>
    <li class="<?php echo $this->getAction()->getId() == 'campaign_template_tags' ? 'active' : 'inactive';?>">
        <a href="<?php echo $this->createUrl('settings/campaign_template_tags')?>">
            <?php echo Yii::t('settings', 'Template tags');?>
        </a>
    </li>
    <li class="<?php echo $this->getAction()->getId() == 'campaign_exclude_ips_from_tracking' ? 'active' : 'inactive';?>">
        <a href="<?php echo $this->createUrl('settings/campaign_exclude_ips_from_tracking')?>">
            <?php echo Yii::t('settings', 'Exclude IPs from tracking');?>
        </a>
    </li>
    <li class="<?php echo $this->getAction()->getId() == 'campaign_blacklist_words' ? 'active' : 'inactive';?>">
        <a href="<?php echo $this->createUrl('settings/campaign_blacklist_words')?>">
            <?php echo Yii::t('settings', 'Blacklist words');?>
        </a>
    </li>
    <li class="<?php echo $this->getAction()->getId() == 'campaign_misc' ? 'active' : 'inactive';?>">
        <a href="<?php echo $this->createUrl('settings/campaign_misc')?>">
            <?php echo Yii::t('settings', 'Miscellaneous');?>
        </a>
    </li>
</ul>
