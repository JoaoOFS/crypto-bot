<?php

namespace App\Console\Commands;

use App\Models\Webhook;
use App\Jobs\ProcessWebhook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetryFailedWebhooks extends Command
{
    protected $signature = 'webhooks:retry {--hours=24 : Número de horas para tentar novamente} {--force : Forçar a operação sem confirmação}';
    protected $description = 'Tenta novamente webhooks que falharam nas últimas X horas';

    public function handle()
    {
        $hours = $this->option('hours');
        $date = now()->subHours($hours);

        $webhooks = Webhook::where('is_active', true)
            ->whereNotNull('last_failed_at')
            ->where('last_failed_at', '>=', $date)
            ->get();

        $count = $webhooks->count();

        if ($count === 0) {
            $this->info('Nenhum webhook com falha encontrado para tentar novamente.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm("Deseja tentar novamente {$count} webhooks que falharam?")) {
            $this->info('Operação cancelada.');
            return 0;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($webhooks as $webhook) {
            try {
                ProcessWebhook::dispatch($webhook, 'retry', [
                    'original_error' => $webhook->last_error,
                    'retry_attempt' => 1
                ])->onQueue('webhooks');

                $successCount++;
                $this->info("Webhook '{$webhook->name}' enviado para fila de processamento.");
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Erro ao enviar webhook '{$webhook->name}' para fila: " . $e->getMessage());

                Log::error('Erro ao tentar novamente webhook', [
                    'webhook_id' => $webhook->id,
                    'name' => $webhook->name,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("\nResumo:");
        $this->info("- Webhooks processados com sucesso: {$successCount}");
        $this->info("- Webhooks com erro: {$errorCount}");

        return $errorCount > 0 ? 1 : 0;
    }
}
