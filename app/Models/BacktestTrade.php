<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="BacktestTrade",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="backtest_id", type="integer", example=1),
 *     @OA\Property(property="symbol", type="string", example="BTC/USDT"),
 *     @OA\Property(property="side", type="string", enum={"long", "short"}, example="long"),
 *     @OA\Property(property="entry_price", type="number", format="float", example=50000),
 *     @OA\Property(property="exit_price", type="number", format="float", example=51000),
 *     @OA\Property(property="quantity", type="number", format="float", example=0.1),
 *     @OA\Property(property="entry_time", type="string", format="date-time"),
 *     @OA\Property(property="exit_time", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="profit_loss", type="number", format="float", example=100),
 *     @OA\Property(property="profit_loss_percentage", type="number", format="float", example=2.0),
 *     @OA\Property(property="stop_loss", type="number", format="float", example=49000),
 *     @OA\Property(property="take_profit", type="number", format="float", example=52000),
 *     @OA\Property(property="trailing_stop", type="number", format="float", example=50500, nullable=true),
 *     @OA\Property(property="highest_price", type="number", format="float", example=51500),
 *     @OA\Property(property="lowest_price", type="number", format="float", example=49500),
 *     @OA\Property(property="exit_reason", type="string", example="take_profit"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="backtest",
 *         ref="#/components/schemas/Backtest"
 *     )
 * )
 */
class BacktestTrade extends Model
{
    protected $fillable = [
        'backtest_id',
        'symbol',
        'side',
        'entry_price',
        'exit_price',
        'quantity',
        'entry_time',
        'exit_time',
        'profit_loss',
        'profit_loss_percentage',
        'stop_loss',
        'take_profit',
        'trailing_stop',
        'highest_price',
        'lowest_price',
        'exit_reason',
    ];

    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
        'entry_price' => 'float',
        'exit_price' => 'float',
        'quantity' => 'float',
        'profit_loss' => 'float',
        'profit_loss_percentage' => 'float',
        'stop_loss' => 'float',
        'take_profit' => 'float',
        'trailing_stop' => 'float',
        'highest_price' => 'float',
        'lowest_price' => 'float',
    ];

    public function backtest(): BelongsTo
    {
        return $this->belongsTo(Backtest::class);
    }

    public function isLong(): bool
    {
        return $this->side === 'long';
    }

    public function isShort(): bool
    {
        return $this->side === 'short';
    }

    public function getDuration(): int
    {
        if (!$this->exit_time) {
            return 0;
        }

        return $this->entry_time->diffInMinutes($this->exit_time);
    }

    public function getRiskRewardRatio(): float
    {
        if ($this->isLong()) {
            $risk = $this->entry_price - $this->stop_loss;
            $reward = $this->take_profit - $this->entry_price;
        } else {
            $risk = $this->stop_loss - $this->entry_price;
            $reward = $this->entry_price - $this->take_profit;
        }

        return $risk > 0 ? $reward / $risk : 0;
    }

    public function getMaxAdverseExcursion(): float
    {
        if ($this->isLong()) {
            return (($this->lowest_price - $this->entry_price) / $this->entry_price) * 100;
        } else {
            return (($this->entry_price - $this->highest_price) / $this->entry_price) * 100;
        }
    }

    public function getMaxFavorableExcursion(): float
    {
        if ($this->isLong()) {
            return (($this->highest_price - $this->entry_price) / $this->entry_price) * 100;
        } else {
            return (($this->entry_price - $this->lowest_price) / $this->entry_price) * 100;
        }
    }
}
