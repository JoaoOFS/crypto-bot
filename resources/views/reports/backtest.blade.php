<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backtest Report - {{ $backtest->strategy->name }}</title>
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
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <h1 class="mb-4">Backtest Report</h1>

        <!-- Strategy Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Strategy Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Strategy:</strong> {{ $backtest->strategy->name }}
                    </div>
                    <div class="col-md-3">
                        <strong>Symbol:</strong> {{ $backtest->symbol }}
                    </div>
                    <div class="col-md-3">
                        <strong>Timeframe:</strong> {{ $backtest->timeframe }}
                    </div>
                    <div class="col-md-3">
                        <strong>Period:</strong> {{ $backtest->period }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Performance Metrics</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value {{ $performance['total_return'] >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($performance['total_return'], 2) }}
                            </div>
                            <div class="metric-label">Total Return</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value {{ $performance['return_percentage'] >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($performance['return_percentage'], 2) }}%
                            </div>
                            <div class="metric-label">Return Percentage</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value">
                                {{ number_format($performance['win_rate'], 2) }}%
                            </div>
                            <div class="metric-label">Win Rate</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value">
                                {{ number_format($performance['profit_factor'], 2) }}
                            </div>
                            <div class="metric-label">Profit Factor</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trade Statistics -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Trade Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value">
                                {{ $trades['total'] }}
                            </div>
                            <div class="metric-label">Total Trades</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value positive">
                                {{ $trades['winning'] }}
                            </div>
                            <div class="metric-label">Winning Trades</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value negative">
                                {{ $trades['losing'] }}
                            </div>
                            <div class="metric-label">Losing Trades</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value">
                                {{ number_format($trades['average_win'], 2) }}
                            </div>
                            <div class="metric-label">Average Win</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equity Curve -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Equity Curve</h5>
            </div>
            <div class="card-body">
                <canvas id="equityCurveChart"></canvas>
            </div>
        </div>

        <!-- Drawdown -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Drawdown</h5>
            </div>
            <div class="card-body">
                <canvas id="drawdownChart"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script>
        // Equity Curve Chart
        const equityCtx = document.getElementById('equityCurveChart').getContext('2d');
        new Chart(equityCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($equity_curve->pluck('timestamp')) !!},
                datasets: [{
                    label: 'Equity',
                    data: {!! json_encode($equity_curve->pluck('equity')) !!},
                    borderColor: '#198754',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Equity Curve'
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
                labels: {!! json_encode($equity_curve->pluck('timestamp')) !!},
                datasets: [{
                    label: 'Drawdown %',
                    data: {!! json_encode($equity_curve->pluck('drawdown_percentage')) !!},
                    borderColor: '#dc3545',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Drawdown Percentage'
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
