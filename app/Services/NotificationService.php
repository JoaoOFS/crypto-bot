<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Alert;
use App\Mail\AlertNotification;
use App\Notifications\PushAlertNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function createNotification(User $user, Alert $alert, string $type, string $channel, string $title, string $message, array $data = [])
    {
        return Notification::create([
            'user_id' => $user->id,
            'alert_id' => $alert->id,
            'type' => $type,
            'channel' => $channel,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'status' => Notification::STATUS_PENDING
        ]);
    }

    public function sendNotification(Notification $notification)
    {
        try {
            switch ($notification->type) {
                case Notification::TYPE_EMAIL:
                    $this->sendEmailNotification($notification);
                    break;
                case Notification::TYPE_PUSH:
                    $this->sendPushNotification($notification);
                    break;
                case Notification::TYPE_WEBHOOK:
                    $this->sendWebhookNotification($notification);
                    break;
                default:
                    throw new \Exception("Tipo de notificação não suportado: {$notification->type}");
            }

            $notification->markAsSent();
            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao enviar notificação: {$e->getMessage()}", [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);

            $notification->markAsFailed($e->getMessage());
            return false;
        }
    }

    protected function sendEmailNotification(Notification $notification)
    {
        try {
            Mail::to($notification->channel)
                ->queue(new AlertNotification($notification));

            Log::info("Email enviado com sucesso para: {$notification->channel}", [
                'notification_id' => $notification->id,
                'title' => $notification->title
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao enviar email: {$e->getMessage()}", [
                'notification_id' => $notification->id,
                'channel' => $notification->channel
            ]);

            throw $e;
        }
    }

    protected function sendPushNotification(Notification $notification)
    {
        try {
            $user = $notification->user;
            $subscriptions = $user->pushSubscriptions()->active()->get();

            foreach ($subscriptions as $subscription) {
                $user->notify(new PushAlertNotification($notification));
            }

            Log::info("Push notification enviada com sucesso", [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'subscriptions_count' => $subscriptions->count()
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao enviar push notification: {$e->getMessage()}", [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id
            ]);

            throw $e;
        }
    }

    protected function sendWebhookNotification(Notification $notification)
    {
        // TODO: Implementar envio de webhook
        // Por enquanto, apenas logamos a tentativa
        Log::info("Enviando webhook para: {$notification->channel}", [
            'notification_id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message
        ]);
    }

    public function processPendingNotifications()
    {
        $pendingNotifications = Notification::where('status', Notification::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();

        foreach ($pendingNotifications as $notification) {
            $this->sendNotification($notification);
        }
    }

    public function markAsRead(Notification $notification)
    {
        $notification->markAsRead();
    }

    public function getUserNotifications(User $user, int $limit = 10)
    {
        return Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getUnreadCount(User $user)
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}
