<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * This file is part of the MailWizz EMA application.
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 */
 
?>


<div class="price-plan-payment">
    <div class="row">
        <div class="col-xs-12">
            <h2 class="page-header">
                <i class="fa fa-credit-card"></i> <?php echo $order->plan->name;?>
            </h2>                            
        </div>
    </div>

    <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
            <?php echo Yii::t('app', 'From');?>
            <address>
                <?php echo $order->htmlPaymentFrom;?>
            </address>
        </div>
        <div class="col-sm-4 invoice-col">
            <?php echo Yii::t('app', 'To');?>
            <address>
                <?php echo $order->htmlPaymentTo;?>
            </address>
        </div>
        <div class="col-sm-4 invoice-col"></div>
    </div>
    
    <hr />
    
    <div class="row">
        <div class="col-xs-12 table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php echo Yii::t('price_plans', 'You have selected the "{planName}" pricing plan.', array('{planName}' => $order->plan->name));?></th>
                    </tr>                                    
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $order->plan->description;?></td>
                    </tr>
                </tbody>
            </table>                            
        </div>
    </div>
    
    <hr />
    
    <div class="row">
        <div class="col-xs-12">
            <div class="form-group">
                <?php echo CHtml::label($note->getAttributeLabel('note'), 'note');?>
                <?php echo CHtml::activeTextArea($note, 'note', $note->getHtmlOptions('note'));?> 
            </div>                       
        </div>
    </div>
    
    <hr />
    
    <div class="row">
        <div class="col-xs-4">
            <p class="lead"><?php echo Yii::t('orders', 'Payment method')?>:</p>
            <?php echo $paymentHandler->renderPaymentView();?>
        </div>
        <div class="col-xs-4">
            <?php 
            echo CHtml::form(array('price_plans/promo'), 'post');
            echo CHtml::hiddenField('plan_uid', $order->plan->plan_uid);
            echo CHtml::hiddenField('payment_gateway', $paymentGateway);
            ?>
            <p class="lead"><?php echo Yii::t('orders', 'Promo code')?>:</p>
            <p class="text-muted well well-sm no-shadow" style="margin-top: 10px;">
                <input type="text" name="promo_code" id="promo_code" value="<?php echo $promoCode;?>" class="form-control" placeholder="<?php echo Yii::t('orders', 'Enter your promo code here');?>"/>
            </p>
            <button class="btn btn-success btn-submit pull-right" data-loading-text="<?php echo Yii::t('app', 'Please wait, processing...');?>"> <?php echo Yii::t('price_plans', 'Apply code')?></button>
            <?php echo CHtml::endForm();?>
        </div>
        <div class="col-xs-4">
            <p class="lead"><?php echo Yii::t('orders', 'Amount due')?>:</p>
            <div class="table-responsive">
                <table class="table">
                    <tr>
                        <th style="width:50%"><?php echo Yii::t('orders', 'Subtotal')?>:</th>
                        <td><?php echo $order->formattedSubtotal;?></td>
                    </tr>
                    <tr>
                        <th><?php echo Yii::t('orders', 'Tax')?>:</th>
                        <td><?php echo $order->formattedTaxValue;?></td>
                    </tr>
                    <tr>
                        <th><?php echo Yii::t('orders', 'Discount')?>:</th>
                        <td><?php echo $order->formattedDiscount;?></td>
                    </tr>
                    <tr>
                        <th><?php echo Yii::t('orders', 'Total')?>:</th>
                        <td><?php echo $order->formattedTotal;?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <hr />
    
    <div class="row no-print">
        <div class="col-xs-12">
            <div class="pull-right">
                <a href="<?php echo $this->createUrl('price_plans/index');?>" class="btn btn-primary"><?php echo Yii::t('app', 'Cancel');?></a>    
            </div>
        </div>
    </div>
</div>
