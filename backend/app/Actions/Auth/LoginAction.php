<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginDTO;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginAction
{
    /**
     * Autentica as credenciais, valida o status e emite o token Sanctum.
     *
     * @return array{token: string, user: User}
     *
     * @throws ValidationException
     */
    public function execute(LoginDTO $dto): array
    {
        /** @var User|null $user */
        $user = User::with('role')->where('email', $dto->email)->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas. Verifique seu e-mail e senha.'],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Esta conta de usuário está inativa. Entre em contato com o administrador.'],
            ]);
        }

        $tokenName = $dto->deviceName ?: 'auth-token';
        $token = $user->createToken($tokenName)->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
