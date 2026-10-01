<?php

namespace App\Actions\Users;

use App\DTOs\Users\UpdateUserDTO;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateUserAction
{
    /**
     * Atualiza os dados de um usuário existente.
     */
    public function execute(User $user, UpdateUserDTO $dto): User
    {
        return DB::transaction(function () use ($user, $dto) {
            $data = [];

            if ($dto->roleId !== null) {
                $data['role_id'] = $dto->roleId;
            }

            if ($dto->name !== null) {
                $data['name'] = $dto->name;
            }

            if ($dto->email !== null) {
                $data['email'] = $dto->email;
            }

            if ($dto->password !== null) {
                $data['password'] = Hash::make($dto->password);
            }

            if ($dto->status !== null) {
                $data['status'] = $dto->status;
            }

            if ($dto->avatarPath !== null) {
                $data['avatar_path'] = $dto->avatarPath;
            }

            if (! empty($data)) {
                $user->update($data);
            }

            return $user->fresh()->load('role');
        });
    }
}
