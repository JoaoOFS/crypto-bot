<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Push Subscriptions",
 *     description="API Endpoints para gerenciamento de assinaturas de notificações push"
 * )
 */
class PushSubscriptionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/push-subscriptions",
     *     summary="Cria uma nova assinatura de notificação push",
     *     tags={"Push Subscriptions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"endpoint", "public_key", "auth_token"},
     *             @OA\Property(property="endpoint", type="string", format="uri"),
     *             @OA\Property(property="public_key", type="string"),
     *             @OA\Property(property="auth_token", type="string"),
     *             @OA\Property(property="device_type", type="string"),
     *             @OA\Property(property="device_name", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Assinatura criada com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="endpoint", type="string"),
     *             @OA\Property(property="public_key", type="string"),
     *             @OA\Property(property="auth_token", type="string"),
     *             @OA\Property(property="device_type", type="string", nullable=true),
     *             @OA\Property(property="device_name", type="string", nullable=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
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
            'endpoint' => 'required|string|unique:push_subscriptions,endpoint',
            'public_key' => 'required|string',
            'auth_token' => 'required|string',
            'device_type' => 'nullable|string',
            'device_name' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subscription = PushSubscription::create([
            'user_id' => Auth::id(),
            'endpoint' => $request->endpoint,
            'public_key' => $request->public_key,
            'auth_token' => $request->auth_token,
            'device_type' => $request->device_type,
            'device_name' => $request->device_name
        ]);

        return response()->json($subscription, 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/push-subscriptions",
     *     summary="Remove uma assinatura de notificação push",
     *     tags={"Push Subscriptions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"endpoint"},
     *             @OA\Property(property="endpoint", type="string", format="uri")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assinatura removida com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Assinatura não encontrada"
     *     )
     * )
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subscription = PushSubscription::where('user_id', Auth::id())
            ->where('endpoint', $request->endpoint)
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'Assinatura não encontrada'], 404);
        }

        $subscription->delete();

        return response()->json(['message' => 'Assinatura removida com sucesso']);
    }

    /**
     * @OA\Put(
     *     path="/api/push-subscriptions/{id}",
     *     summary="Atualiza o status de uma assinatura",
     *     tags={"Push Subscriptions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da assinatura",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"is_active"},
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assinatura atualizada com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function update(Request $request, PushSubscription $subscription)
    {
        if ($subscription->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subscription->update([
            'is_active' => $request->is_active
        ]);

        return response()->json($subscription);
    }

    /**
     * @OA\Get(
     *     path="/api/push-subscriptions",
     *     summary="Lista todas as assinaturas do usuário",
     *     tags={"Push Subscriptions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de assinaturas",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="endpoint", type="string"),
     *                 @OA\Property(property="public_key", type="string"),
     *                 @OA\Property(property="auth_token", type="string"),
     *                 @OA\Property(property="device_type", type="string", nullable=true),
     *                 @OA\Property(property="device_name", type="string", nullable=true),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $subscriptions = PushSubscription::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($subscriptions);
    }
}
