<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class TransactionalEmailGroupModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_transactional_email_group';
    protected $primaryKey = "transactional_email_group_id";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id'

    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];
}
