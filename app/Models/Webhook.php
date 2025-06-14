<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Webhook extends Model
{
    use HasFactory;

    const EVENT_ALERT_TRIGGERED = 'alert.triggered';
    const EVENT_STRATEGY_EXECUTED = 'strategy.executed';
    const EVENT_PORTFOLIO_UPDATED = 'portfolio.updated';
    const EVENT_EXCHANGE_ERROR = 'exchange.error';

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'retry_count',
        'timeout',
        'headers'
    ];

    protected $casts = [
        'events' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'last_failed_at' => 'datetime'
    ];

    protected $hidden = [
        'secret'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function markAsTriggered()
    {
        $this->update(['last_triggered_at' => now()]);
    }

    public function markAsFailed($error)
    {
        $this->update([
            'last_failed_at' => now(),
            'last_error' => $error
        ]);
    }

    public function generateSignature(array $payload): string
    {
        if (!$this->secret) {
            return '';
        }

        $data = json_encode($payload);
        return hash_hmac('sha256', $data, $this->secret);
    }

    public function verifySignature(string $signature, array $payload): bool
    {
        if (!$this->secret) {
            return false;
        }

        return hash_equals($this->generateSignature($payload), $signature);
    }

    public function setSecretAttribute($value)
    {
        if ($value) {
            $this->attributes['secret'] = Crypt::encryptString($value);
        }
    }

    public function getSecretAttribute($value)
    {
        if ($value) {
            return Crypt::decryptString($value);
        }
        return null;
    }

    public static function getAvailableEvents()
    {
        return [
            self::EVENT_ALERT_TRIGGERED => 'Alerta Disparado',
            self::EVENT_STRATEGY_EXECUTED => 'Estratégia Executada',
            self::EVENT_PORTFOLIO_UPDATED => 'Portfólio Atualizado',
            self::EVENT_EXCHANGE_ERROR => 'Erro na Exchange'
        ];
    }
}
