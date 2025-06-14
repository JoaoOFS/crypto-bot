<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $webhook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'secret' => 'test-secret'
        ]);
    }

    public function test_it_can_list_webhooks()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/webhooks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'url',
                    'events',
                    'is_active',
                    'last_triggered_at',
                    'created_at'
                ]
            ])
            ->assertJsonMissing(['secret']);
    }

    public function test_it_can_create_webhook()
    {
        $data = [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => [Webhook::EVENT_ALERT_TRIGGERED],
            'is_active' => true,
            'retry_count' => 3,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'webhook' => [
                    'id',
                    'name',
                    'url',
                    'events',
                    'is_active',
                    'created_at'
                ]
            ])
            ->assertJsonMissing(['secret']);

        $this->assertDatabaseHas('webhooks', [
            'name' => $data['name'],
            'url' => $data['url'],
            'is_active' => $data['is_active']
        ]);
    }

    public function test_it_can_show_webhook()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/webhooks/{$this->webhook->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'url',
                'events',
                'is_active',
                'last_triggered_at',
                'created_at'
            ])
            ->assertJsonMissing(['secret']);
    }

    public function test_it_can_update_webhook()
    {
        $data = [
            'name' => 'Updated Webhook',
            'url' => 'https://example.com/updated-webhook',
            'events' => [Webhook::EVENT_STRATEGY_EXECUTED],
            'is_active' => false
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/webhooks/{$this->webhook->id}", $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'webhook' => [
                    'id',
                    'name',
                    'url',
                    'events',
                    'is_active',
                    'updated_at'
                ]
            ])
            ->assertJsonMissing(['secret']);

        $this->assertDatabaseHas('webhooks', [
            'id' => $this->webhook->id,
            'name' => $data['name'],
            'url' => $data['url'],
            'is_active' => $data['is_active']
        ]);
    }

    public function test_it_can_delete_webhook()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/webhooks/{$this->webhook->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Webhook removido com sucesso']);

        $this->assertDatabaseMissing('webhooks', [
            'id' => $this->webhook->id
        ]);
    }

    public function test_it_can_regenerate_webhook_secret()
    {
        $oldSecret = $this->webhook->secret;

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/webhooks/{$this->webhook->id}/regenerate-secret");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'webhook' => [
                    'id',
                    'name',
                    'url',
                    'events',
                    'is_active',
                    'updated_at'
                ]
            ])
            ->assertJsonMissing(['secret']);

        $this->webhook->refresh();
        $this->assertNotEquals($oldSecret, $this->webhook->secret);
    }

    public function test_it_cannot_access_other_users_webhooks()
    {
        $otherUser = User::factory()->create();
        $otherWebhook = Webhook::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/webhooks/{$otherWebhook->id}");

        $response->assertStatus(404);
    }

    public function test_it_validates_required_fields_on_create()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'url', 'events']);
    }

    public function test_it_validates_webhook_events()
    {
        $data = [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['invalid.event']
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/webhooks', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['events.0']);
    }
}
