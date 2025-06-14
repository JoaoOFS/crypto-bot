<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Strategy;
use App\Models\Portfolio;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Strategy>
 */
class StrategyFactory extends Factory
{
    protected $model = Strategy::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['technical', 'fundamental', 'sentiment', 'hybrid']),
            'parameters' => $this->getDefaultParameters(),
            'description' => $this->faker->sentence,
            'is_active' => true,
            'last_executed' => $this->faker->optional(0.3)->dateTimeThisMonth(),
        ];
    }

    /**
     * Retorna parâmetros padrão baseados no tipo de estratégia.
     */
    private function getDefaultParameters(): array
    {
        $type = $this->faker->randomElement(['technical', 'fundamental', 'sentiment', 'hybrid']);

        return match($type) {
            'technical' => [
                'timeframe' => $this->faker->randomElement(['1m', '5m', '15m', '1h', '4h', '1d']),
                'indicators' => $this->faker->randomElements(['SMA', 'EMA', 'RSI', 'MACD', 'Bollinger Bands'], 2),
                'entry_rules' => [
                    'condition' => $this->faker->randomElement(['above', 'below', 'crosses']),
                    'value' => $this->faker->randomFloat(2, 10, 100)
                ],
                'exit_rules' => [
                    'stop_loss' => $this->faker->randomFloat(2, 1, 10),
                    'take_profit' => $this->faker->randomFloat(2, 10, 50)
                ]
            ],
            'fundamental' => [
                'market_cap_min' => $this->faker->numberBetween(1000000, 1000000000),
                'volume_24h_min' => $this->faker->numberBetween(100000, 10000000),
                'metrics' => [
                    'pe_ratio' => $this->faker->randomFloat(2, 5, 50),
                    'market_cap_to_volume' => $this->faker->randomFloat(2, 1, 20)
                ]
            ],
            'sentiment' => [
                'sources' => $this->faker->randomElements(['twitter', 'reddit', 'news', 'social_media'], 2),
                'sentiment_threshold' => $this->faker->randomFloat(2, -1, 1),
                'volume_threshold' => $this->faker->numberBetween(100, 1000)
            ],
            'hybrid' => [
                'technical_weight' => $this->faker->randomFloat(2, 0.1, 0.9),
                'fundamental_weight' => $this->faker->randomFloat(2, 0.1, 0.9),
                'sentiment_weight' => $this->faker->randomFloat(2, 0.1, 0.9),
                'timeframe' => $this->faker->randomElement(['1h', '4h', '1d']),
                'indicators' => ['RSI', 'MACD']
            ],
            default => []
        };
    }

    /**
     * Indica que a estratégia está inativa.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indica que a estratégia é do tipo técnico.
     */
    public function technical(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'technical',
            'parameters' => $this->getDefaultParameters('technical'),
        ]);
    }

    /**
     * Indica que a estratégia é do tipo fundamental.
     */
    public function fundamental(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fundamental',
            'parameters' => $this->getDefaultParameters('fundamental'),
        ]);
    }

    /**
     * Indica que a estratégia é do tipo sentimento.
     */
    public function sentiment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sentiment',
            'parameters' => $this->getDefaultParameters('sentiment'),
        ]);
    }

    /**
     * Indica que a estratégia é do tipo híbrido.
     */
    public function hybrid(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'hybrid',
            'parameters' => $this->getDefaultParameters('hybrid'),
        ]);
    }
}
