<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylorotwell@gmail.com>
 */

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels nice to relax.
|
*/

require __DIR__.'/bootstrap/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
*/

$app = require_once __DIR__.'/bootstrap/app.php';

/*
 *
 * For trace logging
 *
 *
 */

function logger_shutdown()
{

    \App\Logger::logEntries();
}

register_shutdown_function('logger_shutdown');


function generateCallTrace()
{

    $e = new Exception();
    $trace = explode("\n", $e->getTraceAsString());
    // reverse array to make steps line up chronologically
    $trace = array_reverse($trace);
    array_shift($trace); // remove {main}
    array_pop($trace); // remove call to this method
    $length = count($trace);
    $result = array();

    for ($i = 0;$i<$length;$i++)
    {
        $result[] = ($i+1).')'.substr($trace[$i],
                strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
    }

    return "  ".implode("\n  ", $result);
}

function exec_shutdown()
{

    if (error_get_last()==null)
    {

        return;
    }
    $to_email = 'russell@smallfri.com';
    $output = null;

    $exec_memory = (memory_get_peak_usage(true)/1024/1024);

    $files = get_included_files();

    $num_files = count($files);

    if (isset($_SERVER["REQUEST_TIME_FLOAT"]))
    {
        $start_time = $_SERVER["REQUEST_TIME_FLOAT"];
    }
    else
    {
        $start_time = $_SERVER['REQUEST_TIME'];
    }
    $time = sprintf("%.4f", microtime(true)-$start_time);
    $output .= "Execution Time: $time s\n\n";

    $data = getrusage();
    $output .= "User time: ".($data['ru_utime.tv_sec']+$data['ru_utime.tv_usec']/1000000)."s\n";
    $output .= "System time: ".($data['ru_stime.tv_sec']+$data['ru_stime.tv_usec']/1000000)."s\n\n";

    $output .= "Peak Memory: {$exec_memory} M\n\n";
    if (error_get_last()==null)
    {
        $output .= "Errors: No execution errors!";
    }
    else
    {
        $output .= "Last error:";
        $output .= str_replace("Array", '', print_r(error_get_last(), true));
    }
    $output .= "\n";


    $output .= "POST info: ".print_r($_POST, true)."\nGET info: ".print_r($_GET,
            true)."\nSERVER info: ".print_r($_SERVER, true)."\n";

    $output .= "Call Trace:\n".generateCallTrace();
    $output .= "\n\n";


    $output .= "Included: {$num_files} files\n\n";
    $i = 0;
    foreach ($files AS $file)
    {
        $i++;
        $output .= "  $i) $file\n";
    }
    $output .= "\n";

    mail($to_email, gethostname().' Died With Errors! '.time(), $output);
}

//register_shutdown_function('exec_shutdown');

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
