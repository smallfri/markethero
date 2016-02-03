<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ListsCustomerNotification extends Model
{
    public $timestamps = false;

    protected $table = 'mw_list_customer_notification';
    protected $primaryKey = "list_id";
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
