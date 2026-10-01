<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\InternalNotificationResource;
use App\Http\Responses\ApiResponse;
use App\Models\InternalNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Lista notificações do usuário logado e contagem de não lidas.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $notifications = InternalNotification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        $unreadCount = InternalNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return ApiResponse::success([
            'items' => InternalNotificationResource::collection($notifications),
            'unread_count' => $unreadCount,
        ], 'Notificações recuperadas com sucesso.');
    }

    /**
     * Marca uma notificação individual como lida.
     */
    public function markAsRead(Request $request, InternalNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return ApiResponse::error('Não autorizado a alterar esta notificação.', null, 403);
        }

        $notification->markAsRead();

        return ApiResponse::success(
            data: new InternalNotificationResource($notification),
            message: 'Notificação marcada como lida.'
        );
    }

    /**
     * Marca todas as notificações do usuário como lidas.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        InternalNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success(
            data: null,
            message: 'Todas as notificações foram marcadas como lidas.'
        );
    }
}
