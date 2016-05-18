<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class TransactionalEmailComplianceModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_transactional_email_compliance';
    public $primaryKey = "transactional_email_group_id";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
            'compliance_level_type_id',
            'last_processed_id',
            'compliance_round',
            'compliance_approval_user_id',
            'date_added',
            'last_updated',
            'offset',
            'compliance_status'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];
}
