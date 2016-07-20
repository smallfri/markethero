<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 7/20/16
 * Time: 9:03 AM
 */

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class GroupEmailModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_group_email';
    protected $primaryKey = "email_id";
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
