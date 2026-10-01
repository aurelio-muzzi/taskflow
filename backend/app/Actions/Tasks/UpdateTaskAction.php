<?php

namespace App\Actions\Tasks;

use App\DTOs\Tasks\UpdateTaskDTO;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class UpdateTaskAction
{
    public function execute(Task $task, UpdateTaskDTO $dto): Task
    {
        return DB::transaction(function () use ($task, $dto) {
            $data = [];

            if ($dto->title !== null) {
                $data['title'] = $dto->title;
            }

            if ($dto->description !== null || array_key_exists('description', get_object_vars($dto))) {
                $data['description'] = $dto->description;
            }

            if ($dto->status !== null) {
                $data['status'] = $dto->status;

                if ($dto->status === TaskStatus::DONE && ! $task->completed_at) {
                    $data['completed_at'] = now();
                } elseif ($dto->status !== TaskStatus::DONE && $task->completed_at) {
                    $data['completed_at'] = null;
                }
            }

            if ($dto->priority !== null) {
                $data['priority'] = $dto->priority;
            }

            if ($dto->assignedTo !== null) {
                $data['assigned_to'] = $dto->assignedTo;
            } elseif ($dto->clearAssignedTo) {
                $data['assigned_to'] = null;
            }

            if ($dto->dueDate !== null) {
                $data['due_date'] = $dto->dueDate;
            }

            if ($dto->estimatedHours !== null) {
                $data['estimated_hours'] = $dto->estimatedHours;
            }

            if ($dto->order !== null) {
                $data['order'] = $dto->order;
            }

            $task->update($data);

            return $task->fresh(['project', 'assignee', 'creator']);
        });
    }
}
