<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class SegmentCondition extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_list_segment_condition';
    protected $primaryKey = "condition_id";
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
