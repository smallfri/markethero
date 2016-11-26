<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class GroupEmailBounceModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_group_email_bounce_log';
    protected $primaryKey = "log_id";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];
}
