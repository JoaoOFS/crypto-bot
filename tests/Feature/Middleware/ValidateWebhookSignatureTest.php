<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidateWebhookSignatureTest extends TestCase
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

    public function test_it_validates_valid_signature()
    {
        $payload = [
            'event' => 'test.event',
            'payload' => ['test' => 'data']
        ];

        $signature = $this->webhook->generateSignature($payload);

        $response = $this->postJson("/api/v1/webhooks/{$this->webhook->id}/events", $payload, [
            'X-Webhook-Signature' => $signature
        ]);

        $response->assertStatus(200);
    }

    public function test_it_rejects_invalid_signature()
    {
        $payload = [
            'event' => 'test.event',
            'payload' => ['test' => 'data']
        ];

        $response = $this->postJson("/api/v1/webhooks/{$this->webhook->id}/events", $payload, [
            'X-Webhook-Signature' => 'invalid-signature'
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Assinatura inválida']);
    }

    public function test_it_rejects_missing_signature()
    {
        $payload = [
            'event' => 'test.event',
            'payload' => ['test' => 'data']
        ];

        $response = $this->postJson("/api/v1/webhooks/{$this->webhook->id}/events", $payload);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Assinatura ou ID do webhook não fornecidos']);
    }

    public function test_it_rejects_invalid_webhook_id()
    {
        $payload = [
            'event' => 'test.event',
            'payload' => ['test' => 'data']
        ];

        $signature = $this->webhook->generateSignature($payload);

        $response = $this->postJson("/api/v1/webhooks/999/events", $payload, [
            'X-Webhook-Signature' => $signature
        ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'Webhook não encontrado ou sem segredo configurado']);
    }

    public function test_it_rejects_webhook_without_secret()
    {
        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'secret' => null
        ]);

        $payload = [
            'event' => 'test.event',
            'payload' => ['test' => 'data']
        ];

        $response = $this->postJson("/api/v1/webhooks/{$webhook->id}/events", $payload, [
            'X-Webhook-Signature' => 'any-signature'
        ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'Webhook não encontrado ou sem segredo configurado']);
    }
}
