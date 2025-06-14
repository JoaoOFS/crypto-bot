<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\PushSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_can_create_push_subscription()
    {
        $subscriptionData = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/example',
            'public_key' => 'public_key_example',
            'auth_token' => 'auth_token_example',
            'device_type' => 'web',
            'device_name' => 'Chrome Browser'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/push-subscriptions', $subscriptionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'user_id',
                'endpoint',
                'public_key',
                'auth_token',
                'device_type',
                'device_name',
                'created_at',
                'updated_at'
            ]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->user->id,
            'endpoint' => $subscriptionData['endpoint']
        ]);
    }

    public function test_cannot_create_duplicate_push_subscription()
    {
        $subscription = PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/example'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/push-subscriptions', [
            'endpoint' => $subscription->endpoint,
            'public_key' => 'new_public_key',
            'auth_token' => 'new_auth_token'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['endpoint']);
    }

    public function test_can_delete_push_subscription()
    {
        $subscription = PushSubscription::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/push-subscriptions', [
            'endpoint' => $subscription->endpoint
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Assinatura removida com sucesso']);

        $this->assertDatabaseMissing('push_subscriptions', [
            'id' => $subscription->id
        ]);
    }

    public function test_cannot_delete_nonexistent_subscription()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/push-subscriptions', [
            'endpoint' => 'nonexistent_endpoint'
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Assinatura não encontrada']);
    }

    public function test_can_update_subscription_status()
    {
        $subscription = PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/push-subscriptions/{$subscription->id}", [
            'is_active' => false
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'is_active' => false
            ]);

        $this->assertDatabaseHas('push_subscriptions', [
            'id' => $subscription->id,
            'is_active' => false
        ]);
    }

    public function test_cannot_update_other_users_subscription()
    {
        $otherUser = User::factory()->create();
        $subscription = PushSubscription::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/push-subscriptions/{$subscription->id}", [
            'is_active' => false
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Não autorizado']);
    }

    public function test_can_list_user_subscriptions()
    {
        PushSubscription::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/push-subscriptions');

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'user_id',
                    'endpoint',
                    'public_key',
                    'auth_token',
                    'device_type',
                    'device_name',
                    'is_active',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }
}
