<?php

use App\Models\GroupEmailGroupsModel;

$I = new FunctionalTester($scenario);

$I->wantTo('Test the SendGroupsCommand');

$id = $I->haveRecord('App\Models\GroupEmailGroupsModel', ['customer_id'=>11, 'status'=>'pending-sending']);

$I->seeRecord('App\Models\GroupEmailGroupsModel', ['customer_id'=>11, 'status'=>'pending-sending']);

$I->runShellCommand('php artisan send-groups');

$I->seeInShellOutput('starting with offset');

$I->seeInShellOutput('Groups in Parallel');