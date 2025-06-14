<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="EquityCurve",
 *     type="object",
 *     @OA\Property(property="points", type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="timestamp", type="string", format="date-time"),
 *             @OA\Property(property="equity", type="number", format="float"),
 *             @OA\Property(property="drawdown", type="number", format="float"),
 *             @OA\Property(property="drawdown_percentage", type="number", format="float")
 *         )
 *     )
 * )
 */
class EquityCurve extends Model
{
    protected $fillable = [
        'backtest_id',
        'points'
    ];

    protected $casts = [
        'points' => 'array'
    ];

    public function backtest(): BelongsTo
    {
        return $this->belongsTo(Backtest::class);
    }
}
