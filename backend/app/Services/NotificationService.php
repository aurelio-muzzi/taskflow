<?php

namespace App\Services;

use App\Models\InternalNotification;

class NotificationService
{
    /**
     * Envia uma notificação interna para o usuário especificado.
     */
    public static function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null
    ): InternalNotification {
        return InternalNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
