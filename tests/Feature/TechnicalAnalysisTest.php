<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Asset;
use App\Services\TechnicalAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class TechnicalAnalysisTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $technicalAnalysisService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->technicalAnalysisService = $this->app->make(TechnicalAnalysisService::class);
    }

    public function test_can_get_technical_analysis()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/technical-analysis/BTC/USDT');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'symbol',
                'interval',
                'indicators' => [
                    'rsi' => [
                        'value',
                        'interpretation'
                    ],
                    'macd' => [
                        'macd_line',
                        'signal_line',
                        'histogram',
                        'interpretation'
                    ],
                    'sma' => [
                        'value',
                        'trend'
                    ],
                    'ema' => [
                        'value',
                        'trend'
                    ]
                ],
                'signals',
                'timestamp',
                'recommendation'
            ]);
    }

    public function test_can_get_specific_indicators()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/technical-analysis/BTC/USDT/indicators?indicators=RSI,MACD');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'RSI',
                'MACD'
            ]);
    }

    public function test_can_run_backtest()
    {
        $data = [
            'symbol' => 'BTC/USDT',
            'strategy' => 'RSI',
            'parameters' => [
                'period' => 14
            ],
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d')
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/technical-analysis/backtest', $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'strategy',
                'parameters',
                'results',
                'metrics' => [
                    'total_trades',
                    'win_rate',
                    'profit_factor',
                    'max_drawdown'
                ]
            ]);
    }

    public function test_validates_backtest_parameters()
    {
        $data = [
            'symbol' => 'BTC/USDT',
            'strategy' => 'RSI',
            'parameters' => [
                'period' => 0 // Invalid period
            ],
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->subDays(1)->format('Y-m-d') // Invalid date range
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/technical-analysis/backtest', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parameters.period', 'end_date']);
    }

    public function test_requires_authentication()
    {
        $response = $this->getJson('/api/v1/technical-analysis/BTC/USDT');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/technical-analysis/BTC/USDT/indicators');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/technical-analysis/backtest', []);
        $response->assertStatus(401);
    }

    public function test_handles_invalid_symbol()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/technical-analysis/INVALID');

        $response->assertStatus(404);
    }

    public function test_handles_invalid_indicators()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/technical-analysis/BTC/USDT/indicators?indicators=INVALID');

        $response->assertStatus(400);
    }

    public function test_handles_invalid_strategy()
    {
        $data = [
            'symbol' => 'BTC/USDT',
            'strategy' => 'INVALID',
            'parameters' => [],
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d')
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/technical-analysis/backtest', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['strategy']);
    }
}
