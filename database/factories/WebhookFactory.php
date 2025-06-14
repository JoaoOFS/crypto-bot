<?php

namespace Database\Factories;

use App\Models\Webhook;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'url' => $this->faker->url,
            'secret' => $this->faker->sha256,
            'events' => $this->faker->randomElements(array_keys(Webhook::getAvailableEvents()), 2),
            'is_active' => true,
            'retry_count' => $this->faker->numberBetween(1, 5),
            'timeout' => $this->faker->numberBetween(5, 30),
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'last_triggered_at' => $this->faker->optional()->dateTimeThisMonth(),
            'last_failed_at' => $this->faker->optional()->dateTimeThisMonth(),
            'last_error' => $this->faker->optional()->sentence
        ];
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false
            ];
        });
    }

    public function withCustomEvents(array $events)
    {
        return $this->state(function (array $attributes) use ($events) {
            return [
                'events' => $events
            ];
        });
    }

    public function withCustomHeaders(array $headers)
    {
        return $this->state(function (array $attributes) use ($headers) {
            return [
                'headers' => array_merge($attributes['headers'], $headers)
            ];
        });
    }
}
