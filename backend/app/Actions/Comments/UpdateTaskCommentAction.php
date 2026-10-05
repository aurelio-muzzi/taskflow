<?php

namespace App\Actions\Comments;

use App\Models\TaskComment;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class UpdateTaskCommentAction
{
    public function execute(TaskComment $comment, string $content): TaskComment
    {
        return DB::transaction(function () use ($comment, $content) {
            $oldContent = $comment->content;

            $comment->update([
                'content' => $content,
            ]);

            $task = $comment->task;

            if ($task) {
                AuditLogService::log(
                    auditable: $task,
                    event: 'comment_updated',
                    description: 'Comentário editado.',
                    oldValues: ['content' => $oldContent],
                    newValues: ['content' => $content],
                    userId: $comment->user_id
                );
            }

            return $comment->load('user.role');
        });
    }
}
