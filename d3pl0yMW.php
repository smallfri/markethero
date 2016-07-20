<?php

$pw = 'd0i7';

DEFINE('APPPATH','mhapi/');
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
//                echo "$file<br /><br/>";
            if(preg_match("/\.new$/",$file))
            {
                $file = str_replace('.new','',$file);
                $deploy_folders[] = $file;
            }
        }
    }
    closedir($handle);
    echo "FOUND folders!!!<br/>";
    print_r($deploy_folders);
    return;
}

function deploy()
{
    global $pw;
	global $path;
    global $deploy_folders;
    echo "Deploying...<br/><br/>";

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

                echo " - deploying new $directory!<br/>";
            }
        }
        echo "<br/><br/>### ## # !!!DEPLOYED!!! # ## ###<br/><br/>";

//        echo "<br/><br/>Sanity Check: <a href=\"admin/load_models\" target=\"_blank\">Test Models</a>";

        echo "Emergency Rollback command: php -f d3pl0y.php p=d0i7 a=yes.rollback<br/>";

//        echo "<br/><br/><a href=\"d3pl0y.php?p=$pw\">Home!</a>";

    }
    else
    {
        echo " - NO NEW FILES!<br/>";
    }
//    echo "<br/><br/><br/>=======================================================<br/><br/>";

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
//                echo "$file<br /><br/>";
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
    echo "Rolling back...<br/><br/>";
//        $handle = opendir(APPPATH);
//        $blacklist = array('.', '..', 'migrations', 'libraries', 'cache', 'helpers', 'errors', 'third_party', 'hooks', 'plugins', 'core', 'config', 'logs', 'language', 'index.html', '.htaccess', 'rating.php');
//        while (false !== ($file = readdir($handle))) {
//            if (!in_array($file, $blacklist)) {
//                echo "$file<br /><br/>";
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
                echo "Rolling back ".$timestamp.'.last.'.$directory."<br/>";
                // save bad ones
                rename(APPPATH.$directory, APPPATH.$directory.'.bad');
                // bring back last ones
                rename(APPPATH.$timestamp.'.last.'.$directory, APPPATH.$directory);
            }
        }

        echo "<br/><br/>### ## # !!!ROLLED BACK!!! # ## ###<br/><br/>";

    }
    else
    {
        echo "Nothing to Roll Back!<br/>";
    }

//    echo "<br/><br/><br/>UNDO: <a href=\"d3pl0y.php?a=deploy&p=$pw\">Deploy!</a>";

//    echo "<br/><br/><a href=\"d3pl0y.php?p=$pw\">Home!</a>";

//    exit;
}

function show_files($files)
{
    echo count($files)." folders:<br/>";
    $i = 0;
    foreach( $files AS $file)
    {
        echo "  #".++$i.") $file<br/>";
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
        echo "Are you sure you want to roll back? Use: php -f d3pl0y.php p=d0i7 a=yes.rollback<br/><br/>";

        echo "You will be rolling back code from [$timestamp]<br/><br/> ";
        show_files($deploy_folders);
    }
    echo "=======================================================<br/><br/>";

}

function show_deploy($pw)
{
    echo "=======================================================<br/>";
    global $deploy_folders;
    $deploy_folders = array();
//        echo "deploy!";
    get_deploy_folders();
    if(count($deploy_folders) == 0)
    {
        echo "Nothing to deploy.<br/><br/>";
    }
    else
    {
        echo "Are you sure you want to deploy? Use: php -f d3pl0y.php p=d0i7 a=yes.deploy<br/><br/>";
        echo "You will be deploying <br/><br/>";
        show_files($deploy_folders);
    }
    echo "=======================================================<br/>";

}

if(isset($_GET['p']) && $_GET['p'] == $pw)
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
    echo "<br/>WORKING on: {$host} ...<br/>";

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
        echo "<br/>";
    }
}
else
{
    echo "wrong!";
    print_r($_GET);
}


//print_r($_SERVER);
?>