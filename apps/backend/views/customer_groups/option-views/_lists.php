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
 <div class="col-lg-12 row-group-category">
    <div class="box box-primary">
        <div class="box-body">
            <div class="clearfix"><!-- --></div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'can_import_subscribers');?>
                <?php echo $form->dropDownList($model, 'can_import_subscribers', $model->getYesNoOptions(), $model->getHtmlOptions('can_import_subscribers')); ?>
                <?php echo $form->error($model, 'can_import_subscribers');?>
            </div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'can_export_subscribers');?>
                <?php echo $form->dropDownList($model, 'can_export_subscribers', $model->getYesNoOptions(), $model->getHtmlOptions('can_export_subscribers')); ?>
                <?php echo $form->error($model, 'can_export_subscribers');?>
            </div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'can_copy_subscribers');?>
                <?php echo $form->dropDownList($model, 'can_copy_subscribers', $model->getYesNoOptions(), $model->getHtmlOptions('can_copy_subscribers')); ?>
                <?php echo $form->error($model, 'can_copy_subscribers');?>
            </div>
            <div class="clearfix"><!-- --></div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'max_lists');?>
                <?php echo $form->textField($model, 'max_lists', $model->getHtmlOptions('max_lists')); ?>
                <?php echo $form->error($model, 'max_lists');?>
            </div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'max_subscribers');?>
                <?php echo $form->textField($model, 'max_subscribers', $model->getHtmlOptions('max_subscribers')); ?>
                <?php echo $form->error($model, 'max_subscribers');?>
            </div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'max_subscribers_per_list');?>
                <?php echo $form->textField($model, 'max_subscribers_per_list', $model->getHtmlOptions('max_subscribers_per_list')); ?>
                <?php echo $form->error($model, 'max_subscribers_per_list');?>
            </div>
            <div class="clearfix"><!-- --></div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'copy_subscribers_memory_limit');?>
                <?php echo $form->dropDownList($model, 'copy_subscribers_memory_limit', $model->getMemoryLimitOptions(), $model->getHtmlOptions('copy_subscribers_memory_limit')); ?>
                <?php echo $form->error($model, 'copy_subscribers_memory_limit');?>
            </div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'copy_subscribers_at_once');?>
                <?php echo $form->textField($model, 'copy_subscribers_at_once', $model->getHtmlOptions('copy_subscribers_at_once')); ?>
                <?php echo $form->error($model, 'copy_subscribers_at_once');?>
            </div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'can_delete_own_lists');?>
                <?php echo $form->dropDownList($model, 'can_delete_own_lists', $model->getYesNoOptions(), $model->getHtmlOptions('can_delete_own_lists')); ?>
                <?php echo $form->error($model, 'can_delete_own_lists');?>
            </div>
            <div class="clearfix"><!-- --></div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'can_delete_own_subscribers');?>
                <?php echo $form->dropDownList($model, 'can_delete_own_subscribers', $model->getYesNoOptions(), $model->getHtmlOptions('can_delete_own_subscribers')); ?>
                <?php echo $form->error($model, 'can_delete_own_subscribers');?>
            </div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'can_segment_lists');?>
                <?php echo $form->dropDownList($model, 'can_segment_lists', $model->getYesNoOptions(), $model->getHtmlOptions('can_segment_lists')); ?>
                <?php echo $form->error($model, 'can_segment_lists');?>
            </div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'max_segment_conditions');?>
                <?php echo $form->textField($model, 'max_segment_conditions', $model->getHtmlOptions('max_segment_conditions')); ?>
                <?php echo $form->error($model, 'max_segment_conditions');?>
            </div>
            <div class="clearfix"><!-- --></div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'max_segment_wait_timeout');?>
                <?php echo $form->textField($model, 'max_segment_wait_timeout', $model->getHtmlOptions('max_segment_wait_timeout')); ?>
                <?php echo $form->error($model, 'max_segment_wait_timeout');?>
            </div>
            <div class="form-group col-lg-4">
                <?php echo $form->labelEx($model, 'can_mark_blacklisted_as_confirmed');?>
                <?php echo $form->dropDownList($model, 'can_mark_blacklisted_as_confirmed', $model->getYesNoOptions(), $model->getHtmlOptions('can_mark_blacklisted_as_confirmed')); ?>
                <?php echo $form->error($model, 'can_mark_blacklisted_as_confirmed');?>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
</div>