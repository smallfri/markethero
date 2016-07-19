<?php

$pw = 'd0i7';
$opts = getopt('p:');


DEFINE('APPPATH','apps');
//echo APPPATH.'controllers.new';
//exit;
error_reporting(E_ALL);
ini_set('display_errors', '1');
$deploy_folders = array(
//    'controllers'
//        , 'views'
//        , 'models'
);

function get_deploy_folders()
{
    global $deploy_folders;

    $handle = opendir(APPPATH);
    $blacklist = array('.', '..', 'assets', 'config', 'database', 'lang', 'start', 'storage', 'tests', 'filters.php', 'api', 'extensions', 'cgi_bin');
    while (false !== ($file = readdir($handle))) {
        if (!in_array($file, $blacklist)) {
//                echo "$file<br />\n";
            if(preg_match("/\.new$/",$file))
            {
                $file = str_replace('.new','',$file);
                $deploy_folders[] = $file;
            }
        }
    }
    closedir($handle);
    echo "FOUND folders!!!";
    print_r($deploy_folders);
    return;
}

function deploy()
{
    global $pw;
	global $path;
    global $deploy_folders;
    echo "Deploying...\n\n";

    $saved_last = FALSE;

    get_deploy_folders();
//    exit;


//    if(file_exists(APPPATH.'controllers.new'))
    if(count($deploy_folders) > 0)
    {
        if(!$saved_last)
        {
            $timestamp = get_timestamp();
            if($timestamp != NULL)
            {
                save_last($timestamp);
            }
            $saved_last = TRUE;
        }

        $timestamp = date ("Y-m-d_H-i-s", time());
        foreach($deploy_folders AS $directory)
        {
            if(file_exists(APPPATH.$directory.'.new'))
            {
                // backup current
                rename(APPPATH.$directory,APPPATH.$timestamp.'.last.'.$directory);
                // deploy new
                rename(APPPATH.$directory.'.new',APPPATH.$directory);

                echo " - deploying new $directory!\n";
            }
        }
        echo "\n\n### ## # !!!DEPLOYED!!! # ## ###\n\n";

//        echo "\n\nSanity Check: <a href=\"admin/load_models\" target=\"_blank\">Test Models</a>";

        echo "Emergency Rollback command: php -f d3pl0y.php p=d0i7 a=yes.rollback\n";

//        echo "\n\n<a href=\"d3pl0y.php?p=$pw\">Home!</a>";

    }
    else
    {
        echo " - NO NEW FILES!\n";
    }
//    echo "\n\n\n=======================================================\n\n";

//    exit;
}

function save_last($timestamp)
{
    global $deploy_folders;
//        echo "Saving last...<br />";
    foreach($deploy_folders AS $directory)
    {
        rename(APPPATH.$timestamp.'.last.'.$directory, APPPATH.$timestamp.'.'.$directory);
    }
//        echo "SAVED LAST!";
}

function get_timestamp()
{
    global $deploy_folders;
//        echo "Getting timestamp...<br />";
    $timestamp = NULL;
    $handle = opendir(APPPATH);
    $blacklist = array('.', '..', 'assets', 'config', 'database', 'lang', 'start', 'storage', 'tests', 'filters.php');
    while (false !== ($file = readdir($handle))) {
        if (!in_array($file, $blacklist)) {
//                echo "$file<br />\n";
            $parts = explode('.',$file);
            if(isset($parts[1]) AND $parts[1] == 'last')
            {
                $timestamp = $parts[0];
//                print_r($parts);
//                echo "now: $timestamp<br />";
//                echo "$file<br />";
                $deploy_folders[] = $parts[2];
//                break;
            }
        }
    }
    closedir($handle);
    return $timestamp;
}

function rollback()
{
    global $pw;
    global $deploy_folders;
    echo "Rolling back...\n\n";
//        $handle = opendir(APPPATH);
//        $blacklist = array('.', '..', 'migrations', 'libraries', 'cache', 'helpers', 'errors', 'third_party', 'hooks', 'plugins', 'core', 'config', 'logs', 'language', 'index.html', '.htaccess', 'rating.php');
//        while (false !== ($file = readdir($handle))) {
//            if (!in_array($file, $blacklist)) {
//                echo "$file<br />\n";
//                $parts = explode('.',$file);
//                if(isset($parts[1]) AND $parts[1] == 'last')
//                {
//                    $timestamp = $parts[2];
//                    echo "now: $timestamp<br />";
//                    break;
//                }
//            }
//        }
//        closedir($handle);
    $timestamp = get_timestamp();
//        echo "|$timestamp|";

//        save_last($timestamp);

//        $timestamp = date ("Y-m-d_H-i-s", time());
    if($timestamp != NULL)
    {
        foreach($deploy_folders AS $directory)
        {
            if(file_exists(APPPATH.$timestamp.'.last.'.$directory))
            {
                echo "Rolling back ".$timestamp.'.last.'.$directory."\n";
                // save bad ones
                rename(APPPATH.$directory, APPPATH.$directory.'.bad');
                // bring back last ones
                rename(APPPATH.$timestamp.'.last.'.$directory, APPPATH.$directory);
            }
        }

        echo "\n\n### ## # !!!ROLLED BACK!!! # ## ###\n\n";

    }
    else
    {
        echo "Nothing to Roll Back!\n";
    }

//    echo "\n\n\nUNDO: <a href=\"d3pl0y.php?a=deploy&p=$pw\">Deploy!</a>";

//    echo "\n\n<a href=\"d3pl0y.php?p=$pw\">Home!</a>";

//    exit;
}

function show_files($files)
{
    echo count($files)." folders:\n";
    $i = 0;
    foreach( $files AS $file)
    {
        echo "  #".++$i.") $file\n";
    }
}

function show_rollback($pw)
{
    global $deploy_folders;
    $deploy_folders = array();
    $timestamp = get_timestamp();
//        echo "rollback!";
    if(count($deploy_folders) == 0)
    {
        echo "Nothing to rollback.";
    }
    else
    {
        echo "Are you sure you want to roll back? Use: php -f d3pl0y.php p=d0i7 a=yes.rollback\n\n";

        echo "You will be rolling back code from [$timestamp] ";
        show_files($deploy_folders);
    }
    echo "=======================================================\n\n";

}

function show_deploy($pw)
{
    echo "=======================================================\n";
    global $deploy_folders;
    $deploy_folders = array();
//        echo "deploy!";
    get_deploy_folders();
    if(count($deploy_folders) == 0)
    {
        echo "Nothing to deploy.";
    }
    else
    {
        echo "Are you sure you want to deploy? Use: php -f d3pl0y.php p=d0i7 a=yes.deploy\n\n";
        echo "You will be deploying ";
        show_files($deploy_folders);
    }
    echo "=======================================================\n";

}

print_r($opts);
if(isset($opts['p']) && $opts['p'] == $pw)
{
    $host = 'Unknown';
    if( isset($_SERVER['HOST']) )
    {
        $host = $_SERVER['HOST'];
    }
    elseif(isset($_SERVER['HOSTNAME']) )
    {
        $host = $_SERVER['HOSTNAME'];
    }
    echo "\nWORKING on: {$host} ...\n";

    if(isset($_GET['a']))
    {
        if($_GET['a'] == 'deploy')
        {
            show_deploy($pw);
        }
        else if($_GET['a'] == 'yes.deploy')
        {
            //        echo "rollback!";
            deploy();
        }
        else if($_GET['a'] == 'rollback')
        {
            show_rollback($pw);
        }
        else if($_GET['a'] == 'yes.rollback')
        {
            //        echo "rollback!";
            rollback();
        }
    }
    else
    {
        show_deploy($pw);
        show_rollback($pw);

//        echo "Sanity Check: <a href=\"admin/load_models\" target=\"_blank\">Test Models</a>";
        echo "\n";
    }
}
else
{
    echo "wrong!";
    print_r($_GET);
}


//print_r($_SERVER);
?>