<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class GroupControlsModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_group_email_options';
    protected $primaryKey = "id";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'emails_at_once', 'emails_per_minute', 'groups_in_parallel', 'group_emails_in_parallel', 'change_server_at',
        'compliance_limit', 'memory_limit', 'compliance_abuse_range', 'compliance_unsub_range', 'compliance_bounce_range'

    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];
}
