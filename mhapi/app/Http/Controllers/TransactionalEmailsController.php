<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 2/5/16
 * Time: 8:56 AM
 */

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Models\Customer;
use App\Models\DeliveryServerModel;
use App\Models\TransactionalEmailLogModel;
use App\Models\TransactionalEmailModel;

class TransactionalEmailsController extends ApiController
{

    private $endpoint;

    function __construct()
    {

        $this->endpoint = new \EmailOneApi_Endpoint_TransactionalEmails();
        $this->middleware('auth.basic');

    }

    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data))
        {
            return $this->respondWithError('No data found, please check your POST data and try again');
        }

        $expected_input = [
            'to_name',
            'to_email',
            'from_name',
            'from_email',
            'reply_to_name',
            'reply_to_email',
            'subject',
            'body',
            'plain_text',
            'send_at',
            'customer_id'
        ];

        $missing_fields = array();

        foreach ($expected_input AS $input)
        {
            if (!isset($data[$input]))
            {
                $missing_fields[$input] = 'Input field not found.';
            }

        }

        if (!empty($missing_fields))
        {
            return $this->respondWithError($missing_fields);
        }

        $Customer = Customer::find($data['customer_id']);

        if (empty($Customer))
        {
            return $this->respondWithError('Customer id does not exist.');
        }

        $emailExist = TransactionalEmailModel::where('to_email', '=', $data['to_email'])
            ->where('subject', '=', $data['subject'])
            ->where('body', '=', $data['body'])
            ->get();

        if (!$emailExist->isEmpty())
        {
            return $this->respondWithError('This email already exists.');
        }

        $email_uid = uniqid();
        $transactional = new TransactionalEmailModel();
        $transactional->email_uid = $email_uid;
        $transactional->to_name = $data['to_name'];
        $transactional->to_email = $data['to_email'];
        $transactional->from_name = $data['from_name'];
        $transactional->from_email = $data['from_email'];
        $transactional->reply_to_name = $data['reply_to_name'];
        $transactional->reply_to_email = $data['reply_to_email'];
        $transactional->subject = $data['subject'];
        $transactional->body = $data['body'];
        $transactional->plain_text = $data['plain_text'];
        $transactional->send_at = $data['send_at'];
        $transactional->customer_id = $data['customer_id'];
        $transactional->date_added = new \DateTime();
        $transactional->status = 'sent';
        $transactional->save();


        if ($transactional->email_id>0)
        {
            $data['email_uid'] = $email_uid;
            $data['email_id'] = $transactional->email_id;

            $this->sendByPHPMailer($data);

            return $this->respond(['email_uid' => $email_uid]);
        }

        return $this->respondWithError('Email was not created.');

    }

    protected function sendByPHPMailer($data)
    {

        $server = DeliveryServerModel::where('status', '=', 'active')
            ->where('use_for', '=', DeliveryServerModel::USE_FOR_ALL)
            ->get();

        if (empty($server))
        {
            return 1;
        }

        $mail = New \PHPMailer();
        $mail->SMTPKeepAlive = true;

        $mail->isSMTP();
        $mail->CharSet = "utf-8";
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = "tls";
        $mail->Host = $server[0]['hostname'];
        $mail->Port = 2525;
        $mail->Username = $server[0]['username'];
        $mail->Password = base64_decode($server[0]['password']);
        $mail->Sender = Helpers::findBounceServerSenderEmail($server[0]['bounce_server_id']);


        $mail->addCustomHeader('X-Mw-Customer-Id', $data['customer_id']);
        $mail->addCustomHeader('X-Mw-Email-Uid', $data['email_uid']);
        $mail->addCustomHeader('X-Mw-Group-Id', 0);

        $mail->addReplyTo($data['from_email'], $data['from_name']);
        $mail->setFrom($data['from_email'], $data['from_name']);
        $mail->addAddress($data['to_email'], $data['to_name']);

        $mail->Subject = $data['subject'];
        $mail->MsgHTML($data['body']);

//        $this->stdout('Sending transactional Emails to '.$data['to_email'].'!');

        if (!$mail->send())
        {
            $this->updateTransactionalEmail($data['email_uid'], 'unsent');
            $this->logTransactionalEmailDelivery($data['email_id'], $mail->ErrorInfo);
//            $this->stdout('ERROR Sending transactional Email to '.$data['to_email'].'!');
//            $this->stdout('ERROR '.$mail->ErrorInfo.'!');
        }
        else
        {
            $this->updateTransactionalEmail($data['email_uid'], 'sent');
//            $this->stdout('Sent transactional Email to '.$data['to_email'].'!');
            $this->logTransactionalEmailDelivery($data['email_id'], 'OK');
        }

        $mail->clearAddresses();
        $mail->clearAttachments();
        $mail->clearCustomHeaders();


        $mail->SmtpClose();

    }

    public function updateTransactionalEmail($emailId, $status)
    {

        TransactionalEmailModel::where('email_uid', $emailId)
            ->update(['status' => $status]);
        TransactionalEmailModel::where('email_uid', '=', $emailId)->increment('retries');

    }

    public function logTransactionalEmailDelivery($emailId, $message = 'OK')
    {

        TransactionalEmailLogModel::insert([
            'email_id' => $emailId,
            'message' => $message,
            'date_added' => new \DateTime()
        ]);
        return;

    }

    public function destroy($email_uid)
    {

        // delete email
        $response = $this->endpoint->delete($email_uid);

        if ($response->body['status']=='success')
        {
            return $this->respond(['email_uid' => 'Deleted '.$email_uid.'.']);

        }

        return $this->respondWithError('Email was not deleted.');
    }

}