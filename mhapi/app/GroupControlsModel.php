<?php

namespace App;

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
        'groups_at_once', 'emails_at_once', 'change_server_at', 'compliance_limit', 'groups_in_parallel', 'groups_emails_in_parallel'

    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];
}
