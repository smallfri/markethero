<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class ComplianceAverageModel extends Authenticatable
{
    public $timestamps = true;

    public $bounce_report;
    public $abuse_report;
    public $unsubscribe_report;
    public $score;

    protected $table = 'mw_group_email_compliance_average';
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
