<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class BounceServer extends Authenticatable
{

    protected $table = 'mw_bounce_server';
    protected $primaryKey = "server_id";
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