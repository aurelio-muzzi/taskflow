<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Users\CreateUserAction;
use App\Actions\Users\UpdateUserAction;
use App\DTOs\Users\CreateUserDTO;
use App\DTOs\Users\UpdateUserDTO;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Users\CreateUserRequest;
use App\Http\Requests\V1\Users\UpdateUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Lista usuários com busca, filtros de status/perfil e paginação.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('role');

        // Busca por nome ou e-mail
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtro por status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filtro por perfil (role_id ou slug)
        if ($roleId = $request->input('role_id')) {
            $query->where('role_id', $roleId);
        } elseif ($roleSlug = $request->input('role')) {
            $query->whereHas('role', fn ($q) => $q->where('slug', $roleSlug));
        }

        // Ordenação segura
        $allowedSorts = ['name', 'email', 'status', 'created_at'];
        $sortBy = in_array($request->input('sort_by'), $allowedSorts, true) ? $request->input('sort_by') : 'name';
        $direction = strtolower((string) $request->input('direction')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $direction);

        // Paginação com metadados
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $paginated = $query->paginate($perPage);

        return ApiResponse::success([
            'items' => UserResource::collection($paginated->items()),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ], 'Usuários listados com sucesso.');
    }

    /**
     * Cria um novo usuário.
     */
    public function store(CreateUserRequest $request, CreateUserAction $action): JsonResponse
    {
        $dto = CreateUserDTO::fromRequest($request->validated());
        $user = $action->execute($dto);

        return ApiResponse::success(
            new UserResource($user),
            'Usuário criado com sucesso.',
            Response::HTTP_CREATED
        );
    }

    /**
     * Exibe os detalhes de um usuário.
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return ApiResponse::success(
            new UserResource($user->load('role')),
            'Usuário recuperado com sucesso.'
        );
    }

    /**
     * Atualiza os dados de um usuário existente.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): JsonResponse
    {
        $dto = UpdateUserDTO::fromRequest($request->validated());
        $updatedUser = $action->execute($user, $dto);

        return ApiResponse::success(
            new UserResource($updatedUser),
            'Usuário atualizado com sucesso.'
        );
    }

    /**
     * Altera o status de ativação do usuário (Ativo / Inativo).
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        $this->authorize('toggleStatus', $user);

        $newStatus = $user->status === UserStatus::ACTIVE ? UserStatus::INACTIVE : UserStatus::ACTIVE;
        $user->update(['status' => $newStatus]);

        return ApiResponse::success(
            new UserResource($user->fresh()->load('role')),
            'Status do usuário alterado com sucesso.'
        );
    }

    /**
     * Remove logicamente (Soft Delete) um usuário.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return ApiResponse::success(null, 'Usuário removido com sucesso.');
    }
}
