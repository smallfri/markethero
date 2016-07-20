<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Zone extends Model
{
    public $timestamps = false;


    protected $table = 'mw_zone';
    protected $primaryKey = "zone_id";
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
