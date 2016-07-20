<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class ListsDefaults extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_list_default';
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
