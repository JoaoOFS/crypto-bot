<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'alert_id',
        'type',
        'channel',
        'title',
        'message',
        'data',
        'sent_at',
        'read_at',
        'status',
        'error_message'
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime'
    ];

    // Tipos de notificação disponíveis
    const TYPE_EMAIL = 'email';
    const TYPE_PUSH = 'push';
    const TYPE_WEBHOOK = 'webhook';

    // Status possíveis
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function alert()
    {
        return $this->belongsTo(Alert::class);
    }

    public function markAsSent()
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now()
        ]);
    }

    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage
        ]);
    }

    public function markAsRead()
    {
        $this->update([
            'read_at' => now()
        ]);
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSent()
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRead()
    {
        return !is_null($this->read_at);
    }
}
