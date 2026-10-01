<?php

namespace App\Services;

use App\Http\Resources\V1\ProjectResource;
use App\Http\Resources\V1\TaskResource;
use App\Http\Resources\V1\UserResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class GlobalSearchService
{
    /**
     * Realiza busca combinada em Projetos, Tarefas e Usuários.
     */
    public function search(User $user, string $query, int $limit = 5): array
    {
        $term = trim($query);
        if (strlen($term) < 2) {
            return [
                'projects' => [],
                'tasks' => [],
                'users' => [],
            ];
        }

        $isAdmin = $user->isAdmin();

        // 1. Busca em Projetos
        $projectQuery = Project::query();
        if (! $isAdmin) {
            $projectQuery->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('memberRecords', fn ($mq) => $mq->where('user_id', $user->id));
            });
        }
        $projects = $projectQuery->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%");
        })->limit($limit)->get();

        // 2. Busca em Tarefas
        $taskQuery = Task::with(['project', 'assignee.role']);
        if (! $isAdmin) {
            $accessibleProjectIds = Project::where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('memberRecords', fn ($mq) => $mq->where('user_id', $user->id));
            })->pluck('id');

            $taskQuery->whereIn('project_id', $accessibleProjectIds);
        }
        $tasks = $taskQuery->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        })->limit($limit)->get();

        // 3. Busca em Usuários (se admin/manager, ou membros de projetos compartilhados)
        $userQuery = User::with('role');
        $users = $userQuery->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        })->limit($limit)->get();

        return [
            'projects' => ProjectResource::collection($projects),
            'tasks' => TaskResource::collection($tasks),
            'users' => UserResource::collection($users),
        ];
    }
}
