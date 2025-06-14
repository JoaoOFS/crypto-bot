<?php

namespace App\Console\Commands;

use App\Models\Backtest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateBacktestReport extends Command
{
    protected $signature = 'backtest:report {id : The ID of the backtest} {--format=csv : Report format (csv or json)}';
    protected $description = 'Generate a backtest report';

    public function handle()
    {
        $id = $this->argument('id');
        $format = $this->option('format');

        $backtest = Backtest::with(['tradingStrategy', 'exchange', 'trades', 'equityCurve'])
            ->findOrFail($id);

        $this->info("Generating report for backtest #{$backtest->id}...");

        $report = [
            'backtest' => [
                'id' => $backtest->id,
                'strategy' => $backtest->tradingStrategy->name,
                'exchange' => $backtest->exchange->name,
                'symbol' => $backtest->symbol,
                'timeframe' => $backtest->timeframe,
                'period' => [
                    'start' => $backtest->start_date->format('Y-m-d H:i:s'),
                    'end' => $backtest->end_date->format('Y-m-d H:i:s'),
                ],
                'initial_balance' => $backtest->initial_balance,
                'final_balance' => $backtest->final_balance,
                'net_profit' => $backtest->final_balance - $backtest->initial_balance,
                'net_profit_percentage' => (($backtest->final_balance - $backtest->initial_balance) / $backtest->initial_balance) * 100,
            ],
            'performance' => [
                'total_trades' => $backtest->total_trades,
                'winning_trades' => $backtest->winning_trades,
                'losing_trades' => $backtest->losing_trades,
                'win_rate' => $backtest->win_rate,
                'profit_factor' => $backtest->profit_factor,
                'max_drawdown' => $backtest->max_drawdown,
                'sharpe_ratio' => $backtest->sharpe_ratio,
                'sortino_ratio' => $backtest->sortino_ratio,
            ],
            'trades' => $backtest->trades->map(function ($trade) {
                return [
                    'id' => $trade->id,
                    'side' => $trade->side,
                    'entry_price' => $trade->entry_price,
                    'exit_price' => $trade->exit_price,
                    'quantity' => $trade->quantity,
                    'entry_time' => $trade->entry_time->format('Y-m-d H:i:s'),
                    'exit_time' => $trade->exit_time->format('Y-m-d H:i:s'),
                    'profit_loss' => $trade->profit_loss,
                    'profit_loss_percentage' => $trade->profit_loss_percentage,
                    'stop_loss' => $trade->stop_loss,
                    'take_profit' => $trade->take_profit,
                    'trailing_stop' => $trade->trailing_stop,
                    'exit_reason' => $trade->exit_reason,
                ];
            }),
            'equity_curve' => $backtest->equityCurve->map(function ($point) {
                return [
                    'timestamp' => $point->timestamp->format('Y-m-d H:i:s'),
                    'equity' => $point->equity,
                    'drawdown' => $point->drawdown,
                    'drawdown_percentage' => $point->drawdown_percentage,
                ];
            }),
        ];

        $filename = "backtest_{$backtest->id}_report." . $format;
        $path = "reports/{$filename}";

        if ($format === 'csv') {
            $this->generateCsvReport($report, $path);
        } else {
            $this->generateJsonReport($report, $path);
        }

        $this->info("Report generated successfully: {$path}");

        return 0;
    }

    protected function generateCsvReport(array $report, string $path): void
    {
        $content = "Backtest Report\n\n";
        $content .= "Backtest Information\n";
        $content .= "ID,{$report['backtest']['id']}\n";
        $content .= "Strategy,{$report['backtest']['strategy']}\n";
        $content .= "Exchange,{$report['backtest']['exchange']}\n";
        $content .= "Symbol,{$report['backtest']['symbol']}\n";
        $content .= "Timeframe,{$report['backtest']['timeframe']}\n";
        $content .= "Start Date,{$report['backtest']['period']['start']}\n";
        $content .= "End Date,{$report['backtest']['period']['end']}\n";
        $content .= "Initial Balance,{$report['backtest']['initial_balance']}\n";
        $content .= "Final Balance,{$report['backtest']['final_balance']}\n";
        $content .= "Net Profit,{$report['backtest']['net_profit']}\n";
        $content .= "Net Profit Percentage,{$report['backtest']['net_profit_percentage']}%\n\n";

        $content .= "Performance Metrics\n";
        $content .= "Total Trades,{$report['performance']['total_trades']}\n";
        $content .= "Winning Trades,{$report['performance']['winning_trades']}\n";
        $content .= "Losing Trades,{$report['performance']['losing_trades']}\n";
        $content .= "Win Rate,{$report['performance']['win_rate']}%\n";
        $content .= "Profit Factor,{$report['performance']['profit_factor']}\n";
        $content .= "Max Drawdown,{$report['performance']['max_drawdown']}%\n";
        $content .= "Sharpe Ratio,{$report['performance']['sharpe_ratio']}\n";
        $content .= "Sortino Ratio,{$report['performance']['sortino_ratio']}\n\n";

        $content .= "Trades\n";
        $content .= "ID,Side,Entry Price,Exit Price,Quantity,Entry Time,Exit Time,Profit/Loss,Profit/Loss %,Stop Loss,Take Profit,Trailing Stop,Exit Reason\n";
        foreach ($report['trades'] as $trade) {
            $content .= implode(',', [
                $trade['id'],
                $trade['side'],
                $trade['entry_price'],
                $trade['exit_price'],
                $trade['quantity'],
                $trade['entry_time'],
                $trade['exit_time'],
                $trade['profit_loss'],
                $trade['profit_loss_percentage'],
                $trade['stop_loss'],
                $trade['take_profit'],
                $trade['trailing_stop'] ?? 'N/A',
                $trade['exit_reason'],
            ]) . "\n";
        }
        $content .= "\n";

        $content .= "Equity Curve\n";
        $content .= "Timestamp,Equity,Drawdown,Drawdown %\n";
        foreach ($report['equity_curve'] as $point) {
            $content .= implode(',', [
                $point['timestamp'],
                $point['equity'],
                $point['drawdown'],
                $point['drawdown_percentage'],
            ]) . "\n";
        }

        Storage::put($path, $content);
    }

    protected function generateJsonReport(array $report, string $path): void
    {
        Storage::put($path, json_encode($report, JSON_PRETTY_PRINT));
    }
}
