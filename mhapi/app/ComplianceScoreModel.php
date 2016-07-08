<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class ComplianceScoreModel extends Authenticatable
{
    public $timestamps = true;

    protected $table = 'mw_group_email_compliance_score';
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
