<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendGroupEmail extends Job implements ShouldQueue
{

    use InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $mail;

    public function __construct(Mail $mail)
    {

        $this->mail = $mail;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Mailer $mailer)
    {

        $mailer->send('emails.main', ['mail' => $this->mail], function ($message)
        {

            $message->from($this->mail->from_email, $this->from_name);
            $message->to($this->to_email, $this->name)->subject($this->subject);

            $headers = $message->getHeaders();
            $headers->addTextHeader('X-Mw-Group-Uid', $this->mail->group_email_uid);
            $headers->addTextHeader('X-Mw-Customer-Id', $this->mail->customer_id);
        });
    }
}
