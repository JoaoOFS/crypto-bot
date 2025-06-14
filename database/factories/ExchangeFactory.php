<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Exchange;
use App\Models\Portfolio;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exchange>
 */
class ExchangeFactory extends Factory
{
    protected $model = Exchange::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'name' => $this->faker->randomElement(['Binance', 'Coinbase', 'Kraken', 'FTX', 'KuCoin']),
            'api_key' => Crypt::encryptString($this->faker->uuid),
            'api_secret' => Crypt::encryptString($this->faker->uuid),
            'is_active' => true,
            'description' => $this->faker->sentence,
            'type' => $this->faker->randomElement(['spot', 'futures']),
            'testnet' => $this->faker->boolean(20), // 20% chance de ser testnet
            'rate_limit' => $this->faker->numberBetween(1000, 5000),
            'last_sync' => $this->faker->dateTimeThisMonth(),
        ];
    }

    /**
     * Indica que a exchange está inativa.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indica que a exchange é do tipo spot.
     */
    public function spot(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'spot',
        ]);
    }

    /**
     * Indica que a exchange é do tipo futures.
     */
    public function futures(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'futures',
        ]);
    }

    /**
     * Indica que a exchange está em modo testnet.
     */
    public function testnet(): static
    {
        return $this->state(fn (array $attributes) => [
            'testnet' => true,
        ]);
    }
}
