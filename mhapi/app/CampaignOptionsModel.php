<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class CampaignOptionsModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_campaign_option';

    protected $primaryKey = "campaign_id";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable
        = [

        ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden
        = [

        ];
}
