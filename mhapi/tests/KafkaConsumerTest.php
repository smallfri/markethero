<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;

class KafkaConsumerTest extends TestCase
{

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testKafkaConsumerTest()
    {

        $id = rand(1, 10000);

        Artisan::call('kafka-producer', ['id' => $id]);

        $this->seeInDatabase('mw_broadcast_email_log',
            ['toEmail' => 'smallfriinc@gmail.com', 'mhEmailID' => $id]);

        $this->assertTrue(true);
    }

}
