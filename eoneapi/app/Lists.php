<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Lists extends Authenticatable
{

    protected $table = 'mw_list';
    protected $primaryKey = "list_id";
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
