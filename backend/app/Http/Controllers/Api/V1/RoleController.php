<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PermissionResource;
use App\Http\Resources\V1\RoleResource;
use App\Http\Responses\ApiResponse;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Lista todos os perfis disponíveis no sistema.
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->orderBy('id')->get();

        return ApiResponse::success(RoleResource::collection($roles), 'Perfis recuperados com sucesso.');
    }

    /**
     * Exibe os detalhes de um perfil com suas permissões.
     */
    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');

        return ApiResponse::success(new RoleResource($role), 'Perfil recuperado com sucesso.');
    }

    /**
     * Lista todas as permissões do sistema (restrito a ADMIN).
     */
    public function permissions(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return ApiResponse::error('Apenas administradores podem gerenciar permissões.', null, 403);
        }

        $permissions = Permission::orderBy('slug')->get();

        return ApiResponse::success(PermissionResource::collection($permissions), 'Permissões recuperadas com sucesso.');
    }
}
