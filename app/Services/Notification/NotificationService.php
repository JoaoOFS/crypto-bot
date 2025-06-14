<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Telegram\Bot\Api as TelegramApi;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TradingSignalNotification;

class NotificationService
{
    protected $telegram;

    public function __construct()
    {
        $this->telegram = new TelegramApi(config('services.telegram.bot_token'));
    }

    public function sendTelegramMessage($chatId, $message)
    {
        try {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Telegram Notification Error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendEmail($to, $subject, $message)
    {
        try {
            Mail::raw($message, function ($mail) use ($to, $subject) {
                $mail->to($to)
                    ->subject($subject);
            });
            return true;
        } catch (\Exception $e) {
            Log::error('Email Notification Error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendTradingSignal($user, $signal)
    {
        try {
            $message = $this->formatTradingSignalMessage($signal);

            // Envia notificação por email
            if ($user->email_notifications_enabled) {
                $this->sendEmail(
                    $user->email,
                    'Novo Sinal de Trading',
                    $message
                );
            }

            // Envia notificação por Telegram
            if ($user->telegram_notifications_enabled && $user->telegram_chat_id) {
                $this->sendTelegramMessage($user->telegram_chat_id, $message);
            }

            // Envia notificação via Laravel Notifications
            $user->notify(new TradingSignalNotification($signal));

            return true;
        } catch (\Exception $e) {
            Log::error('Trading Signal Notification Error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPortfolioAlert($user, $alert)
    {
        try {
            $message = $this->formatPortfolioAlertMessage($alert);

            // Envia notificação por email
            if ($user->email_notifications_enabled) {
                $this->sendEmail(
                    $user->email,
                    'Alerta de Portfólio',
                    $message
                );
            }

            // Envia notificação por Telegram
            if ($user->telegram_notifications_enabled && $user->telegram_chat_id) {
                $this->sendTelegramMessage($user->telegram_chat_id, $message);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Portfolio Alert Notification Error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendSystemAlert($user, $alert)
    {
        try {
            $message = $this->formatSystemAlertMessage($alert);

            // Envia notificação por email
            if ($user->email_notifications_enabled) {
                $this->sendEmail(
                    $user->email,
                    'Alerta do Sistema',
                    $message
                );
            }

            // Envia notificação por Telegram
            if ($user->telegram_notifications_enabled && $user->telegram_chat_id) {
                $this->sendTelegramMessage($user->telegram_chat_id, $message);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('System Alert Notification Error: ' . $e->getMessage());
            return false;
        }
    }

    protected function formatTradingSignalMessage($signal)
    {
        return sprintf(
            "🔔 <b>Novo Sinal de Trading</b>\n\n" .
            "Par: %s\n" .
            "Ação: %s\n" .
            "Preço: %s\n" .
            "RSI: %.2f\n" .
            "MACD: %.2f\n" .
            "Signal: %.2f\n" .
            "Histogram: %.2f\n\n" .
            "Data/Hora: %s",
            $signal['symbol'],
            strtoupper($signal['action']),
            $signal['price'],
            $signal['rsi'],
            $signal['macd'],
            $signal['signal'],
            $signal['histogram'],
            now()->format('d/m/Y H:i:s')
        );
    }

    protected function formatPortfolioAlertMessage($alert)
    {
        return sprintf(
            "📊 <b>Alerta de Portfólio</b>\n\n" .
            "Tipo: %s\n" .
            "Ativo: %s\n" .
            "Valor: %s\n" .
            "Variação: %.2f%%\n\n" .
            "Data/Hora: %s",
            $alert['type'],
            $alert['asset'],
            $alert['value'],
            $alert['variation'],
            now()->format('d/m/Y H:i:s')
        );
    }

    protected function formatSystemAlertMessage($alert)
    {
        return sprintf(
            "⚠️ <b>Alerta do Sistema</b>\n\n" .
            "Tipo: %s\n" .
            "Mensagem: %s\n" .
            "Severidade: %s\n\n" .
            "Data/Hora: %s",
            $alert['type'],
            $alert['message'],
            $alert['severity'],
            now()->format('d/m/Y H:i:s')
        );
    }
}
