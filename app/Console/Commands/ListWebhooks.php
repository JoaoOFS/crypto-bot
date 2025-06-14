<?php

namespace App\Console\Commands;

use App\Models\Webhook;
use Illuminate\Console\Command;

class ListWebhooks extends Command
{
    protected $signature = 'webhooks:list {--user= : ID do usuário} {--active : Mostrar apenas webhooks ativos} {--inactive : Mostrar apenas webhooks inativos}';
    protected $description = 'Lista todos os webhooks';

    public function handle()
    {
        $query = Webhook::query();

        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        if ($this->option('active')) {
            $query->where('is_active', true);
        } elseif ($this->option('inactive')) {
            $query->where('is_active', false);
        }

        $webhooks = $query->get();

        if ($webhooks->isEmpty()) {
            $this->info('Nenhum webhook encontrado.');
            return 0;
        }

        $headers = ['ID', 'Nome', 'URL', 'Eventos', 'Status', 'Último Acionamento', 'Último Erro'];
        $rows = [];

        foreach ($webhooks as $webhook) {
            $rows[] = [
                $webhook->id,
                $webhook->name,
                $webhook->url,
                implode(', ', $webhook->events),
                $webhook->is_active ? 'Ativo' : 'Inativo',
                $webhook->last_triggered_at ? $webhook->last_triggered_at->format('Y-m-d H:i:s') : 'Nunca',
                $webhook->last_error ?? 'Nenhum'
            ];
        }

        $this->table($headers, $rows);
        $this->info("Total de webhooks: {$webhooks->count()}");

        return 0;
    }
}
