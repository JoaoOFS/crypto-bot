<?php

namespace App\Console\Commands;

use App\Models\Webhook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestWebhook extends Command
{
    protected $signature = 'webhook:test {id : ID do webhook} {--event=test.event : Evento a ser testado} {--payload= : Payload em formato JSON}';
    protected $description = 'Testa um webhook enviando um evento de teste';

    public function handle()
    {
        $webhook = Webhook::find($this->argument('id'));

        if (!$webhook) {
            $this->error('Webhook não encontrado');
            return 1;
        }

        if (!$webhook->is_active) {
            $this->error('Webhook está inativo');
            return 1;
        }

        $event = $this->option('event');
        $payload = $this->option('payload') ? json_decode($this->option('payload'), true) : ['test' => 'data'];

        if (!in_array($event, $webhook->events)) {
            $this->error('Evento não permitido para este webhook');
            return 1;
        }

        $data = [
            'event' => $event,
            'payload' => $payload,
            'timestamp' => now()->toIso8601String()
        ];

        $headers = $webhook->headers ?? [];
        $headers['Content-Type'] = 'application/json';

        if ($webhook->secret) {
            $headers['X-Webhook-Signature'] = $webhook->generateSignature($data);
        }

        $this->info('Enviando evento de teste...');
        $this->table(['Campo', 'Valor'], [
            ['Evento', $event],
            ['URL', $webhook->url],
            ['Payload', json_encode($payload, JSON_PRETTY_PRINT)],
            ['Headers', json_encode($headers, JSON_PRETTY_PRINT)]
        ]);

        try {
            $response = Http::timeout($webhook->timeout)
                ->withHeaders($headers)
                ->post($webhook->url, $data);

            if ($response->successful()) {
                $this->info('Webhook testado com sucesso!');
                $this->info('Resposta: ' . $response->body());
                return 0;
            } else {
                $this->error('Erro ao testar webhook: ' . $response->status());
                $this->error('Resposta: ' . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Erro ao testar webhook: ' . $e->getMessage());
            Log::error('Erro ao testar webhook', [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }
}
