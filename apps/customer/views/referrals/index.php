<?php defined('MW_PATH')||exit('No direct script access allowed');

/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->data}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->renderContent} to false
 * in order to stop rendering the default content.
 * @since 1.3.3.1
 */
$hooks->doAction('before_view_file_content',$viewCollection = new CAttributeCollection(array(
    'controller' => $this,
    'renderContent' => true,
)));

// and render if allowed
if($viewCollection->renderContent)
    $form = $this->beginWidget('CActiveForm');

{ ?>
    <div class="box box-primary">
        <div class="box-header">
            <div class="pull-left">
                <h3 class="box-title">
                    <span class="glyphicon glyphicon-star"></span> <?php echo $pageHeading; ?>
                </h3>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">
            <?php
                            /**
                             * This hook gives a chance to prepend content before the active form fields.
                             * Please note that from inside the action callback you can access all the controller view variables
                             * via {@CAttributeCollection $collection->controller->data}
                             * @since 1.3.3.1
                             */
                            $hooks->doAction('before_active_form_fields', new CAttributeCollection(array(
                                'controller'    => $this,
                                'form'          => $form
                            )));
                            ?>
            <div class="callout callout-info">
                Request approval to promote EmailONE here:
                <a href="http://www.jvzoo.com/affiliates/info/181475" style="color:yellow" target="_blank">JVZoo</a>
            </div>
            <div class="form-group col-lg-6">
                <label for="Campaign_name" class="required">JVZoo Affiliate URL <span class="required">*</span></label>
                <input data-title="Referral URL" data-container="body" data-toggle="popover" data-content="This is the URL given to you by JVZoo to promote EmailONE." class="form-control has-help-text" placeholder="" data-placement="top" name="referral_url" id="referral_url" type="text" maxlength="255" data-original-title="" title="" value="<?php echo $referral_url;?>">
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-footer">
            <div class="pull-right">
                <button type="submit" class="btn btn-primary btn-submit" data-loading-text="Please wait, processing...">Save changes</button>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
    </div>
    <?php
          $this->endWidget();

      /**
       * This hook gives a chance to append content after the active form.
       * Please note that from inside the action callback you can access all the controller view variables
       * via {@CAttributeCollection $collection->controller->data}
       * @since 1.3.3.1
       */
      $hooks->doAction('after_active_form', new CAttributeCollection(array(
          'controller'      => $this,
          'renderedForm'    => $collection->renderForm,
      )));
}
/**
 * This hook gives a chance to append content after the view file default content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->data}
 * @since 1.3.3.1
 */
$hooks->doAction('after_view_file_content',new CAttributeCollection(array(
    'controller' => $this,
    'renderedContent' => $viewCollection->renderContent,
)));