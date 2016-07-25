<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class GroupEmailBounceLogModel extends Authenticatable
{

    public $timestamps = false;

    protected $table = 'mw_group_email_bounce_log';

    protected $primaryKey = "log_id";

    const BOUNCE_SOFT = 'soft';

    const BOUNCE_HARD = 'hard';

//    public $customer_id;
//
//    public $group_id;
//
//    public $email_uid;
//
//    public $email;
//
//    public $message;
//
//    public $bounce_type;
//
//    public $processed;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable
        = [
            'customer_id', 'group_id', 'email_uid', 'email', 'message', 'bounce_type', 'processed'
        ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden
        = [

        ];
}
