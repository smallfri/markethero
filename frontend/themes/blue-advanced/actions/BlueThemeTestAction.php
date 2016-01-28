<?php defined('MW_PATH') || exit('No direct script access allowed');

class BlueThemeTestAction extends CAction 
{
    public function run()
    {
        echo 'Hello from inside an action!!!';
    }
}