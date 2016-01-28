<?php defined('MW_PATH') || exit('No direct script access allowed');?>


Hi <?php echo $customer->getFullName()?>, <br />

Your new account at <?php echo Yii::app()->options->get('system.common.site_name', 'Marketing website');?> is now ready.<br />
You can login into your <a href="<?php echo Yii::app()->options->get('system.urls.customer_absolute_url');?>">customer area</a> by using the following credentials:<br />
Email: <?php echo $customer->email;?><br />
Password: <?php echo $customer->fake_password;?><br />
