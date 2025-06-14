<?php

namespace App\Console\Commands;

use App\Models\Backtest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class VisualizeBacktestResults extends Command
{
    protected $signature = 'backtest:visualize {id} {--format=html} {--output=storage/app/public/backtest-reports}';
    protected $description = 'Generate visual representations of backtest results';

    public function handle()
    {
        $backtest = Backtest::findOrFail($this->argument('id'));

        if ($backtest->status !== 'completed') {
            $this->error('Backtest must be completed to generate visualizations.');
            return 1;
        }

        $this->info("Generating visualizations for backtest #{$backtest->id}...");

        // Create output directory if it doesn't exist
        $outputDir = $this->option('output');
        if (!Storage::exists($outputDir)) {
            Storage::makeDirectory($outputDir);
        }

        // Generate visualizations
        $this->generateEquityCurve($backtest, $outputDir);
        $this->generateTradeDistribution($backtest, $outputDir);
        $this->generateMonthlyReturns($backtest, $outputDir);
        $this->generateDrawdownChart($backtest, $outputDir);

        // Generate HTML report if requested
        if ($this->option('format') === 'html') {
            $this->generateHtmlReport($backtest, $outputDir);
        }

        $this->info("\nVisualizations generated successfully!");
        $this->info("Output directory: {$outputDir}");

        return 0;
    }

    private function generateEquityCurve(Backtest $backtest, string $outputDir): void
    {
        $equityCurve = $backtest->equityCurve()
            ->orderBy('timestamp')
            ->get();

        $data = [
            'labels' => $equityCurve->pluck('timestamp')->map(function ($date) {
                return $date->format('Y-m-d H:i');
            })->toArray(),
            'equity' => $equityCurve->pluck('equity')->toArray(),
            'drawdown' => $equityCurve->pluck('drawdown_percentage')->toArray()
        ];

        $this->generateChart(
            'equity-curve',
            'Equity Curve',
            $data,
            $outputDir
        );
    }

    private function generateTradeDistribution(Backtest $backtest, string $outputDir): void
    {
        $trades = $backtest->trades;

        $data = [
            'labels' => ['Winning Trades', 'Losing Trades'],
            'values' => [
                $trades->where('profit_loss', '>', 0)->count(),
                $trades->where('profit_loss', '<', 0)->count()
            ]
        ];

        $this->generateChart(
            'trade-distribution',
            'Trade Distribution',
            $data,
            $outputDir
        );
    }

    private function generateMonthlyReturns(Backtest $backtest, string $outputDir): void
    {
        $trades = $backtest->trades;
        $monthlyReturns = [];

        foreach ($trades as $trade) {
            $month = $trade->exit_time->format('Y-m');
            if (!isset($monthlyReturns[$month])) {
                $monthlyReturns[$month] = 0;
            }
            $monthlyReturns[$month] += $trade->profit_loss_percentage;
        }

        $data = [
            'labels' => array_keys($monthlyReturns),
            'values' => array_values($monthlyReturns)
        ];

        $this->generateChart(
            'monthly-returns',
            'Monthly Returns',
            $data,
            $outputDir
        );
    }

    private function generateDrawdownChart(Backtest $backtest, string $outputDir): void
    {
        $equityCurve = $backtest->equityCurve()
            ->orderBy('timestamp')
            ->get();

        $data = [
            'labels' => $equityCurve->pluck('timestamp')->map(function ($date) {
                return $date->format('Y-m-d H:i');
            })->toArray(),
            'drawdown' => $equityCurve->pluck('drawdown_percentage')->toArray()
        ];

        $this->generateChart(
            'drawdown-chart',
            'Drawdown Analysis',
            $data,
            $outputDir
        );
    }

    private function generateChart(string $type, string $title, array $data, string $outputDir): void
    {
        $chartConfig = [
            'type' => $this->getChartType($type),
            'data' => [
                'labels' => $data['labels'],
                'datasets' => $this->getChartDatasets($type, $data)
            ],
            'options' => $this->getChartOptions($type, $title)
        ];

        $filename = "{$outputDir}/{$type}.json";
        Storage::put($filename, json_encode($chartConfig, JSON_PRETTY_PRINT));
    }

    private function getChartType(string $type): string
    {
        return match ($type) {
            'equity-curve' => 'line',
            'trade-distribution' => 'pie',
            'monthly-returns' => 'bar',
            'drawdown-chart' => 'line',
            default => 'line'
        };
    }

    private function getChartDatasets(string $type, array $data): array
    {
        return match ($type) {
            'equity-curve' => [
                [
                    'label' => 'Equity',
                    'data' => $data['equity'],
                    'borderColor' => 'rgb(75, 192, 192)',
                    'fill' => false
                ],
                [
                    'label' => 'Drawdown',
                    'data' => $data['drawdown'],
                    'borderColor' => 'rgb(255, 99, 132)',
                    'fill' => false
                ]
            ],
            'trade-distribution' => [
                [
                    'data' => $data['values'],
                    'backgroundColor' => ['rgb(75, 192, 192)', 'rgb(255, 99, 132)']
                ]
            ],
            'monthly-returns' => [
                [
                    'label' => 'Monthly Returns',
                    'data' => $data['values'],
                    'backgroundColor' => 'rgb(75, 192, 192)'
                ]
            ],
            'drawdown-chart' => [
                [
                    'label' => 'Drawdown',
                    'data' => $data['drawdown'],
                    'borderColor' => 'rgb(255, 99, 132)',
                    'fill' => true,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)'
                ]
            ],
            default => []
        };
    }

    private function getChartOptions(string $type, string $title): array
    {
        $baseOptions = [
            'responsive' => true,
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => $title
                ]
            ]
        ];

        return match ($type) {
            'equity-curve' => array_merge($baseOptions, [
                'scales' => [
                    'y' => [
                        'beginAtZero' => false
                    ]
                ]
            ]),
            'monthly-returns' => array_merge($baseOptions, [
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'callback' => 'function(value) { return value + "%"; }'
                        ]
                    ]
                ]
            ]),
            'drawdown-chart' => array_merge($baseOptions, [
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'callback' => 'function(value) { return value + "%"; }'
                        ]
                    ]
                ]
            ]),
            default => $baseOptions
        };
    }

    private function generateHtmlReport(Backtest $backtest, string $outputDir): void
    {
        $html = view('backtest.report', [
            'backtest' => $backtest,
            'charts' => [
                'equity-curve',
                'trade-distribution',
                'monthly-returns',
                'drawdown-chart'
            ]
        ])->render();

        Storage::put("{$outputDir}/report.html", $html);
    }
}
