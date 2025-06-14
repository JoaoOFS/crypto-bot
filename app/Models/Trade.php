<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Trade",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="backtest_id", type="integer", example=1),
 *     @OA\Property(property="entry_time", type="string", format="date-time"),
 *     @OA\Property(property="exit_time", type="string", format="date-time"),
 *     @OA\Property(property="entry_price", type="number", format="float", example=50000.00),
 *     @OA\Property(property="exit_price", type="number", format="float", example=51000.00),
 *     @OA\Property(property="position_size", type="number", format="float", example=0.1),
 *     @OA\Property(property="profit", type="number", format="float", example=100.00),
 *     @OA\Property(property="profit_percentage", type="number", format="float", example=2.00),
 *     @OA\Property(property="type", type="string", enum={"long", "short"}, example="long"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="backtest",
 *         ref="#/components/schemas/Backtest"
 *     )
 * )
 */
class Trade extends Model
{
    protected $fillable = [
        'backtest_id',
        'entry_time',
        'exit_time',
        'entry_price',
        'exit_price',
        'position_size',
        'profit',
        'profit_percentage',
        'type'
    ];

    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
        'entry_price' => 'float',
        'exit_price' => 'float',
        'position_size' => 'float',
        'profit' => 'float',
        'profit_percentage' => 'float'
    ];

    public function backtest(): BelongsTo
    {
        return $this->belongsTo(Backtest::class);
    }

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(TradingStrategy::class, 'trading_strategy_id');
    }

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isLong(): bool
    {
        return $this->side === 'long';
    }

    public function isShort(): bool
    {
        return $this->side === 'short';
    }

    public function updateProfitLoss(float $currentPrice): void
    {
        if ($this->isLong()) {
            $this->profit_loss = ($currentPrice - $this->entry_price) * $this->quantity;
            $this->profit_loss_percentage = (($currentPrice - $this->entry_price) / $this->entry_price) * 100;
        } else {
            $this->profit_loss = ($this->entry_price - $currentPrice) * $this->quantity;
            $this->profit_loss_percentage = (($this->entry_price - $currentPrice) / $this->entry_price) * 100;
        }

        $this->save();
    }

    public function updateHighestPrice(float $price): void
    {
        if ($price > $this->highest_price) {
            $this->highest_price = $price;
            $this->save();
        }
    }

    public function updateLowestPrice(float $price): void
    {
        if ($price < $this->lowest_price) {
            $this->lowest_price = $price;
            $this->save();
        }
    }

    public function shouldClose(float $currentPrice): bool
    {
        if ($this->isLong()) {
            // Check stop loss
            if ($currentPrice <= $this->stop_loss) {
                return true;
            }

            // Check take profit
            if ($currentPrice >= $this->take_profit) {
                return true;
            }

            // Check trailing stop
            if ($this->trailing_stop && $this->highest_price > 0) {
                $trailingStopPrice = $this->strategy->calculateTrailingStop($this->highest_price);
                if ($currentPrice <= $trailingStopPrice) {
                    return true;
                }
            }
        } else {
            // Check stop loss
            if ($currentPrice >= $this->stop_loss) {
                return true;
            }

            // Check take profit
            if ($currentPrice <= $this->take_profit) {
                return true;
            }

            // Check trailing stop
            if ($this->trailing_stop && $this->lowest_price > 0) {
                $trailingStopPrice = $this->strategy->calculateTrailingStop($this->lowest_price);
                if ($currentPrice >= $trailingStopPrice) {
                    return true;
                }
            }
        }

        return false;
    }

    public function close(float $exitPrice): void
    {
        $this->exit_price = $exitPrice;
        $this->exit_time = now();
        $this->status = 'closed';
        $this->updateProfitLoss($exitPrice);
        $this->save();
    }
}
