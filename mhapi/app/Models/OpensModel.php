<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class OpensModel extends Authenticatable
{
   public $timestamps = false;

    protected $table = 'mw_group_email_opens';
    protected $primaryKey = "openId";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'openedIP',
        'openDate',
        'externalId',
        'emailOneId',
        'groupId',
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