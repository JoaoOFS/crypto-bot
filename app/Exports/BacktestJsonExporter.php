<?php

namespace App\Exports;

use App\Models\Backtest;

class BacktestJsonExporter implements BacktestExporterInterface
{
    public function export(Backtest $backtest): string
    {
        $data = [
            'metadata' => [
                'id' => $backtest->id,
                'strategy' => [
                    'id' => $backtest->tradingStrategy->id,
                    'name' => $backtest->tradingStrategy->name,
                    'parameters' => $backtest->parameters
                ],
                'symbol' => $backtest->symbol,
                'timeframe' => $backtest->timeframe,
                'start_date' => $backtest->start_date->format('Y-m-d H:i:s'),
                'end_date' => $backtest->end_date->format('Y-m-d H:i:s'),
                'initial_balance' => $backtest->initial_balance,
                'final_balance' => $backtest->final_balance,
                'total_return' => (($backtest->final_balance - $backtest->initial_balance) / $backtest->initial_balance) * 100,
                'win_rate' => $backtest->win_rate * 100,
                'profit_factor' => $backtest->profit_factor,
                'sharpe_ratio' => $backtest->sharpe_ratio,
                'max_drawdown' => $backtest->max_drawdown
            ],
            'trades' => $backtest->trades->map(function ($trade) {
                return [
                    'id' => $trade->id,
                    'side' => $trade->side,
                    'entry_time' => $trade->entry_time->format('Y-m-d H:i:s'),
                    'exit_time' => $trade->exit_time->format('Y-m-d H:i:s'),
                    'entry_price' => $trade->entry_price,
                    'exit_price' => $trade->exit_price,
                    'quantity' => $trade->quantity,
                    'profit_loss' => $trade->profit_loss,
                    'profit_loss_percentage' => $trade->profit_loss_percentage,
                    'duration_minutes' => $trade->getDuration(),
                    'exit_reason' => $trade->exit_reason,
                    'stop_loss' => $trade->stop_loss,
                    'take_profit' => $trade->take_profit,
                    'trailing_stop' => $trade->trailing_stop,
                    'highest_price' => $trade->highest_price,
                    'lowest_price' => $trade->lowest_price
                ];
            })->toArray(),
            'equity_curve' => $backtest->equityCurve->map(function ($point) {
                return [
                    'timestamp' => $point->timestamp->format('Y-m-d H:i:s'),
                    'equity' => $point->equity,
                    'drawdown' => $point->drawdown,
                    'drawdown_percentage' => $point->drawdown_percentage
                ];
            })->toArray()
        ];

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public function getMimeType(): string
    {
        return 'application/json';
    }

    public function getFileExtension(): string
    {
        return 'json';
    }
}
