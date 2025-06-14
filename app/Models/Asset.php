<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'portfolio_id',
        'symbol',
        'quantity',
        'average_price',
        'current_price',
        'total_value',
        'allocation_percentage',
        'metadata',
    ];

    // RELACIONAMENTOS
    public function portfolio()
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
