<?php

namespace App\Actions\Users;

use App\DTOs\Users\CreateUserDTO;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUserAction
{
    /**
     * Cria e persiste um novo usuário no sistema.
     */
    public function execute(CreateUserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $user = User::create([
                'role_id' => $dto->roleId,
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
                'status' => $dto->status,
                'avatar_path' => $dto->avatarPath,
            ]);

            return $user->load('role');
        });
    }
}
