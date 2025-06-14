<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'portfolio_id',
        'asset_id',
        'type',
        'quantity',
        'price',
        'total',
        'status',
        'exchange_order_id',
        'metadata',
    ];

    // RELACIONAMENTOS
    public function portfolio()
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
