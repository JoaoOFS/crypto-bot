<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notification;

    /**
     * Create a new job instance.
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService)
    {
        try {
            Log::info('Processando notificação', [
                'notification_id' => $this->notification->id,
                'type' => $this->notification->type,
                'channel' => $this->notification->channel
            ]);

            $notificationService->sendNotification($this->notification);
        } catch (\Exception $e) {
            Log::error('Erro ao processar notificação', [
                'notification_id' => $this->notification->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Falha ao processar notificação', [
            'notification_id' => $this->notification->id,
            'error' => $exception->getMessage()
        ]);
    }
}
