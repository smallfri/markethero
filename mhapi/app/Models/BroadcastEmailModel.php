<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class BroadcastEmailModel extends Authenticatable
{

    public $timestamps = false;

    protected $table = 'mw_broadcast_email_log';

    protected $primaryKey = "emailID";

    const STATUS_APPROVED = 'approved';

    const STATUS_MANUAL_REVIEW = 'manual-review';

    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING_SENDING = 'pending-sending';

    const STATUS_SENDING = 'sending';

    const STATUS_IN_REVIEW = 'in-review';

    const STATUS_COMPLIANCE_REVIEW = 'compliance-review';

    const STATUS_IN_COMPLIANCE_REVIEW = 'in-compliance';

    const STATUS_FAILED_SEND = 'failed';

    const STATUS_FAILED_ERROR = 'error';

    const STATUS_SENT = 'sent';

    const STATUS_PROCESSING = 'processing';

    const STATUS_PAUSED = 'paused';

    const STATUS_PENDING_DELETE = 'pending-delete';

    const STATUS_BLOCKED = 'blocked';

    const STATUS_QUEUED = 'queued';

    const TYPE_REGULAR = 'regular';

    const TYPE_AUTORESPONDER = 'autoresponder';

    const BULK_ACTION_PAUSE_UNPAUSE = 'pause-unpause';

    const BULK_ACTION_MARK_SENT = 'mark-sent';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable
        = [
            'mhEmailID',
            'emailUID',
            'customerID',
            'groupID',
            'toEmail',
            'toName',
            'fromEmail',
            'fromName',
            'replyToEmail',
            'replyToName',
            'subject',
            'body',
            'plainText',
            'status',
            'dateAdded',
            'lastUpdated',
            'hash',
        ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden
        = [

        ];

    public static function insertIgnore(array $attributes = [])
    {

        $model = new static($attributes);

        if ($model->usesTimestamps())
        {
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
