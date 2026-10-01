<?php

namespace App\Actions\Tasks;

use App\DTOs\Tasks\UpdateTaskStatusDTO;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class UpdateTaskStatusAction
{
    public function execute(Task $task, UpdateTaskStatusDTO $dto): Task
    {
        return DB::transaction(function () use ($task, $dto) {
            $data = ['status' => $dto->status];

            if ($dto->status === TaskStatus::DONE && ! $task->completed_at) {
                $data['completed_at'] = now();
            } elseif ($dto->status !== TaskStatus::DONE && $task->completed_at) {
                $data['completed_at'] = null;
            }

            if ($dto->order !== null) {
                $data['order'] = $dto->order;
            }

            $oldStatus = $task->status->value;
            $task->update($data);

            AuditLogService::log(
                auditable: $task,
                event: 'status_changed',
                description: "Status da tarefa alterado para '{$dto->status->label()}'.",
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $dto->status->value]
            );

            if ($task->assigned_to) {
                NotificationService::send(
                    userId: $task->assigned_to,
                    type: 'task_status_changed',
                    title: 'Status de tarefa atualizado',
                    message: "A tarefa '{$task->title}' mudou para '{$dto->status->label()}'.",
                    data: ['task_id' => $task->id, 'new_status' => $dto->status->value]
                );
            }

            return $task->fresh(['project', 'assignee', 'creator']);
        });
    }
}
