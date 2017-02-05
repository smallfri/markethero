<?php 
$I = new AcceptanceTester($scenario);

$I->

$I->runShellCommand('php artisan send-forgotten-email');

$I->seeInShellOutput('Groups in Parallel 10');