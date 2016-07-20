<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_customer';

    protected $primaryKey = "customer_id";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable
        = [

        ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden
        = [
            'password',
            'hourly_quota',
            'removable',
            'confirmation_key',
            'oauth_uid',
            'oauth_provider',
            'status',
            'date_added',
            'last_updated',
            'avatar',
            'language_id'
        ];
}
