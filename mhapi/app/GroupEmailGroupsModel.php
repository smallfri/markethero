<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class GroupEmailGroupsModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_group_email_groups';
    protected $primaryKey = "group_email_id";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id', 'group_email_uid'

    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];
}
