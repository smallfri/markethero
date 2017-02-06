<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;

class SendForgottenEmailsTest extends TestCase
{

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testSendForgottenEmailsTest()
    {

        $group = factory(App\Models\GroupEmailGroupsModel::class)->create(
            [
                'customer_id' => 11,
                'status' => 'pending-sending',
            ]
        );

        for ($x = 0;$x<=10;$x++)
        {
            factory(App\Models\BroadcastEmailModel::class)->create(['groupID' => $group['group_email_id']]);
        }

        $this->assertTrue(true);

    }

    public function testSendForgottenEmailTest()
    {
        Artisan::call('send-forgotten-email');
    }
}
