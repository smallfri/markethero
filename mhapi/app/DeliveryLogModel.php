<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class DeliveryLogModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_campaign_delivery_log';
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
