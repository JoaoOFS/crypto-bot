<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Alert;
use App\Models\Portfolio;
use App\Models\Asset;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'asset_id' => null,
            'type' => $this->faker->randomElement(['price', 'volume', 'change', 'technical']),
            'condition' => $this->faker->randomElement(['above', 'below', 'equals', 'percent_change']),
            'value' => $this->faker->randomFloat(2, 1000, 100000),
            'description' => $this->faker->sentence,
            'is_active' => true,
            'notification_channels' => $this->faker->randomElements(['email', 'telegram'], $this->faker->numberBetween(1, 2)),
            'last_triggered' => $this->faker->optional(0.3)->dateTimeThisMonth(),
        ];
    }

    /**
     * Indica que o alerta está inativo.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indica que o alerta é do tipo preço.
     */
    public function price(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'price',
            'condition' => $this->faker->randomElement(['above', 'below']),
        ]);
    }

    /**
     * Indica que o alerta é do tipo volume.
     */
    public function volume(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'volume',
            'condition' => $this->faker->randomElement(['above', 'below']),
        ]);
    }

    /**
     * Indica que o alerta é do tipo mudança percentual.
     */
    public function change(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'change',
            'condition' => 'percent_change',
            'value' => $this->faker->randomFloat(2, 1, 50),
        ]);
    }

    /**
     * Indica que o alerta é do tipo técnico.
     */
    public function technical(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'technical',
            'condition' => $this->faker->randomElement(['above', 'below', 'equals']),
        ]);
    }

    /**
     * Indica que o alerta está vinculado a um ativo específico.
     */
    public function forAsset(Asset $asset): static
    {
        return $this->state(fn (array $attributes) => [
            'portfolio_id' => $asset->portfolio_id,
            'asset_id' => $asset->id,
        ]);
    }
}
