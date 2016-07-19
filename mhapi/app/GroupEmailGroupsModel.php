<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class GroupEmailGroupsModel extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'mw_group_email_groups';
    public $primaryKey = "group_email_id";


//    protected $status;
    protected $finished_at;
    protected $last_offset;

    const STATUS_DRAFT = 'draft';



            const STATUS_PENDING_SENDING = 'pending-sending';

            const STATUS_SENDING = 'sending';

            const STATUS_IN_REVIEW = 'in-review';

            const STATUS_COMPLIANCE_REVIEW = 'compliance-review';

            const STATUS_SENT = 'sent';

            const STATUS_PROCESSING = 'processing';

            const STATUS_PAUSED = 'paused';

            const STATUS_PENDING_DELETE = 'pending-delete';

            const STATUS_BLOCKED = 'blocked';

            const TYPE_REGULAR = 'regular';

            const TYPE_AUTORESPONDER = 'autoresponder';

            const BULK_ACTION_PAUSE_UNPAUSE = 'pause-unpause';

            const BULK_ACTION_MARK_SENT = 'mark-sent';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id', 'group_email_uid'

    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];

}
