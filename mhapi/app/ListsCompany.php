<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class ListsCompany extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_list_company';
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