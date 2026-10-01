<?php

namespace App\Actions\Tasks;

use App\DTOs\Tasks\ReorderTasksDTO;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ReorderTasksAction
{
    /**
     * Reordena e/ou altera o status de tarefas de forma atômica.
     *
     * @return Collection<int, Task>
     */
    public function execute(Project $project, ReorderTasksDTO $dto): Collection
    {
        return DB::transaction(function () use ($project, $dto) {
            $updatedTasks = new Collection;

            foreach ($dto->items as $item) {
                /** @var Task|null $task */
                $task = Task::where('project_id', $project->id)->find($item->id);

                if (! $task) {
                    continue;
                }

                $data = ['order' => $item->order];

                if ($item->status !== null && $item->status !== $task->status) {
                    $oldStatus = $task->status->value;
                    $data['status'] = $item->status;

                    if ($item->status === TaskStatus::DONE && ! $task->completed_at) {
                        $data['completed_at'] = now();
                    } elseif ($item->status !== TaskStatus::DONE && $task->completed_at) {
                        $data['completed_at'] = null;
                    }

                    AuditLogService::log(
                        auditable: $task,
                        event: 'status_changed',
                        description: "Status da tarefa alterado para '{$item->status->label()}' via Kanban.",
                        oldValues: ['status' => $oldStatus],
                        newValues: ['status' => $item->status->value]
                    );

                    if ($task->assigned_to) {
                        NotificationService::send(
                            userId: $task->assigned_to,
                            type: 'task_status_changed',
                            title: 'Status de tarefa atualizado no Kanban',
                            message: "A tarefa '{$task->title}' mudou para '{$item->status->label()}'.",
                            data: ['task_id' => $task->id, 'new_status' => $item->status->value]
                        );
                    }
                }

                $task->update($data);
                $updatedTasks->push($task->fresh(['project', 'assignee', 'creator']));
            }

            return $updatedTasks;
        });
    }
}
