<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Field extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_list_field';
    protected $primaryKey = "field_id";
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