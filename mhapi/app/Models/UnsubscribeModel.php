<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class UnsubscribeModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_group_email_unsubscribe';
    protected $primaryKey = "id";
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
