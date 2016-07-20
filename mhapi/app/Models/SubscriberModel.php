<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class SubscriberModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_list_subscriber';
    protected $primaryKey = "subscriber_id";
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
        'password'
    ];
}
