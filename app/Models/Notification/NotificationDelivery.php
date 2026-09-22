<?php

namespace App\Models\Notification;

use Database\Factories\Notification\NotificationDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationDelivery extends Model
{
    /** @use HasFactory<NotificationDeliveryFactory> */
    use HasFactory;

    public const STATUS_FAILED = 'failed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    protected $fillable = [
        'channel',
        'provider',
        'message_type',
        'deduplication_key',
        'recipient_hash',
        'recipient',
        'message',
        'context',
        'status',
        'attempts',
        'external_id',
        'response',
        'last_error',
        'sent_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'attempts' => 0,
    ];

    protected $hidden = [
        'recipient',
        'message',
        'response',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'recipient' => 'encrypted',
            'message' => 'encrypted',
            'context' => 'array',
            'attempts' => 'integer',
            'response' => 'encrypted:array',
            'last_error' => 'encrypted',
            'sent_at' => 'immutable_datetime',
        ];
    }
}
