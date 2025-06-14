<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class ProcessNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processa todas as notificações pendentes';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando processamento de notificações pendentes...');

        try {
            $this->notificationService->processPendingNotifications();
            $this->info('Notificações processadas com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao processar notificações: ' . $e->getMessage());
            $this->error('Erro ao processar notificações: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
