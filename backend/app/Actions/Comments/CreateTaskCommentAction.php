<?php

namespace App\Actions\Comments;

use App\DTOs\Comments\CreateTaskCommentDTO;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class CreateTaskCommentAction
{
    public function execute(CreateTaskCommentDTO $dto): TaskComment
    {
        return DB::transaction(function () use ($dto) {
            $comment = TaskComment::create([
                'task_id' => $dto->taskId,
                'user_id' => $dto->userId,
                'content' => $dto->content,
            ]);

            $task = Task::with('project')->find($dto->taskId);

            if ($task) {
                AuditLogService::log(
                    auditable: $task,
                    event: 'comment_added',
                    description: 'Novo comentário adicionado à tarefa.',
                    newValues: ['comment_id' => $comment->id],
                    userId: $dto->userId
                );

                // Notificar responsável caso não seja o autor do comentário
                if ($task->assigned_to && $task->assigned_to !== $dto->userId) {
                    NotificationService::send(
                        userId: $task->assigned_to,
                        type: 'task_comment',
                        title: 'Novo comentário na tarefa',
                        message: "Novo comentário adicionado à tarefa '{$task->title}'.",
                        data: ['task_id' => $task->id, 'comment_id' => $comment->id]
                    );
                }

                // Notificar criador da tarefa se diferente do autor e do responsável
                if ($task->created_by && $task->created_by !== $dto->userId && $task->created_by !== $task->assigned_to) {
                    NotificationService::send(
                        userId: $task->created_by,
                        type: 'task_comment',
                        title: 'Novo comentário na tarefa',
                        message: "Novo comentário adicionado à tarefa '{$task->title}'.",
                        data: ['task_id' => $task->id, 'comment_id' => $comment->id]
                    );
                }
            }

            return $comment->load('user.role');
        });
    }
}
