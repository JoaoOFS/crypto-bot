<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="TradingStrategy",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="RSI Strategy"),
 *     @OA\Property(property="description", type="string", example="Strategy based on RSI indicator"),
 *     @OA\Property(property="parameters", type="object",
 *         @OA\Property(property="rsi_period", type="integer", example=14),
 *         @OA\Property(property="overbought", type="integer", example=70),
 *         @OA\Property(property="oversold", type="integer", example=30)
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="backtests",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Backtest")
 *     )
 * )
 */
class TradingStrategy extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'parameters',
        'is_active',
        'user_id',
        'exchange_id',
        'symbol',
        'timeframe',
        'risk_percentage',
        'max_open_trades',
        'stop_loss_percentage',
        'take_profit_percentage',
        'trailing_stop',
        'trailing_stop_activation',
        'trailing_stop_distance',
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
        'trailing_stop' => 'boolean',
        'risk_percentage' => 'float',
        'stop_loss_percentage' => 'float',
        'take_profit_percentage' => 'float',
        'trailing_stop_activation' => 'float',
        'trailing_stop_distance' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function backtests(): HasMany
    {
        return $this->hasMany(Backtest::class);
    }

    public function getActiveTradesCount(): int
    {
        return $this->trades()->where('status', 'open')->count();
    }

    public function canOpenNewTrade(): bool
    {
        return $this->getActiveTradesCount() < $this->max_open_trades;
    }

    public function calculatePositionSize(float $accountBalance): float
    {
        return ($accountBalance * $this->risk_percentage) / 100;
    }

    public function calculateStopLoss(float $entryPrice): float
    {
        return $entryPrice * (1 - ($this->stop_loss_percentage / 100));
    }

    public function calculateTakeProfit(float $entryPrice): float
    {
        return $entryPrice * (1 + ($this->take_profit_percentage / 100));
    }

    public function shouldActivateTrailingStop(float $currentPrice, float $highestPrice): bool
    {
        if (!$this->trailing_stop) {
            return false;
        }

        $activationPrice = $highestPrice * (1 - ($this->trailing_stop_activation / 100));
        return $currentPrice >= $activationPrice;
    }

    public function calculateTrailingStop(float $highestPrice): float
    {
        return $highestPrice * (1 - ($this->trailing_stop_distance / 100));
    }
}
