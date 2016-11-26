<?php

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
        'email_id',
        'email_uid',
        'mhEmailID',
        'to_name',
        'to_email',
        'from_name',
        'from_email',
        'reply_to_name',
        'reply_to_email',
        'subject',
        'body',
        'plain_text',
        'send_at',
        'customer_id',
        'group_emai',
        'date_added',
        'last_updated',
        'max_retries',
        'status'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];

    public static function insertIgnore(array $attributes = [])
        {
            $model = new static($attributes);

            if ($model->usesTimestamps()) {
                $model->updateTimestamps();
            }

            $attributes = $model->getAttributes();

            $query = $model->newBaseQueryBuilder();
            $processor = $query->getProcessor();
            $grammar = $query->getGrammar();

            $table = $grammar->wrapTable($model->getTable());
            $keyName = $model->getKeyName();
            $columns = $grammar->columnize(array_keys($attributes));
            $values = $grammar->parameterize($attributes);

            $sql = "insert ignore into {$table} ({$columns}) values ({$values})";

            $id = $processor->processInsertGetId($query, $sql, array_values($attributes));

            $model->setAttribute($keyName, $id);

            return $model;
        }
}
