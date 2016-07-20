<?php
namespace App\Models;

class LogEntry {

    public $time;
    public $caller;
    public $message;
    public $level;
    public $lapse_time;
    public $queries = null;

    public function __construct($level, $caller, $message, $lapse_time, $queries = null)
    {
        $this->time = time();
        $this->level = $level;
        $this->caller = $caller;
        $this->message = $message;
        $this->lapse_time = $lapse_time;
        $this->queries = $queries;
    }

    public function __toString()
    {
        $qoutput = null;
        if($this->queries != null)
        {
            $qoutput = "\n---------------------------------------------------------------------\n".print_r($this->queries, TRUE);

        }

        $datetime = date('Y-m-d H:i:s', $this->time);
        $output = <<<END
Timestamp: {$this->time}
Datetime: $datetime
Lapse: {$this->lapse_time}
Level: {$this->level}
Caller: {$this->caller}
Message:
{$this->message}$qoutput
=====================================================================

END;

        return $output;
    }

    public function display()
    {
        return $this->__toString();
    }

}