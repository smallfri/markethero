<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * This file is part of the MailWizz EMA application.
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.5.4
 */

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->data}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->renderContent} to false 
 * in order to stop rendering the default content.
 * @since 1.3.3.1
 */
$hooks->doAction('before_view_file_content', $viewCollection = new CAttributeCollection(array(
    'controller'    => $this,
    'renderContent' => true,
)));

// and render if allowed
if ($viewCollection->renderContent) {
    /**
     * This hook gives a chance to prepend content before the active form or to replace the default active form entirely.
     * Please note that from inside the action callback you can access all the controller view variables 
     * via {@CAttributeCollection $collection->controller->data}
     * In case the form is replaced, make sure to set {@CAttributeCollection $collection->renderForm} to false 
     * in order to stop rendering the default content.
     * @since 1.3.3.1
     */
    $hooks->doAction('before_active_form', $collection = new CAttributeCollection(array(
        'controller'    => $this,
        'renderForm'    => true,
    )));
    
    // and render if allowed
    if ($collection->renderForm) {
        $form = $this->beginWidget('CActiveForm'); 
        ?>
        <div class="box box-primary">
            <div class="box-header">
                <h3 class="box-title"><?php echo Yii::t('settings', 'Customization')?></h3>
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
                <div class="clearfix"><!-- --></div>
                
                <div class="form-group col-lg-4">
                    <?php echo $form->labelEx($model, 'backend_logo_text');?>
                    <?php echo $form->textField($model, 'backend_logo_text', $model->getHtmlOptions('backend_logo_text')); ?>
                    <?php echo $form->error($model, 'backend_logo_text');?>
                </div>
                <div class="form-group col-lg-4">
                    <?php echo $form->labelEx($model, 'customer_logo_text');?>
                    <?php echo $form->textField($model, 'customer_logo_text', $model->getHtmlOptions('customer_logo_text')); ?>
                    <?php echo $form->error($model, 'customer_logo_text');?>
                </div>
                <div class="form-group col-lg-4">
                    <?php echo $form->labelEx($model, 'frontend_logo_text');?>
                    <?php echo $form->textField($model, 'frontend_logo_text', $model->getHtmlOptions('frontend_logo_text')); ?>
                    <?php echo $form->error($model, 'frontend_logo_text');?>
                </div>
                <div class="clearfix"><!-- --></div>
                <div class="form-group col-lg-4">
                    <?php echo $form->labelEx($model, 'backend_skin');?>
                    <?php echo $form->dropDownList($model, 'backend_skin', $model->getAppSkins('backend'), $model->getHtmlOptions('backend_skin')); ?>
                    <?php echo $form->error($model, 'backend_skin');?>
                </div>
                <div class="form-group col-lg-4">
                    <?php echo $form->labelEx($model, 'customer_skin');?>
                    <?php echo $form->dropDownList($model, 'customer_skin', $model->getAppSkins('customer'), $model->getHtmlOptions('customer_skin')); ?>
                    <?php echo $form->error($model, 'customer_skin');?>
                </div>
                <div class="form-group col-lg-4">
                    <?php echo $form->labelEx($model, 'frontend_skin');?>
                    <?php echo $form->dropDownList($model, 'frontend_skin', $model->getAppSkins('frontend'), $model->getHtmlOptions('frontend_skin')); ?>
                    <?php echo $form->error($model, 'frontend_skin');?>
                </div>
                <div class="clearfix"><!-- --></div>
                <?php 
                /**
                 * This hook gives a chance to append content after the active form fields.
                 * Please note that from inside the action callback you can access all the controller view variables 
                 * via {@CAttributeCollection $collection->controller->data}
                 * @since 1.3.3.1
                 */
                $hooks->doAction('after_active_form_fields', new CAttributeCollection(array(
                    'controller'    => $this,
                    'form'          => $form    
                )));
                ?>
                <div class="clearfix"><!-- --></div>
            </div>
            <div class="box-footer">
                <div class="pull-right">
                    <button type="submit" class="btn btn-primary btn-submit" data-loading-text="<?php echo Yii::t('app', 'Please wait, processing...');?>"><?php echo Yii::t('app', 'Save changes');?></button>
                </div>
                <div class="clearfix"><!-- --></div>
            </div>
        </div>
        <?php 
        $this->endWidget(); 
    }
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
$hooks->doAction('after_view_file_content', new CAttributeCollection(array(
    'controller'        => $this,
    'renderedContent'   => $viewCollection->renderContent,
)));