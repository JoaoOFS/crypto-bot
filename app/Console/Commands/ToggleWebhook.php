<?php

namespace App\Console\Commands;

use App\Models\Webhook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ToggleWebhook extends Command
{
    protected $signature = 'webhook:toggle {id : ID do webhook} {--force : Forçar a operação sem confirmação}';
    protected $description = 'Ativa ou desativa um webhook';

    public function handle()
    {
        $webhook = Webhook::find($this->argument('id'));

        if (!$webhook) {
            $this->error('Webhook não encontrado');
            return 1;
        }

        $newStatus = !$webhook->is_active;
        $statusText = $newStatus ? 'ativar' : 'desativar';

        if (!$this->option('force') && !$this->confirm("Deseja {$statusText} o webhook '{$webhook->name}'?")) {
            $this->info('Operação cancelada.');
            return 0;
        }

        if ($newStatus) {
            $webhook->activate();
            $this->info("Webhook '{$webhook->name}' ativado com sucesso.");
        } else {
            $webhook->deactivate();
            $this->info("Webhook '{$webhook->name}' desativado com sucesso.");
        }

        Log::info('Status do webhook alterado', [
            'webhook_id' => $webhook->id,
            'name' => $webhook->name,
            'new_status' => $newStatus ? 'active' : 'inactive'
        ]);

        return 0;
    }
}
