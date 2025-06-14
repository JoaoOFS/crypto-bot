<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backtest Comparison Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css" rel="stylesheet">
    <style>
        .card {
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .metric-card {
            text-align: center;
            padding: 1rem;
        }
        .metric-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .metric-label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        .positive {
            color: #198754;
        }
        .negative {
            color: #dc3545;
        }
        .table th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <h1 class="mb-4">Backtest Comparison Report</h1>

        <!-- Metadata Comparison -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Strategy Information</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Strategy</th>
                                <th>Symbol</th>
                                <th>Timeframe</th>
                                <th>Period</th>
                                <th>Initial Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($metadata as $backtest)
                            <tr>
                                <td>{{ $backtest['strategy'] }}</td>
                                <td>{{ $backtest['symbol'] }}</td>
                                <td>{{ $backtest['timeframe'] }}</td>
                                <td>{{ $backtest['period'] }}</td>
                                <td>{{ number_format($backtest['initial_balance'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Performance Comparison -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Performance Comparison</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                @foreach($metadata as $backtest)
                                <th>{{ $backtest['strategy'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($performance as $metric => $values)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $metric)) }}</td>
                                @foreach($values as $value)
                                <td class="{{ $value >= 0 ? 'positive' : 'negative' }}">
                                    {{ is_numeric($value) ? number_format($value, 2) : $value }}
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Trade Statistics Comparison -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Trade Statistics Comparison</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Statistic</th>
                                @foreach($metadata as $backtest)
                                <th>{{ $backtest['strategy'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trades as $stat => $values)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $stat)) }}</td>
                                @foreach($values as $value)
                                <td>{{ is_numeric($value) ? number_format($value, 2) : $value }}</td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Equity Curves Comparison -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Equity Curves Comparison</h5>
            </div>
            <div class="card-body">
                <canvas id="equityCurvesChart"></canvas>
            </div>
        </div>

        <!-- Drawdown Comparison -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Drawdown Comparison</h5>
            </div>
            <div class="card-body">
                <canvas id="drawdownChart"></canvas>
            </div>
        </div>

        <!-- Correlation Matrix -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Correlation Matrix</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Strategy</th>
                                <th>Correlation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($correlation as $strategy => $value)
                            <tr>
                                <td>{{ $strategy }}</td>
                                <td>{{ number_format($value, 4) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script>
        // Equity Curves Chart
        const equityCtx = document.getElementById('equityCurvesChart').getContext('2d');
        new Chart(equityCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($equity_curves[0]['points']->pluck('timestamp')) !!},
                datasets: [
                    @foreach($equity_curves as $index => $curve)
                    {
                        label: '{{ $metadata[$index]['strategy'] }}',
                        data: {!! json_encode($curve['points']->pluck('equity')) !!},
                        borderColor: `hsl(${($index * 360) / {{ count($equity_curves) }}}, 70%, 50%)`,
                        tension: 0.1
                    },
                    @endforeach
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Equity Curves Comparison'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });

        // Drawdown Chart
        const drawdownCtx = document.getElementById('drawdownChart').getContext('2d');
        new Chart(drawdownCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($equity_curves[0]['points']->pluck('timestamp')) !!},
                datasets: [
                    @foreach($equity_curves as $index => $curve)
                    {
                        label: '{{ $metadata[$index]['strategy'] }}',
                        data: {!! json_encode($curve['points']->pluck('drawdown_percentage')) !!},
                        borderColor: `hsl(${($index * 360) / {{ count($equity_curves) }}}, 70%, 50%)`,
                        tension: 0.1
                    },
                    @endforeach
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Drawdown Comparison'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        reverse: true
                    }
                }
            }
        });
    </script>
</body>
</html>
