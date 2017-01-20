<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/28/16
 * Time: 9:01 AM
 */

namespace App\Http\Controllers;

use App\Models\TraceLog;
use Illuminate\Support\Facades\Input;
use App\Logger;

class LogsController extends Controller
{

    public $endpoint;

    function __construct()
    {

    }

    public function index()
    {

        $level = $data['level'] = Input::get('level', -1);
        $min_time = $data['time'] = Input::get('time', 0);
        $text = $data['text'] = Input::get('text', null);

        $sql = TraceLog::where('level', '>=', $level)
            ->where('execution', '>=', $min_time)
            ->orderBy('created_at', 'DESC');
        if ($text!=null)
        {
            $sql->where(function ($query) use ($text)
            {
                $query->where('title', 'LIKE', "%$text%")->orWhere('log', 'LIKE', "%$text%");
            });
        }

        $data['logs'] = $sql->take(50)->get();


        // get levels for dropdown
        $data['level_array'] = Logger::getLevelsArray();
        //        echo

        return view('logs.logs', $data);
    }

    public function viewLog($id)
    {

        $data['log'] = TraceLog::find($id);

        return view('logs.viewLog', $data);
    }

    public function applicationLog()
    {
        $data['log']  = file_get_contents('/var/www/html/apps/common/runtime/application.log');

        return view('logs.application', $data);

    }
}
