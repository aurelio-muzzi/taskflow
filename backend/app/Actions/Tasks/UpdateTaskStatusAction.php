<?php

namespace App\Actions\Tasks;

use App\DTOs\Tasks\UpdateTaskStatusDTO;
use App\Enums\TaskStatus;
use App\Models\Task;
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

            $task->update($data);

            return $task->fresh(['project', 'assignee', 'creator']);
        });
    }
}
