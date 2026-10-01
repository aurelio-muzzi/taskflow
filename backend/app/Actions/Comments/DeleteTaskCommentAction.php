<?php

namespace App\Actions\Comments;

use App\Models\TaskComment;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class DeleteTaskCommentAction
{
    public function execute(TaskComment $comment): bool
    {
        return DB::transaction(function () use ($comment) {
            $task = $comment->task;

            if ($task) {
                AuditLogService::log(
                    auditable: $task,
                    event: 'comment_deleted',
                    description: 'Comentário removido da tarefa.',
                    oldValues: ['comment_id' => $comment->id]
                );
            }

            return (bool) $comment->delete();
        });
    }
}
