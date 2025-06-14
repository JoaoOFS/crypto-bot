<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'symbol',
        'condition',
        'value',
        'is_triggered',
        'triggered_at',
        'is_active',
        'metadata',
    ];

    // RELACIONAMENTO
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
