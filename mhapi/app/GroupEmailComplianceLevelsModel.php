<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class GroupEmailComplianceLevelsModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_compliance_levels';
    public $primaryKey = "compliance_level_id";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
            'compliance_level_id',
            'threshold'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];
}
