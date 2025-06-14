<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="EquityCurvePoint",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="backtest_id", type="integer", example=1),
 *     @OA\Property(property="timestamp", type="string", format="date-time"),
 *     @OA\Property(property="equity", type="number", format="float", example=1100.00),
 *     @OA\Property(property="drawdown", type="number", format="float", example=50.00),
 *     @OA\Property(property="drawdown_percentage", type="number", format="float", example=5.00),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="backtest",
 *         ref="#/components/schemas/Backtest"
 *     )
 * )
 */
class EquityCurvePoint extends Model
{
    protected $fillable = [
        'backtest_id',
        'timestamp',
        'equity',
        'drawdown',
        'drawdown_percentage'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'equity' => 'float',
        'drawdown' => 'float',
        'drawdown_percentage' => 'float'
    ];

    public function backtest(): BelongsTo
    {
        return $this->belongsTo(Backtest::class);
    }
}
