<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

use Carbon\Carbon;


$factory->define(App\Models\BroadcastEmailModel::class, function (Faker\Generator $faker) {

    $time = Carbon::parse('2 hours ago')->format('Y-m-d H:i:s');

    return [
        'mhEmailID' => $faker->numberBetween(0,10000),
        'emailUID' => uniqid(null, true),
        'customerID' => 11,
        'status' => 'queued',
        'toEmail' => 'smallfriinc@gmail.com',
        'toName' => $faker->name,
        'replyToName' => $faker->name,
        'fromEmail' => $faker->email,
        'fromName' => $faker->name,
        'replyToEmail' => $faker->email,
        'subject' => $faker->paragraph,
        'plainText' => $faker->paragraph,
        'body' => $faker->paragraph,
        'dateAdded' => $time,
        'lastUpdated' => $time,

    ];
});

$factory->define(App\Models\GroupEmailGroupsModel::class, function (Faker\Generator $faker) {

    return [
        'group_email_uid' => uniqid(null, true),
        'customer_id' => 11,
        'status' => 'pending-sending',

    ];
});
