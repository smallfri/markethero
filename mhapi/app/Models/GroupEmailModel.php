<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class GroupEmailModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_group_email';
    protected $primaryKey = "email_id";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];
}
