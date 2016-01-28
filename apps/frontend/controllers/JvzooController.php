<?php defined('MW_PATH') || exit('No direct script access allowed');

class JvzooController extends Controller
{
    public function actionIndex()
    {
        $this->redirect(Yii::app()->apps->getAppUrl('customer'));
    }

    public function actionIpn()
    {
 
        if (!$this->isIpnValid($_POST)) {
            Yii::app()->end();
        }

        $request = Yii::app()->request;
        $name      = $request->getPost('ccustname');
        $email     = $request->getPost('ccustemail');
        $prodItem  = $request->getPost('cproditem');
        $prodType  = $request->getPost('cprodtype');
        $transType = $request->getPost('ctransaction');
        $verify    = $request->getPost('cverify');

        $keys = array('name', 'email', 'prodItem', 'prodType', 'transType');
        foreach ($keys as $key) {
            if (empty($$key)) {
                Yii::app()->end();
            }
        }

        $prodItemGroup = array(181475 => 1, 182857 => 2, 183145 => 3);

        $customer = Customer::model()->findByAttributes(array(
            'email' => $email,
        ));

        // create the customer and send the email.
        if (empty($customer) && $transType == 'SALE') {
            $password  = StringHelper::random();
            $nameParts = explode(" ", $name);
            $customer  = new Customer();
            $customer->email         = $email;
            $customer->first_name    = array_shift($nameParts);
            $customer->last_name     = implode(" ", $nameParts);
            $customer->fake_password = $password;
            $customer->status        = Customer::STATUS_ACTIVE;
            
            if (!empty($prodItem) && isset($prodItemGroup[$prodItem])) {
                $gid   = $prodItemGroup[$prodItem];
                $group = CustomerGroup::model()->findByPk((int)$gid);
                if (!empty($group)) {
                    $customer->group_id = $gid;
                }
            }

            if (!$customer->save(false)) {
                Yii::app()->end();
            }
            
            $options        = Yii::app()->options;
            $emailTemplate  = $options->get('system.email_templates.common');
            $emailBody      = $this->renderPartial('_email-new-account', compact('customer'), true);
            $emailTemplate  = str_replace('[CONTENT]', $emailBody, $emailTemplate);
            
            $email = new TransactionalEmail();
            $email->sendDirectly = (bool)($options->get('system.customer_registration.send_email_method', 'transactional') == 'direct');
            $email->to_name      = $customer->getFullName();
            $email->to_email     = $customer->email;
            $email->from_name    = $options->get('system.common.site_name', 'Marketing website');
            $email->subject      = Yii::t('customers', 'Your new account info!');
            $email->body         = $emailTemplate;
            $email->save();
    
            Yii::app()->end();
        }

        // existing customer, refund
        if (!empty($customer) && $transType == 'RFND') {
            $customer->status = Customer::STATUS_INACTIVE;
            $customer->save(false);
            Yii::app()->end();
        }

        // existing customer, renew
        if (!empty($customer) && $transType == 'BILL') {
            $customer->createQuotaMark();
            Yii::app()->end();
        }
    }

    protected function isIpnValid(array $data = array())
    {
        if (!isset($data['cverify'])) {
            return false;
        }
        $secretKey = "Dextor!#&@";
        $pop = "";
        $ipnFields = array();
        foreach ($data AS $key => $value) {
            if ($key == "cverify") {
                continue;
            }
            $ipnFields[] = $key;
        }
        sort($ipnFields);
        foreach ($ipnFields as $field) {
            $pop = $pop . $_POST[$field] . "|";
        }
        $pop = $pop . $secretKey;
        if ('UTF-8' != mb_detect_encoding($pop)) {
            $pop = mb_convert_encoding($pop, "UTF-8");
        }
        $calcedVerify = sha1($pop);
        $calcedVerify = strtoupper(substr($calcedVerify,0,8));
        return $calcedVerify == $data["cverify"];
    }

}
