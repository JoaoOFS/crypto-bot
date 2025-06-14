<?php

namespace App\Console\Commands;

use App\Models\Webhook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupWebhooks extends Command
{
    protected $signature = 'webhooks:cleanup {--days=30 : Número de dias para manter webhooks inativos}';
    protected $description = 'Remove webhooks inativos que não foram acionados há mais de X dias';

    public function handle()
    {
        $days = $this->option('days');
        $date = now()->subDays($days);

        $webhooks = Webhook::where('is_active', false)
            ->where(function ($query) use ($date) {
                $query->where('last_triggered_at', '<', $date)
                    ->orWhereNull('last_triggered_at');
            })
            ->get();

        $count = $webhooks->count();

        if ($count === 0) {
            $this->info('Nenhum webhook inativo encontrado para remoção.');
            return 0;
        }

        if ($this->confirm("Deseja remover {$count} webhooks inativos?")) {
            foreach ($webhooks as $webhook) {
                $webhook->delete();
                Log::info('Webhook removido por inatividade', [
                    'webhook_id' => $webhook->id,
                    'name' => $webhook->name,
                    'last_triggered_at' => $webhook->last_triggered_at
                ]);
            }

            $this->info("{$count} webhooks removidos com sucesso.");
        } else {
            $this->info('Operação cancelada.');
        }

        return 0;
    }
}
