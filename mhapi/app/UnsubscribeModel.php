<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class UnsubscribeModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_group_email_unsubscribe';
    public $primaryKey = "id";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];
}
