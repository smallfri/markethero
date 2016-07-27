<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class TransactionalEmailOptionsModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_transactional_email_options';
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