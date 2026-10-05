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

use App\Http\Requests\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\V1\Auth\ResetPasswordRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    /**
     * Solicita redefinição de senha e gera token de recuperação.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        return ApiResponse::success([
            'reset_token' => $token,
            'email' => $email,
        ], 'Token de recuperação de senha gerado com sucesso.');
    }

    /**
     * Redefine a senha do usuário utilizando o token emitido.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $token = $request->validated('token');

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record || ! Hash::check($token, $record->token)) {
            return ApiResponse::error('Token de recuperação inválido ou expirado.', null, 422);
        }

        // Verifica expiração de 60 minutos
        if (now()->subMinutes(60)->isAfter($record->created_at)) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return ApiResponse::error('Token de recuperação expirado. Solicite uma nova redefinição.', null, 422);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->update([
            'password' => Hash::make((string) $request->validated('password')),
        ]);

        // Invalida o token após o uso
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return ApiResponse::success(null, 'Senha redefinida com sucesso. Você já pode autenticar-se.');
    }
}
