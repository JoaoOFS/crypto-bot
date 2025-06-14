<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="BacktestMetadata",
 *     type="object",
 *     @OA\Property(property="strategy", type="string", example="RSI Strategy"),
 *     @OA\Property(property="symbol", type="string", example="BTC/USDT"),
 *     @OA\Property(property="timeframe", type="string", example="1h"),
 *     @OA\Property(property="period", type="string", example="2024-01-01 to 2024-03-01"),
 *     @OA\Property(property="initial_balance", type="number", format="float", example=1000.00)
 * )
 */
class BacktestMetadata extends Model
{
    protected $fillable = [
        'strategy',
        'symbol',
        'timeframe',
        'period',
        'initial_balance'
    ];

    protected $casts = [
        'initial_balance' => 'float'
    ];

    public function backtest(): BelongsTo
    {
        return $this->belongsTo(Backtest::class);
    }
}
