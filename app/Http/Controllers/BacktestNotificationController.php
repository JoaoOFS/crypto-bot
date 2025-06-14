<?php

namespace App\Http\Controllers;

use App\Models\Backtest;
use App\Models\BacktestNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BacktestNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BacktestNotification::query()
            ->with('backtest')
            ->orderBy('created_at', 'desc');

        // Filter by backtest
        if ($request->has('backtest_id')) {
            $query->where('backtest_id', $request->backtest_id);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by read status
        if ($request->has('read')) {
            if ($request->read) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        $notifications = $query->paginate(20);

        return response()->json($notifications);
    }

    public function markAsRead(BacktestNotification $notification): JsonResponse
    {
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $query = BacktestNotification::query()
            ->whereNull('read_at');

        if ($request->has('backtest_id')) {
            $query->where('backtest_id', $request->backtest_id);
        }

        $count = $query->update(['read_at' => now()]);

        return response()->json([
            'message' => "{$count} notifications marked as read"
        ]);
    }

    public function getUnreadCount(Request $request): JsonResponse
    {
        $query = BacktestNotification::query()
            ->whereNull('read_at');

        if ($request->has('backtest_id')) {
            $query->where('backtest_id', $request->backtest_id);
        }

        $count = $query->count();

        return response()->json([
            'unread_count' => $count
        ]);
    }

    public function destroy(BacktestNotification $notification): JsonResponse
    {
        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }
}
