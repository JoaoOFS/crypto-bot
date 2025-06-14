<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Strategy extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'type',
        'parameters',
        'indicators',
        'risk_level',
        'is_active',
        'performance_metrics',
    ];

    // RELACIONAMENTO
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
