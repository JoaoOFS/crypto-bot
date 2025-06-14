<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backtest Report #{{ $backtest->id }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .metric-card {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .metric-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        .chart-container {
            margin-bottom: 30px;
            padding: 20px;
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .chart-title {
            margin-bottom: 15px;
            color: #2c3e50;
            font-size: 18px;
        }
        .trades-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .trades-table th,
        .trades-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .trades-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .profit {
            color: #27ae60;
        }
        .loss {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Backtest Report #{{ $backtest->id }}</h1>
            <p>
                Strategy: {{ $backtest->tradingStrategy->name }} |
                Symbol: {{ $backtest->symbol }} |
                Timeframe: {{ $backtest->timeframe }} |
                Period: {{ $backtest->start_date->format('Y-m-d') }} to {{ $backtest->end_date->format('Y-m-d') }}
            </p>
        </div>

        <div class="metrics">
            <div class="metric-card">
                <div class="metric-value">{{ number_format($backtest->win_rate * 100, 2) }}%</div>
                <div class="metric-label">Win Rate</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{{ number_format($backtest->profit_factor, 2) }}</div>
                <div class="metric-label">Profit Factor</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{{ number_format($backtest->sharpe_ratio, 2) }}</div>
                <div class="metric-label">Sharpe Ratio</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{{ number_format($backtest->max_drawdown, 2) }}%</div>
                <div class="metric-label">Max Drawdown</div>
            </div>
        </div>

        @foreach($charts as $chart)
        <div class="chart-container">
            <div class="chart-title">{{ ucwords(str_replace('-', ' ', $chart)) }}</div>
            <canvas id="{{ $chart }}"></canvas>
        </div>
        @endforeach

        <h2>Recent Trades</h2>
        <table class="trades-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Side</th>
                    <th>Entry Price</th>
                    <th>Exit Price</th>
                    <th>P/L</th>
                    <th>P/L %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($backtest->trades()->latest()->take(10)->get() as $trade)
                <tr>
                    <td>{{ $trade->exit_time->format('Y-m-d H:i') }}</td>
                    <td>{{ $trade->side }}</td>
                    <td>{{ number_format($trade->entry_price, 2) }}</td>
                    <td>{{ number_format($trade->exit_price, 2) }}</td>
                    <td class="{{ $trade->profit_loss >= 0 ? 'profit' : 'loss' }}">
                        {{ number_format($trade->profit_loss, 2) }}
                    </td>
                    <td class="{{ $trade->profit_loss_percentage >= 0 ? 'profit' : 'loss' }}">
                        {{ number_format($trade->profit_loss_percentage, 2) }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
        // Load chart data from JSON files
        @foreach($charts as $chart)
        fetch('{{ $chart }}.json')
            .then(response => response.json())
            .then(config => {
                new Chart(
                    document.getElementById('{{ $chart }}'),
                    config
                );
            });
        @endforeach
    </script>
</body>
</html>
