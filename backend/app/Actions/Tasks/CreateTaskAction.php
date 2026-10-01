<?php

namespace App\Actions\Tasks;

use App\DTOs\Tasks\CreateTaskDTO;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class CreateTaskAction
{
    public function execute(CreateTaskDTO $dto): Task
    {
        return DB::transaction(function () use ($dto) {
            $order = $dto->order;

            if ($order === 0) {
                $maxOrder = Task::where('project_id', $dto->projectId)
                    ->where('status', $dto->status)
                    ->max('order');
                $order = is_null($maxOrder) ? 0 : $maxOrder + 1;
            }

            $completedAt = $dto->status === TaskStatus::DONE ? now() : null;

            return Task::create([
                'project_id' => $dto->projectId,
                'created_by' => $dto->createdBy,
                'title' => $dto->title,
                'description' => $dto->description,
                'status' => $dto->status,
                'priority' => $dto->priority,
                'assigned_to' => $dto->assignedTo,
                'due_date' => $dto->dueDate,
                'estimated_hours' => $dto->estimatedHours,
                'order' => $order,
                'completed_at' => $completedAt,
            ]);
        });
    }
}
