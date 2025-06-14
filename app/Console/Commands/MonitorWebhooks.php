<?php

namespace App\Console\Commands;

use App\Models\Webhook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorWebhooks extends Command
{
    protected $signature = 'webhooks:monitor {--hours=24 : Número de horas para monitorar}';
    protected $description = 'Monitora o status dos webhooks ativos';

    public function handle()
    {
        $hours = $this->option('hours');
        $date = now()->subHours($hours);

        $webhooks = Webhook::where('is_active', true)
            ->where(function ($query) use ($date) {
                $query->where('last_triggered_at', '<', $date)
                    ->orWhereNull('last_triggered_at');
            })
            ->get();

        $count = $webhooks->count();

        if ($count === 0) {
            $this->info('Todos os webhooks ativos estão funcionando normalmente.');
            return 0;
        }

        $this->warn("Encontrados {$count} webhooks que não foram acionados nas últimas {$hours} horas:");

        $headers = ['ID', 'Nome', 'URL', 'Último Acionamento', 'Último Erro'];
        $rows = [];

        foreach ($webhooks as $webhook) {
            $rows[] = [
                $webhook->id,
                $webhook->name,
                $webhook->url,
                $webhook->last_triggered_at ? $webhook->last_triggered_at->format('Y-m-d H:i:s') : 'Nunca',
                $webhook->last_error ?? 'Nenhum'
            ];

            Log::warning('Webhook inativo detectado', [
                'webhook_id' => $webhook->id,
                'name' => $webhook->name,
                'last_triggered_at' => $webhook->last_triggered_at,
                'last_error' => $webhook->last_error
            ]);
        }

        $this->table($headers, $rows);

        return 1;
    }
}
