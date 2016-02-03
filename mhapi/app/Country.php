<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Country extends Model
{
    public $timestamps = false;


    protected $table = 'mw_country';
    protected $primaryKey = "country_id";
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
