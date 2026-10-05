<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AuditLogResource;
use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Lista a trilha geral de auditoria com filtros e paginação.
     * Restrito a Administradores e Gerentes.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        if (! $currentUser->isAdmin() && ! $currentUser->isManager()) {
            return ApiResponse::error('Acesso restrito a administradores e gerentes.', null, 403);
        }

        $query = AuditLog::with(['user.role']);

        $query->filterEvent($request->input('event'))
            ->filterUser($request->input('user_id') ? (int) $request->input('user_id') : null);

        if ($type = $request->input('auditable_type')) {
            $query->where('auditable_type', 'like', "%{$type}%");
        }

        $query->orderBy('created_at', 'desc');

        $perPage = min((int) $request->input('per_page', 20), 100);
        $paginated = $query->paginate($perPage);

        return ApiResponse::paginated(
            items: AuditLogResource::collection($paginated->items()),
            paginator: $paginated,
            message: 'Trilha de auditoria recuperada com sucesso.'
        );
    }

    /**
     * Exibe os detalhes de um registro de auditoria específico (somente leitura).
     */
    public function show(Request $request, AuditLog $auditLog): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        if (! $currentUser->isAdmin() && ! $currentUser->isManager()) {
            return ApiResponse::error('Acesso restrito a administradores e gerentes.', null, 403);
        }

        $auditLog->load('user.role');

        return ApiResponse::success(
            data: new AuditLogResource($auditLog),
            message: 'Registro de auditoria recuperado com sucesso.'
        );
    }

    /**
     * Lista os logs de auditoria de uma tarefa específica.
     */
    public function forTask(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $logs = $task->auditLogs()
            ->with(['user.role'])
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success(
            data: AuditLogResource::collection($logs),
            message: 'Histórico de alterações da tarefa recuperado com sucesso.'
        );
    }
}
