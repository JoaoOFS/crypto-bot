<?php

namespace App\Jobs;

use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $webhook;
    protected $event;
    protected $payload;
    public $tries = 3;
    public $timeout = 30;

    public function __construct(Webhook $webhook, string $event, array $payload)
    {
        $this->webhook = $webhook;
        $this->event = $event;
        $this->payload = $payload;
        $this->timeout = $webhook->timeout;
        $this->tries = $webhook->retry_count;
    }

    public function handle()
    {
        try {
            $data = [
                'event' => $this->event,
                'payload' => $this->payload,
                'timestamp' => now()->toIso8601String()
            ];

            $headers = $this->webhook->headers ?? [];
            $headers['Content-Type'] = 'application/json';

            if ($this->webhook->secret) {
                $headers['X-Webhook-Signature'] = $this->webhook->generateSignature($data);
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($this->webhook->url, $data);

            if ($response->successful()) {
                $this->webhook->update([
                    'last_triggered_at' => now()
                ]);

                Log::info('Webhook processado com sucesso', [
                    'webhook_id' => $this->webhook->id,
                    'event' => $this->event,
                    'status' => $response->status()
                ]);
            } else {
                throw new \Exception("Erro na resposta do webhook: " . $response->status());
            }
        } catch (\Exception $e) {
            $this->webhook->update([
                'last_failed_at' => now()
            ]);

            Log::error('Erro ao processar webhook', [
                'webhook_id' => $this->webhook->id,
                'event' => $this->event,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Falha ao processar webhook após todas as tentativas', [
            'webhook_id' => $this->webhook->id,
            'event' => $this->event,
            'error' => $exception->getMessage()
        ]);
    }
}
