<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Lists extends Model
{
    public $timestamps = false;


    protected $table = 'mw_list';
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
        'visibility',
        'opt_id',
        'opt_out',
        'merged',
        'welcome_email',
        'subscriber_404_redirect',
        'meta_data',
        'status',
        'date_added',
        'last_updated'
    ];
}
