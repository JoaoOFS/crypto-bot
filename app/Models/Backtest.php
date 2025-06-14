<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Backtest",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="strategy_id", type="integer", example=1),
 *     @OA\Property(property="symbol", type="string", example="BTC/USDT"),
 *     @OA\Property(property="timeframe", type="string", example="1h"),
 *     @OA\Property(property="period", type="string", example="2024-01-01 to 2024-03-01"),
 *     @OA\Property(property="initial_balance", type="number", format="float", example=1000.00),
 *     @OA\Property(property="final_balance", type="number", format="float", example=1200.00),
 *     @OA\Property(property="status", type="string", enum={"pending", "running", "completed", "failed"}, example="completed"),
 *     @OA\Property(property="total_trades", type="integer", example=100),
 *     @OA\Property(property="winning_trades", type="integer", example=60),
 *     @OA\Property(property="losing_trades", type="integer", example=40),
 *     @OA\Property(property="win_rate", type="number", format="float", example=60.00),
 *     @OA\Property(property="profit_factor", type="number", format="float", example=1.5),
 *     @OA\Property(property="max_drawdown", type="number", format="float", example=10.00),
 *     @OA\Property(property="sharpe_ratio", type="number", format="float", example=1.8),
 *     @OA\Property(property="sortino_ratio", type="number", format="float", example=2.1),
 *     @OA\Property(property="error_message", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="strategy",
 *         ref="#/components/schemas/TradingStrategy"
 *     ),
 *     @OA\Property(
 *         property="trades",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Trade")
 *     ),
 *     @OA\Property(
 *         property="equity_curve",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/EquityCurvePoint")
 *     )
 * )
 */
class Backtest extends Model
{
    protected $fillable = [
        'strategy_id',
        'symbol',
        'timeframe',
        'period',
        'initial_balance',
        'final_balance',
        'status',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'win_rate',
        'profit_factor',
        'max_drawdown',
        'sharpe_ratio',
        'sortino_ratio',
        'error_message'
    ];

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(TradingStrategy::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function equityCurve(): HasMany
    {
        return $this->hasMany(EquityCurvePoint::class);
    }

    public function getNetProfit(): float
    {
        return $this->final_balance - $this->initial_balance;
    }

    public function getNetProfitPercentage(): float
    {
        return (($this->final_balance - $this->initial_balance) / $this->initial_balance) * 100;
    }

    public function getAverageWin(): float
    {
        return $this->trades()->where('profit_loss', '>', 0)->avg('profit_loss') ?? 0;
    }

    public function getAverageLoss(): float
    {
        return $this->trades()->where('profit_loss', '<', 0)->avg('profit_loss') ?? 0;
    }

    public function getLargestWin(): float
    {
        return $this->trades()->where('profit_loss', '>', 0)->max('profit_loss') ?? 0;
    }

    public function getLargestLoss(): float
    {
        return $this->trades()->where('profit_loss', '<', 0)->min('profit_loss') ?? 0;
    }

    public function getAverageTradeDuration(): float
    {
        return $this->trades()->avg(\DB::raw('TIMESTAMPDIFF(MINUTE, entry_time, exit_time)')) ?? 0;
    }

    public function getConsecutiveWins(): int
    {
        $maxConsecutiveWins = 0;
        $currentConsecutiveWins = 0;

        foreach ($this->trades()->orderBy('entry_time')->get() as $trade) {
            if ($trade->profit_loss > 0) {
                $currentConsecutiveWins++;
                $maxConsecutiveWins = max($maxConsecutiveWins, $currentConsecutiveWins);
            } else {
                $currentConsecutiveWins = 0;
            }
        }

        return $maxConsecutiveWins;
    }

    public function getConsecutiveLosses(): int
    {
        $maxConsecutiveLosses = 0;
        $currentConsecutiveLosses = 0;

        foreach ($this->trades()->orderBy('entry_time')->get() as $trade) {
            if ($trade->profit_loss < 0) {
                $currentConsecutiveLosses++;
                $maxConsecutiveLosses = max($maxConsecutiveLosses, $currentConsecutiveLosses);
            } else {
                $currentConsecutiveLosses = 0;
            }
        }

        return $maxConsecutiveLosses;
    }

    public function getMonthlyReturns(): array
    {
        $monthlyReturns = [];
        $trades = $this->trades()->orderBy('entry_time')->get();

        foreach ($trades as $trade) {
            $month = $trade->entry_time->format('Y-m');
            if (!isset($monthlyReturns[$month])) {
                $monthlyReturns[$month] = 0;
            }
            $monthlyReturns[$month] += $trade->profit_loss;
        }

        return $monthlyReturns;
    }

    public function getDrawdowns(): array
    {
        $drawdowns = [];
        $peak = $this->initial_balance;
        $currentDrawdown = 0;

        foreach ($this->equityCurve()->orderBy('timestamp')->get() as $point) {
            if ($point->equity > $peak) {
                $peak = $point->equity;
            }

            $drawdown = ($peak - $point->equity) / $peak;
            $currentDrawdown = max($currentDrawdown, $drawdown);

            if ($point->equity >= $peak) {
                if ($currentDrawdown > 0) {
                    $drawdowns[] = $currentDrawdown;
                }
                $currentDrawdown = 0;
            }
        }

        return $drawdowns;
    }
}
