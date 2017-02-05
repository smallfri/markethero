<?php 
$I = new AcceptanceTester($scenario);

$I->runShellCommand('php artisan send-groups');

$I->seeInShellOutput('Groups in Parallel 10');