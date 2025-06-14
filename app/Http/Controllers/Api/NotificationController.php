<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="API Endpoints para gerenciamento de notificações"
 * )
 */
class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     summary="Lista as notificações do usuário",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Número máximo de notificações a retornar",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de notificações",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="data", type="object"),
     *                 @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $notifications = $this->notificationService->getUserNotifications(Auth::user(), $limit);

        return response()->json($notifications);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/unread-count",
     *     summary="Retorna o número de notificações não lidas",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Contagem de notificações não lidas",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="count", type="integer")
     *         )
     *     )
     * )
     */
    public function unreadCount()
    {
        $count = $this->notificationService->getUnreadCount(Auth::user());

        return response()->json(['count' => $count]);
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/{id}/mark-as-read",
     *     summary="Marca uma notificação específica como lida",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da notificação",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notificação marcada como lida",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $this->notificationService->markAsRead($notification);

        return response()->json(['message' => 'Notificação marcada como lida']);
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/mark-all-as-read",
     *     summary="Marca todas as notificações como lidas",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Todas as notificações foram marcadas como lidas",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Todas as notificações foram marcadas como lidas']);
    }
}
