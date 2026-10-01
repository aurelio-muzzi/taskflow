<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Administradores podem deletar usuários, exceto a si mesmos
        if (! $user->isAdmin()) {
            return false;
        }

        return $user->id !== $model->id;
    }

    /**
     * Determine whether the user can toggle the active status of another user.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        // Não pode desativar a própria conta
        return $user->id !== $model->id;
    }
}
