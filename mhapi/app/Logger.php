<?php
namespace App;


class Logger
{

    const ALL = -1;

    const INFORMATION = 10;

    const WARNING = 20;

    const ERROR = 30;

    const ALERT = 100; // this log level is immune to log level squelching (always gets reported)
    const CRITICAL = 200; // this log level is immune to log level squelching (always gets reported)
//    const NOTIFICATION  = 30;

    protected static $entries = array();

    protected static $title = null;

    protected static $level = 0;

    protected static $last_time = 0;

    protected static $query_count = 0;

    public function __construct()
    {
//        self::$entries[] = "Logger Started... ".time();
    }

    public static function getLevelsArray()
    {

        return [-1 => self::getLevelName(-1),10 => self::getLevelName(10),20 => self::getLevelName(20),30 => self::getLevelName(30),100 => self::getLevelName(100),200 => self::getLevelName(200),];
    }

    public static function getLevelName($level)
    {

        switch($level)
        {
            case -1:
                return 'ALL';
            case 10:
                return 'INFORMATION';
            case 20:
                return 'WARNING';
            case 30:
                return 'ERROR';
            case 100:
                return 'ALERT';
            case 200:
                return 'CRITICAL';
//            case 30:
//                return 'NOTIFICATION';
        }
    }

    public static function addError($message,$title = null)
    {

        self::setTitle($title);
        self::setLevel(self::ERROR);
        self::addProgress($message,$title);

        $headers = 'FROM: support@licneseengine.com';

        mail('8436552621@vtext.com','Error',$title,$headers);

        mail('smallfriinc@gmail.com','Error ',print_r($message,true),$headers);

    }

    public static function addTrialAccount($message,$title = null)
    {

        self::setTitle($title);
        self::setLevel(self::ERROR);
        self::addProgress($message,$title);

        $headers = 'FROM: support@licneseengine.com';

        mail('8436552621@vtext.com','Trial Account Created',$title,$headers);

        mail('smallfriinc@gmail.com','Trial Account Created ',print_r($message,true),$headers);

    }

    public static function setTitle($title)
    {

        if($title!=null)
        {
            self::$title = $title;
        }
    }

    public static function setLevel($level = self::INFORMATION)
    {

        if($level>self::$level)
        {
            self::$level = $level;
        }
    }

    public static function addProgress($message,$title = null)
    {
        // if first time set start time and set default title if none provided
        if(self::$last_time==0)
        {
            self::$last_time = $_SERVER["REQUEST_TIME_FLOAT"];
            self::setLevel(self::INFORMATION);
            if(self::$title==null)
            {
                self::setTitle($message);
            }
        }

        // set title if passed in
        if($title!=null)
        {
            self::setTitle($title);
        }

        // get the queries since the last call
        $queries = null;
        $current_queries = \DB::getQueryLog();
        $current_query_count = count($current_queries);
        if($current_query_count>self::$query_count)
        {
            $queries = [];
            for($i = self::$query_count;$i<$current_query_count;$i++)
            {
                $queries[] = $current_queries[$i];
            }
            self::$query_count = $current_query_count;
        }

        // create the log entry
        $nowtime = microtime(true);
        $entry = new LogEntry(self::$level,self::get_last_caller(),$message,sprintf("%.4f",$nowtime-self::$last_time),$queries);
        self::$entries[] = $entry;

        // update last time accessed
        self::$last_time = $nowtime;

    }

    public static function get_last_caller()
    {

        $i = 2;
        $caller = self::my_call_stack_info($i);
//        pr($i." ".$caller);
//        pr(strpos($caller,'Facade'));
        while(strpos($caller,'Facade')!==false OR strpos($caller,'Logger')!==false)
        {
            $i++;
            $caller = self::my_call_stack_info($i);
//            pr($i." ".$caller);
//            pr(strpos($caller,'Facade'));
        }
        return $caller;
    }

    public static function my_call_stack_info($call_stack_backtrace_index = 0,$include_class = TRUE)
    {

        if($call_stack_backtrace_index<=0)
        {
            $results = array('Stack Trace:');
            for($i = 0;$i>=$call_stack_backtrace_index;$i--)
            {
                $results[] = self::my_call_stack_info(abs($i)+1,$include_class);
            }
            return implode("\n\t",$results);
        }
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        //find the caller of the caller of this method (the method that called the method with the called_from() statement)
        $call = $backtrace[$call_stack_backtrace_index];
        $prior_call = $backtrace[$call_stack_backtrace_index+1];

        if(array_key_exists('file',$call))
        {
            $file_parts = pathinfo($call['file']);
            $file = $file_parts['basename'];
        }
        else
        {
            $file = 'unknown file';
        }

        if(array_key_exists('line',$call))
        {
            $line = $call['line'];
        }
        else
        {
            $line = '??';
        }

        if($include_class==TRUE)
        {
            $class = array_key_exists('class',$prior_call)?$prior_call['class']:null;
            $function = array_key_exists('function',$prior_call)?$prior_call['function']:null;
        }
        else
        {
            $class = '';
            $function = '';
        }

        if(!empty($class)&&!empty($function))
        {
            $class .= '->';
        }

        $string = sprintf("(%s): %s[%s] %s%s",$call_stack_backtrace_index,$file,$line,$class,$function);

        return $string;
    }

    public static function addWarning($message,$title = null)
    {

        self::setTitle($title);
        self::setLevel(self::WARNING);
        self::addProgress($message,$title);
//
//        $headers = 'FROM: support@licneseengine.com';
//
//        mail('smallfriinc@gmail.com','Warning ',print_r($message,true),$headers);

    }

    public static function addCritical($message,$title = null)
    {

        self::setTitle($title);
        self::setLevel(self::CRITICAL);
        self::addProgress($message,$title);
    }

    public static function addInformation($message,$title = null)
    {

        self::setTitle($title);
        self::setLevel(self::INFORMATION);
        self::addProgress($message,$title);
    }

    public static function addAlert($message,$title = null)
    {

        self::setTitle($title);
        self::setLevel(self::ALERT);
        self::addProgress($message,$title);
    }

    public static function addGKFail($file,$function,$level = GateKeeper::SUPPORT_TRAINEE)
    {

        self::setTitle("Site Admin FAIL: $level");
        self::setLevel(self::ALERT);
        $name = \Auth::user()->name;
        $uid = \Auth::user()->id;
        $ulevel = \Auth::user()->site_admin;
        $message = "$name (#$uid) level ($ulevel) tried to access a level $level resource at $file->$function";
        self::addProgress($message);
    }

    public static function singleLog($message,$title = null,$level = self::INFORMATION)
    {

        // set title if passed in
        if($title==null)
        {
            $title = $message;
        }
        // create the log entry
        $nowtime = microtime(true);
        $entry = new LogEntry($level,self::get_last_caller(),$message,sprintf("%.4f",$nowtime-$_SERVER["REQUEST_TIME_FLOAT"]),null);

        $output = null;
        $start_time = $_SERVER["REQUEST_TIME_FLOAT"];
        $exectime = sprintf("%.4f",microtime(true)-$start_time);
//            $output .= "Time: $exectime s\n--------------\n";


        $exec_memory = (memory_get_peak_usage(true)/1024/1024);
        $output .= "Peak Memory: {$exec_memory} M\n";


        $output .= $entry->display();

        $mysql_now = date("Y-m-d H:i:s",time());
        \DB::connection('mysql')->table('mw_trace_logs')->insert(array('created_at' => $mysql_now,'updated_at' => $mysql_now,'execution' => $exectime,'level' => $level,'title' => $title,'log' => $output));
    }

    public static function writeLogs($flush = false)
    {

        self::logEntries();
        if($flush)
        {
            self::$entries = array();
            self::$title = null;
            self::$level = 0;
            self::$last_time = 0;
            self::$query_count = 0;
        }
    }

    public static function logEntries()
    {

        if(!empty(self::$entries) AND self::$level>=\Config::get('app.minimum_tracelog_level'))
        {
            self::addProgress("Shutting down...");

            $output = null;

            $start_time = $_SERVER["REQUEST_TIME_FLOAT"];
            $exectime = sprintf("%.4f",microtime(true)-$start_time);
//            $output .= "Time: $exectime s\n--------------\n";


            $exec_memory = (memory_get_peak_usage(true)/1024/1024);
            $output .= "Peak Memory: {$exec_memory} M\n";


            foreach(self::$entries AS $entry)
            {
                $output .= $entry->display();
            }

            $mysql_now = date("Y-m-d H:i:s",time());
            \DB::connection('mysql')->table('mw_trace_logs')->insert(array('created_at' => $mysql_now,'updated_at' => $mysql_now,'execution' => $exectime,'level' => self::$level,'title' => self::$title,'log' => $output));

        }
    }

    public static function currentUserInfo()
    {

        if(\Auth::user()!=null)
        {
            return \Auth::user()->name." (#".\Auth::user()->id.")";
        }
        else
        {
            return null;
        }
    }

}