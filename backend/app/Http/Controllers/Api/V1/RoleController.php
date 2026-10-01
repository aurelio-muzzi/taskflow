<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RoleResource;
use App\Http\Responses\ApiResponse;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    /**
     * Lista todos os perfis disponíveis no sistema.
     */
    public function index(): JsonResponse
    {
        $roles = Role::orderBy('id')->get();

        return ApiResponse::success(RoleResource::collection($roles), 'Perfis recuperados com sucesso.');
    }
}
