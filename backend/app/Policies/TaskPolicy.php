<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Bypassa autorização para administradores globais.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determina se o usuário pode listar tarefas do projeto.
     */
    public function viewAny(User $user, Project $project): bool
    {
        return $project->hasMember($user);
    }

    /**
     * Determina se o usuário pode visualizar uma tarefa específica.
     */
    public function view(User $user, Task $task): bool
    {
        return $task->project->hasMember($user);
    }

    /**
     * Determina se o usuário pode criar tarefas no projeto.
     * Somente Owner, Manager ou Member do projeto podem criar tarefas (Viewer não pode).
     */
    public function create(User $user, Project $project): bool
    {
        if ($project->owner_id === $user->id) {
            return true;
        }

        $role = $project->getUserRole($user);

        return in_array($role, [ProjectRole::OWNER, ProjectRole::MANAGER, ProjectRole::MEMBER], true);
    }

    /**
     * Determina se o usuário pode atualizar uma tarefa.
     * Owner, Manager do projeto, criador da tarefa ou usuário atribuído.
     */
    public function update(User $user, Task $task): bool
    {
        $project = $task->project;

        if ($project->owner_id === $user->id) {
            return true;
        }

        if ($task->created_by === $user->id || $task->assigned_to === $user->id) {
            return true;
        }

        $role = $project->getUserRole($user);

        return in_array($role, [ProjectRole::OWNER, ProjectRole::MANAGER], true);
    }

    /**
     * Determina se o usuário pode atualizar o status de uma tarefa.
     */
    public function updateStatus(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Determina se o usuário pode excluir uma tarefa.
     * Somente Owner, Manager do projeto ou criador da tarefa.
     */
    public function delete(User $user, Task $task): bool
    {
        $project = $task->project;

        if ($project->owner_id === $user->id || $task->created_by === $user->id) {
            return true;
        }

        $role = $project->getUserRole($user);

        return in_array($role, [ProjectRole::OWNER, ProjectRole::MANAGER], true);
    }
}
