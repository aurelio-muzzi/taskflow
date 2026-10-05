<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;

class TaskCommentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Qualquer membro ou observador da equipe do projeto pode comentar na tarefa.
     */
    public function create(User $user, Task $task): bool
    {
        return $task->project->hasMember($user);
    }

    /**
     * O autor do comentário pode editar seu próprio comentário.
     */
    public function update(User $user, TaskComment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    /**
     * O autor do comentário, ou os gestores/proprietários do projeto, podem excluir o comentário.
     */
    public function delete(User $user, TaskComment $comment): bool
    {
        if ($comment->user_id === $user->id) {
            return true;
        }

        $project = $comment->task?->project;

        if (! $project) {
            return false;
        }

        if ($project->owner_id === $user->id) {
            return true;
        }

        $role = $project->getUserRole($user);

        return in_array($role, [ProjectRole::OWNER, ProjectRole::MANAGER], true);
    }
}
