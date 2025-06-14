<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Webhooks",
 *     description="API Endpoints para gerenciamento de webhooks"
 * )
 */
class WebhookController extends Controller
{
    protected $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * @OA\Get(
     *     path="/api/webhooks",
     *     summary="Lista todos os webhooks do usuário",
     *     tags={"Webhooks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de webhooks",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="url", type="string"),
     *                 @OA\Property(property="events", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="last_triggered_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $webhooks = auth()->user()->webhooks;
        return response()->json($webhooks);
    }

    /**
     * @OA\Post(
     *     path="/api/webhooks",
     *     summary="Cria um novo webhook",
     *     tags={"Webhooks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "url", "events"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="url", type="string", format="uri"),
     *             @OA\Property(property="secret", type="string"),
     *             @OA\Property(property="events", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="retry_count", type="integer"),
     *             @OA\Property(property="timeout", type="integer"),
     *             @OA\Property(property="headers", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Webhook criado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="webhook", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'secret' => 'nullable|string|max:255',
            'events' => 'required|array',
            'events.*' => 'required|string|in:' . implode(',', array_keys(Webhook::getAvailableEvents())),
            'is_active' => 'boolean',
            'retry_count' => 'integer|min:1|max:10',
            'timeout' => 'integer|min:5|max:120',
            'headers' => 'array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $webhook = $this->webhookService->createWebhook(auth()->user(), $request->all());

        return response()->json([
            'message' => 'Webhook criado com sucesso',
            'webhook' => $webhook
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/webhooks/{id}",
     *     summary="Exibe um webhook específico",
     *     tags={"Webhooks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do webhook",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do webhook",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Webhook não encontrado"
     *     )
     * )
     */
    public function show($id)
    {
        $webhook = auth()->user()->webhooks()->findOrFail($id);
        return response()->json($webhook);
    }

    /**
     * @OA\Put(
     *     path="/api/webhooks/{id}",
     *     summary="Atualiza um webhook existente",
     *     tags={"Webhooks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do webhook",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="url", type="string", format="uri"),
     *             @OA\Property(property="secret", type="string"),
     *             @OA\Property(property="events", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="retry_count", type="integer"),
     *             @OA\Property(property="timeout", type="integer"),
     *             @OA\Property(property="headers", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook atualizado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="webhook", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Webhook não encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $webhook = auth()->user()->webhooks()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'url' => 'url|max:255',
            'secret' => 'nullable|string|max:255',
            'events' => 'array',
            'events.*' => 'string|in:' . implode(',', array_keys(Webhook::getAvailableEvents())),
            'is_active' => 'boolean',
            'retry_count' => 'integer|min:1|max:10',
            'timeout' => 'integer|min:5|max:120',
            'headers' => 'array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $webhook->update($request->all());

        return response()->json([
            'message' => 'Webhook atualizado com sucesso',
            'webhook' => $webhook
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/webhooks/{id}",
     *     summary="Remove um webhook",
     *     tags={"Webhooks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do webhook",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook removido com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Webhook não encontrado"
     *     )
     * )
     */
    public function destroy($id)
    {
        $webhook = auth()->user()->webhooks()->findOrFail($id);
        $webhook->delete();

        return response()->json([
            'message' => 'Webhook removido com sucesso'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/webhooks/{id}/regenerate-secret",
     *     summary="Regenera o secret do webhook",
     *     tags={"Webhooks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do webhook",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Secret regenerado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="secret", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Webhook não encontrado"
     *     )
     * )
     */
    public function regenerateSecret($id)
    {
        $webhook = auth()->user()->webhooks()->findOrFail($id);
        $secret = bin2hex(random_bytes(32));

        $webhook->update(['secret' => $secret]);

        return response()->json([
            'message' => 'Secret regenerado com sucesso',
            'secret' => $secret
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/webhooks/{id}/events",
     *     summary="Processa um evento de webhook",
     *     tags={"Webhooks"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do webhook",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"event", "payload"},
     *             @OA\Property(property="event", type="string"),
     *             @OA\Property(property="payload", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evento processado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Webhook inativo ou evento não permitido"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Assinatura inválida"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Webhook não encontrado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao processar evento"
     *     )
     * )
     */
    public function handleEvent(Request $request, $id)
    {
        $webhook = Webhook::findOrFail($id);

        if (!$webhook->is_active) {
            return response()->json([
                'error' => 'Webhook inativo'
            ], 400);
        }

        $event = $request->input('event');
        $payload = $request->input('payload');

        if (!in_array($event, $webhook->events)) {
            return response()->json([
                'error' => 'Evento não permitido para este webhook'
            ], 400);
        }

        try {
            $this->webhookService->triggerEvent($event, $payload);

            return response()->json([
                'message' => 'Evento processado com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar evento de webhook', [
                'webhook_id' => $id,
                'event' => $event,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erro ao processar evento'
            ], 500);
        }
    }
}
