<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class GroupAbuseModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_group_email_abuse_report';
    protected $primaryKey = "report_id";
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
