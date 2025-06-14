<?php

namespace App\Services;

use App\Models\TradingStrategy;
use App\Models\Trade;
use App\Models\Exchange;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TradingStrategyService
{
    protected $exchangeService;
    protected $technicalAnalysisService;

    public function __construct(
        ExchangeService $exchangeService,
        TechnicalAnalysisService $technicalAnalysisService
    ) {
        $this->exchangeService = $exchangeService;
        $this->technicalAnalysisService = $technicalAnalysisService;
    }

    public function executeStrategy(TradingStrategy $strategy): void
    {
        try {
            if (!$strategy->is_active) {
                return;
            }

            if (!$strategy->canOpenNewTrade()) {
                return;
            }

            $analysis = $this->technicalAnalysisService->analyze(
                $strategy->symbol,
                $strategy->timeframe
            );

            $signal = $this->interpretSignals($analysis['signals'], $strategy->type);

            if ($signal) {
                $this->executeTrade($strategy, $signal);
            }

            $this->manageOpenTrades($strategy);
        } catch (\Exception $e) {
            Log::error('Error executing strategy: ' . $e->getMessage(), [
                'strategy_id' => $strategy->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function interpretSignals(array $signals, string $strategyType): ?string
    {
        $bullishSignals = collect($signals)->filter(fn($s) => in_array($s['signal'], ['BULLISH', 'OVERSOLD']))->count();
        $bearishSignals = collect($signals)->filter(fn($s) => in_array($s['signal'], ['BEARISH', 'OVERBOUGHT']))->count();

        switch ($strategyType) {
            case 'trend_following':
                if ($bullishSignals > $bearishSignals) {
                    return 'long';
                } elseif ($bearishSignals > $bullishSignals) {
                    return 'short';
                }
                break;

            case 'mean_reversion':
                if ($bullishSignals > $bearishSignals) {
                    return 'short';
                } elseif ($bearishSignals > $bullishSignals) {
                    return 'long';
                }
                break;

            case 'breakout':
                // Implementar lógica específica para breakout
                break;
        }

        return null;
    }

    protected function executeTrade(TradingStrategy $strategy, string $side): void
    {
        try {
            $currentPrice = $this->exchangeService->getCurrentPrice($strategy->symbol);
            $accountBalance = $this->exchangeService->getAccountBalance($strategy->exchange_id);

            $positionSize = $strategy->calculatePositionSize($accountBalance);
            $quantity = $positionSize / $currentPrice;

            $stopLoss = $strategy->calculateStopLoss($currentPrice);
            $takeProfit = $strategy->calculateTakeProfit($currentPrice);

            $trade = Trade::create([
                'trading_strategy_id' => $strategy->id,
                'exchange_id' => $strategy->exchange_id,
                'symbol' => $strategy->symbol,
                'side' => $side,
                'type' => 'market',
                'status' => 'open',
                'entry_price' => $currentPrice,
                'quantity' => $quantity,
                'stop_loss' => $stopLoss,
                'take_profit' => $takeProfit,
                'trailing_stop' => $strategy->trailing_stop,
                'highest_price' => $currentPrice,
                'lowest_price' => $currentPrice,
                'entry_time' => now(),
            ]);

            // Executar ordem na exchange
            $this->exchangeService->placeOrder(
                $strategy->exchange_id,
                $strategy->symbol,
                $side,
                'market',
                $quantity
            );

            Log::info('Trade executed', [
                'trade_id' => $trade->id,
                'strategy_id' => $strategy->id,
                'side' => $side,
                'price' => $currentPrice,
                'quantity' => $quantity
            ]);
        } catch (\Exception $e) {
            Log::error('Error executing trade: ' . $e->getMessage(), [
                'strategy_id' => $strategy->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function manageOpenTrades(TradingStrategy $strategy): void
    {
        $openTrades = $strategy->trades()->where('status', 'open')->get();

        foreach ($openTrades as $trade) {
            try {
                $currentPrice = $this->exchangeService->getCurrentPrice($trade->symbol);

                // Atualizar preços mais altos/baixos
                if ($trade->isLong()) {
                    $trade->updateHighestPrice($currentPrice);
                } else {
                    $trade->updateLowestPrice($currentPrice);
                }

                // Atualizar P&L
                $trade->updateProfitLoss($currentPrice);

                // Verificar se deve fechar a posição
                if ($trade->shouldClose($currentPrice)) {
                    $this->closeTrade($trade, $currentPrice);
                }
            } catch (\Exception $e) {
                Log::error('Error managing trade: ' . $e->getMessage(), [
                    'trade_id' => $trade->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    protected function closeTrade(Trade $trade, float $currentPrice): void
    {
        try {
            // Executar ordem de fechamento na exchange
            $this->exchangeService->placeOrder(
                $trade->exchange_id,
                $trade->symbol,
                $trade->isLong() ? 'sell' : 'buy',
                'market',
                $trade->quantity
            );

            $trade->close($currentPrice);

            Log::info('Trade closed', [
                'trade_id' => $trade->id,
                'price' => $currentPrice,
                'profit_loss' => $trade->profit_loss
            ]);
        } catch (\Exception $e) {
            Log::error('Error closing trade: ' . $e->getMessage(), [
                'trade_id' => $trade->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getStrategyPerformance(TradingStrategy $strategy): array
    {
        $trades = $strategy->trades()->where('status', 'closed')->get();

        $totalTrades = $trades->count();
        $winningTrades = $trades->filter(fn($t) => $t->profit_loss > 0)->count();
        $losingTrades = $trades->filter(fn($t) => $t->profit_loss < 0)->count();

        $totalProfit = $trades->sum('profit_loss');
        $totalProfitPercentage = $trades->sum('profit_loss_percentage');

        $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;

        $averageWin = $winningTrades > 0 ? $trades->filter(fn($t) => $t->profit_loss > 0)->avg('profit_loss_percentage') : 0;
        $averageLoss = $losingTrades > 0 ? $trades->filter(fn($t) => $t->profit_loss < 0)->avg('profit_loss_percentage') : 0;

        return [
            'total_trades' => $totalTrades,
            'winning_trades' => $winningTrades,
            'losing_trades' => $losingTrades,
            'win_rate' => $winRate,
            'total_profit' => $totalProfit,
            'total_profit_percentage' => $totalProfitPercentage,
            'average_win' => $averageWin,
            'average_loss' => $averageLoss,
            'profit_factor' => $this->calculateProfitFactor($trades),
            'max_drawdown' => $this->calculateMaxDrawdown($trades),
        ];
    }

    protected function calculateProfitFactor($trades): float
    {
        $grossProfit = $trades->filter(fn($t) => $t->profit_loss > 0)->sum('profit_loss');
        $grossLoss = abs($trades->filter(fn($t) => $t->profit_loss < 0)->sum('profit_loss'));

        return $grossLoss > 0 ? $grossProfit / $grossLoss : 0;
    }

    protected function calculateMaxDrawdown($trades): float
    {
        $equity = 0;
        $peak = 0;
        $maxDrawdown = 0;

        foreach ($trades as $trade) {
            $equity += $trade->profit_loss;

            if ($equity > $peak) {
                $peak = $equity;
            }

            $drawdown = ($peak - $equity) / $peak;
            $maxDrawdown = max($maxDrawdown, $drawdown);
        }

        return $maxDrawdown;
    }
}
