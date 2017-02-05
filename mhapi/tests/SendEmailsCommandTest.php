<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;

class SendEmailsCommandTest extends TestCase
{

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testSendEmailTest()
    {

        $id = rand(1, 10000);
        $id2 = rand(1, 10000);

        factory(App\Models\BroadcastEmailModel::class)->create(
            [
                'mhEmailID' => $id2,
                'emailUID' => uniqid(null, true),
                'customerID' => 11,
                'groupID' => $id,
                'status' => 'pending-sending',
                'toEmail' => 'smallfriinc@gmail.com',
                'toName' => 'to_name',
                'fromEmail' => 'some@email.com',
                'fromName' => 'from_name',
                'replyToEmail' => 'some@email.com',
                'subject' => 'subject',
                'body' => 'body',
                'plainText' => 'plaintext',


            ]
        );

        factory(App\Models\GroupEmailGroupsModel::class)->create(
            [
                'customer_id' => 11,
                'group_email_id' => $id,
                'status' => 'pending-sending',
            ]
        );

        Artisan::call('send-broadcast');

        $this->assertTrue(true);

    }
}
