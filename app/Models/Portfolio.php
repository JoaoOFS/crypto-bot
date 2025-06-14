<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'initial_balance',
        'current_balance',
        'rebalance_threshold',
        'allocation_targets',
        'is_active',
        'settings',
    ];

    // RELACIONAMENTOS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
