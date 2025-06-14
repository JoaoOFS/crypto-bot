<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Alert;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $alert;
    protected $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->alert = Alert::factory()->create(['user_id' => $this->user->id]);
        $this->notificationService = app(NotificationService::class);
    }

    /** @test */
    public function it_can_create_notification()
    {
        $notification = $this->notificationService->createNotification(
            $this->user,
            $this->alert,
            Notification::TYPE_EMAIL,
            $this->user->email,
            'Teste de Notificação',
            'Esta é uma mensagem de teste',
            ['additional' => 'data']
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($this->user->id, $notification->user_id);
        $this->assertEquals($this->alert->id, $notification->alert_id);
        $this->assertEquals(Notification::TYPE_EMAIL, $notification->type);
        $this->assertEquals($this->user->email, $notification->channel);
        $this->assertEquals('Teste de Notificação', $notification->title);
        $this->assertEquals('Esta é uma mensagem de teste', $notification->message);
        $this->assertEquals(['additional' => 'data'], $notification->data);
        $this->assertEquals(Notification::STATUS_PENDING, $notification->status);
    }

    /** @test */
    public function it_can_send_email_notification()
    {
        Mail::fake();

        $notification = $this->notificationService->createNotification(
            $this->user,
            $this->alert,
            Notification::TYPE_EMAIL,
            $this->user->email,
            'Teste de Email',
            'Esta é uma mensagem de teste'
        );

        $this->notificationService->sendNotification($notification);

        Mail::assertSent(AlertNotification::class, function ($mail) use ($notification) {
            return $mail->hasTo($notification->channel) &&
                   $mail->notification->id === $notification->id;
        });

        $this->assertEquals(Notification::STATUS_SENT, $notification->fresh()->status);
        $this->assertNotNull($notification->fresh()->sent_at);
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        $notification = $this->notificationService->createNotification(
            $this->user,
            $this->alert,
            Notification::TYPE_EMAIL,
            $this->user->email,
            'Teste de Notificação',
            'Esta é uma mensagem de teste'
        );

        $this->notificationService->markAsRead($notification);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function it_can_get_user_notifications()
    {
        // Criar algumas notificações
        $this->notificationService->createNotification(
            $this->user,
            $this->alert,
            Notification::TYPE_EMAIL,
            $this->user->email,
            'Notificação 1',
            'Mensagem 1'
        );

        $this->notificationService->createNotification(
            $this->user,
            $this->alert,
            Notification::TYPE_EMAIL,
            $this->user->email,
            'Notificação 2',
            'Mensagem 2'
        );

        $notifications = $this->notificationService->getUserNotifications($this->user);

        $this->assertCount(2, $notifications);
        $this->assertEquals('Notificação 2', $notifications->first()->title);
    }

    /** @test */
    public function it_can_get_unread_notifications_count()
    {
        // Criar algumas notificações
        $this->notificationService->createNotification(
            $this->user,
            $this->alert,
            Notification::TYPE_EMAIL,
            $this->user->email,
            'Notificação 1',
            'Mensagem 1'
        );

        $notification2 = $this->notificationService->createNotification(
            $this->user,
            $this->alert,
            Notification::TYPE_EMAIL,
            $this->user->email,
            'Notificação 2',
            'Mensagem 2'
        );

        // Marcar uma notificação como lida
        $this->notificationService->markAsRead($notification2);

        $count = $this->notificationService->getUnreadCount($this->user);

        $this->assertEquals(1, $count);
    }
}
