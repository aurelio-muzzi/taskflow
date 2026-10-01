<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\LoginAction;
use App\DTOs\Auth\LoginDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\ChangePasswordRequest;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Requests\V1\Auth\UpdateProfileRequest;
use App\Http\Resources\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Autentica o usuário e emite o token Sanctum.
     */
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $dto = LoginDTO::fromRequest($request->validated());
        $authData = $action->execute($dto);

        return ApiResponse::success([
            'token' => $authData['token'],
            'user' => new UserResource($authData['user']),
        ], 'Autenticação realizada com sucesso.');
    }

    /**
     * Revoga o token atual do usuário autenticado.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Revoga o token atual
        $user->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Sessão encerrada com sucesso.');
    }

    /**
     * Retorna os dados do usuário autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load('role');

        return ApiResponse::success(new UserResource($user), 'Perfil recuperado com sucesso.');
    }

    /**
     * Atualiza o perfil do usuário logado.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'avatar_path' => $validated['avatar_path'] ?? $user->avatar_path,
        ]);

        return ApiResponse::success(
            new UserResource($user->fresh()->load('role')),
            'Perfil atualizado com sucesso.'
        );
    }

    /**
     * Altera a senha do usuário autenticado.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->update([
            'password' => Hash::make((string) $request->validated('password')),
        ]);

        return ApiResponse::success(null, 'Senha alterada com sucesso.');
    }
}
