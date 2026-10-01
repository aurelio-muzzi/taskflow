<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Http\Resources\V1\AuditLogResource;
use App\Http\Resources\V1\TaskResource;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class DashboardMetricsService
{
    /**
     * Calcula métricas e KPIs consolidados respeitando o escopo RBAC do usuário.
     */
    public function getMetrics(User $user): array
    {
        $isAdmin = $user->isAdmin();

        // Query base para projetos com controle de acesso
        $projectQuery = Project::query();
        if (! $isAdmin) {
            $projectQuery->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('memberRecords', fn ($mq) => $mq->where('user_id', $user->id));
            });
        }

        // IDs dos projetos acessíveis para filtrar tarefas
        $accessibleProjectIds = (clone $projectQuery)->pluck('id');

        // Query base para tarefas acessíveis
        $taskQuery = Task::whereIn('project_id', $accessibleProjectIds);

        // 1. Métricas de Projetos
        $totalProjects = (clone $projectQuery)->count();
        $projectsByStatus = (clone $projectQuery)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 2. Métricas de Tarefas
        $totalTasks = (clone $taskQuery)->count();
        $completedTasks = (clone $taskQuery)->where('status', TaskStatus::DONE)->count();
        $pendingTasks = $totalTasks - $completedTasks;

        $today = now()->format('Y-m-d');
        $overdueTasks = (clone $taskQuery)
            ->where('status', '!=', TaskStatus::DONE)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->count();

        $tasksByStatus = (clone $taskQuery)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $tasksByPriority = (clone $taskQuery)
            ->selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0.0;

        // 3. Métricas das tarefas atribuídas especificamente ao usuário autenticado
        $myTaskQuery = (clone $taskQuery)->where('assigned_to', $user->id);
        $myTotalTasks = (clone $myTaskQuery)->count();
        $myCompletedTasks = (clone $myTaskQuery)->where('status', TaskStatus::DONE)->count();
        $myPendingTasks = $myTotalTasks - $myCompletedTasks;
        $myOverdueTasks = (clone $myTaskQuery)
            ->where('status', '!=', TaskStatus::DONE)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->count();

        $myTasksByStatus = (clone $myTaskQuery)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 4. Próximos prazos de entrega (deadlines)
        $upcomingDeadlines = (clone $taskQuery)
            ->with(['project', 'assignee.role'])
            ->where('status', '!=', TaskStatus::DONE)
            ->whereNotNull('due_date')
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        // 5. Atividades recentes (trilha de auditoria)
        $recentAuditQuery = AuditLog::with(['user.role']);
        if (! $isAdmin) {
            $recentAuditQuery->where(function ($aq) use ($accessibleProjectIds) {
                $aq->where(function ($sub) use ($accessibleProjectIds) {
                    $sub->where('auditable_type', Project::class)
                        ->whereIn('auditable_id', $accessibleProjectIds);
                })->orWhere(function ($sub) use ($accessibleProjectIds) {
                    $sub->where('auditable_type', Task::class)
                        ->whereHasMorph('auditable', [Task::class], function ($tq) use ($accessibleProjectIds) {
                            $tq->whereIn('project_id', $accessibleProjectIds);
                        });
                });
            });
        }

        $recentActivities = $recentAuditQuery
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return [
            'projects' => [
                'total' => $totalProjects,
                'active' => $projectsByStatus[ProjectStatus::ACTIVE->value] ?? 0,
                'planning' => $projectsByStatus[ProjectStatus::PLANNING->value] ?? 0,
                'completed' => $projectsByStatus[ProjectStatus::COMPLETED->value] ?? 0,
                'on_hold' => $projectsByStatus[ProjectStatus::ON_HOLD->value] ?? 0,
                'archived' => $projectsByStatus[ProjectStatus::ARCHIVED->value] ?? 0,
                'by_status' => $projectsByStatus,
            ],
            'tasks' => [
                'total' => $totalTasks,
                'pending' => $pendingTasks,
                'completed' => $completedTasks,
                'overdue' => $overdueTasks,
                'completion_rate' => $completionRate,
                'by_status' => [
                    'todo' => $tasksByStatus[TaskStatus::TODO->value] ?? 0,
                    'in_progress' => $tasksByStatus[TaskStatus::IN_PROGRESS->value] ?? 0,
                    'review' => $tasksByStatus[TaskStatus::REVIEW->value] ?? 0,
                    'done' => $tasksByStatus[TaskStatus::DONE->value] ?? 0,
                ],
                'by_priority' => [
                    'low' => $tasksByPriority['low'] ?? 0,
                    'medium' => $tasksByPriority['medium'] ?? 0,
                    'high' => $tasksByPriority['high'] ?? 0,
                    'urgent' => $tasksByPriority['urgent'] ?? 0,
                ],
            ],
            'my_tasks' => [
                'total' => $myTotalTasks,
                'pending' => $myPendingTasks,
                'completed' => $myCompletedTasks,
                'overdue' => $myOverdueTasks,
                'by_status' => [
                    'todo' => $myTasksByStatus[TaskStatus::TODO->value] ?? 0,
                    'in_progress' => $myTasksByStatus[TaskStatus::IN_PROGRESS->value] ?? 0,
                    'review' => $myTasksByStatus[TaskStatus::REVIEW->value] ?? 0,
                    'done' => $myTasksByStatus[TaskStatus::DONE->value] ?? 0,
                ],
            ],
            'upcoming_deadlines' => TaskResource::collection($upcomingDeadlines),
            'recent_activities' => AuditLogResource::collection($recentActivities),
        ];
    }
}
