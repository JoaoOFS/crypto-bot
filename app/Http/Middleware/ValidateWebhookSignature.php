<?php

namespace App\Http\Middleware;

use App\Models\Webhook;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ValidateWebhookSignature
{
    public function handle(Request $request, Closure $next)
    {
        $webhookId = $request->route('id');
        $signature = $request->header('X-Webhook-Signature');

        if (!$signature || !$webhookId) {
            return response()->json([
                'error' => 'Assinatura ou ID do webhook não fornecidos'
            ], 401);
        }

        $webhook = Webhook::find($webhookId);

        if (!$webhook || !$webhook->secret) {
            return response()->json([
                'error' => 'Webhook não encontrado ou sem segredo configurado'
            ], 404);
        }

        $payload = $request->all();

        if (!$webhook->verifySignature($signature, $payload)) {
            Log::warning('Tentativa de acesso com assinatura inválida', [
                'webhook_id' => $webhookId,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'error' => 'Assinatura inválida'
            ], 401);
        }

        return $next($request);
    }
}
