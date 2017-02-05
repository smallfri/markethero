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
    public function SendForgottenEmailsTest()
    {

        $id = rand(1, 10000);

        for ($x = 0;$x<=10;$x++)
        {
            factory(App\Models\BroadcastEmailModel::class)->create(['groupID' => $id]);
        }

        factory(App\Models\GroupEmailGroupsModel::class)->create(
            [
                'customer_id' => 11,
                'group_email_id' => $id,
                'status' => 'pending-sending',
            ]
        );
        Artisan::call('send-forgotten-email');

        $this->assertTrue(true);

    }
}
