<?php

namespace App\Exports;

use App\Models\Backtest;
use League\Csv\Writer;

class BacktestCsvExporter implements BacktestExporterInterface
{
    public function export(Backtest $backtest): string
    {
        $csv = Writer::createFromString('');

        // Add backtest metadata
        $csv->insertOne([
            'Backtest ID',
            'Strategy',
            'Symbol',
            'Timeframe',
            'Start Date',
            'End Date',
            'Initial Balance',
            'Final Balance',
            'Total Return',
            'Win Rate',
            'Profit Factor',
            'Sharpe Ratio',
            'Max Drawdown'
        ]);

        $csv->insertOne([
            $backtest->id,
            $backtest->tradingStrategy->name,
            $backtest->symbol,
            $backtest->timeframe,
            $backtest->start_date->format('Y-m-d H:i:s'),
            $backtest->end_date->format('Y-m-d H:i:s'),
            $backtest->initial_balance,
            $backtest->final_balance,
            (($backtest->final_balance - $backtest->initial_balance) / $backtest->initial_balance) * 100,
            $backtest->win_rate * 100,
            $backtest->profit_factor,
            $backtest->sharpe_ratio,
            $backtest->max_drawdown
        ]);

        // Add trades
        $csv->insertOne([]);
        $csv->insertOne([
            'Trade ID',
            'Side',
            'Entry Time',
            'Exit Time',
            'Entry Price',
            'Exit Price',
            'Quantity',
            'Profit/Loss',
            'Profit/Loss %',
            'Duration (minutes)',
            'Exit Reason'
        ]);

        foreach ($backtest->trades as $trade) {
            $csv->insertOne([
                $trade->id,
                $trade->side,
                $trade->entry_time->format('Y-m-d H:i:s'),
                $trade->exit_time->format('Y-m-d H:i:s'),
                $trade->entry_price,
                $trade->exit_price,
                $trade->quantity,
                $trade->profit_loss,
                $trade->profit_loss_percentage,
                $trade->getDuration(),
                $trade->exit_reason
            ]);
        }

        // Add equity curve
        $csv->insertOne([]);
        $csv->insertOne([
            'Timestamp',
            'Equity',
            'Drawdown',
            'Drawdown %'
        ]);

        foreach ($backtest->equityCurve as $point) {
            $csv->insertOne([
                $point->timestamp->format('Y-m-d H:i:s'),
                $point->equity,
                $point->drawdown,
                $point->drawdown_percentage
            ]);
        }

        return $csv->toString();
    }

    public function getMimeType(): string
    {
        return 'text/csv';
    }

    public function getFileExtension(): string
    {
        return 'csv';
    }
}
