<?php

namespace App\Notifications;

use App\Models\Notification as NotificationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class PushAlertNotification extends Notification
{
    use Queueable;

    protected $notification;

    /**
     * Create a new notification instance.
     */
    public function __construct(NotificationModel $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    public function toWebPush($notifiable, $notification)
    {
        $alert = $this->notification->alert;
        $data = [
            'notification_id' => $this->notification->id,
            'alert_id' => $alert ? $alert->id : null,
            'type' => $this->notification->type,
            'data' => $this->notification->data
        ];

        return WebPushMessage::create()
            ->id($this->notification->id)
            ->title($this->notification->title)
            ->icon('/images/icon-192x192.png')
            ->body($this->notification->message)
            ->action('Ver Detalhes', 'view_alert')
            ->data($data)
            ->badge('/images/badge-72x72.png')
            ->vibrate([100, 50, 100])
            ->renotify()
            ->requireInteraction();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
