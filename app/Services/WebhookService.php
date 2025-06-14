<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\User;
use App\Jobs\ProcessWebhook;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function createWebhook(User $user, array $data)
    {
        return Webhook::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'url' => $data['url'],
            'secret' => $data['secret'] ?? null,
            'events' => $data['events'] ?? [],
            'is_active' => $data['is_active'] ?? true,
            'retry_count' => $data['retry_count'] ?? 3,
            'timeout' => $data['timeout'] ?? 30,
            'headers' => $data['headers'] ?? []
        ]);
    }

    public function triggerEvent(string $event, array $payload)
    {
        $webhooks = Webhook::active()
            ->forEvent($event)
            ->get();

        foreach ($webhooks as $webhook) {
            ProcessWebhook::dispatch($webhook, $event, $payload)
                ->onQueue('webhooks');
        }

        Log::info('Evento disparado para webhooks', [
            'event' => $event,
            'webhooks_count' => $webhooks->count()
        ]);
    }

    public function triggerAlertEvent($alert)
    {
        $this->triggerEvent(Webhook::EVENT_ALERT_TRIGGERED, [
            'alert_id' => $alert->id,
            'type' => $alert->type,
            'condition' => $alert->condition,
            'value' => $alert->value,
            'asset' => $alert->asset ? [
                'id' => $alert->asset->id,
                'symbol' => $alert->asset->symbol,
                'name' => $alert->asset->name
            ] : null,
            'portfolio' => [
                'id' => $alert->portfolio->id,
                'name' => $alert->portfolio->name
            ]
        ]);
    }

    public function triggerStrategyEvent($strategy)
    {
        $this->triggerEvent(Webhook::EVENT_STRATEGY_EXECUTED, [
            'strategy_id' => $strategy->id,
            'name' => $strategy->name,
            'type' => $strategy->type,
            'parameters' => $strategy->parameters,
            'portfolio' => [
                'id' => $strategy->portfolio->id,
                'name' => $strategy->portfolio->name
            ]
        ]);
    }

    public function triggerPortfolioEvent($portfolio)
    {
        $this->triggerEvent(Webhook::EVENT_PORTFOLIO_UPDATED, [
            'portfolio_id' => $portfolio->id,
            'name' => $portfolio->name,
            'total_value' => $portfolio->total_value,
            'assets' => $portfolio->assets->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'symbol' => $asset->symbol,
                    'name' => $asset->name,
                    'quantity' => $asset->pivot->quantity,
                    'value' => $asset->pivot->value
                ];
            })
        ]);
    }

    public function triggerExchangeErrorEvent($exchange, $error)
    {
        $this->triggerEvent(Webhook::EVENT_EXCHANGE_ERROR, [
            'exchange_id' => $exchange->id,
            'name' => $exchange->name,
            'type' => $exchange->type,
            'error' => $error
        ]);
    }
}
