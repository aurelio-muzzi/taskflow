<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Comments\CreateTaskCommentAction;
use App\Actions\Comments\DeleteTaskCommentAction;
use App\DTOs\Comments\CreateTaskCommentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Comments\CreateTaskCommentRequest;
use App\Http\Resources\V1\TaskCommentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Actions\Comments\UpdateTaskCommentAction;
use App\Http\Requests\V1\Comments\UpdateTaskCommentRequest;

class TaskCommentController extends Controller
{
    /**
     * Lista comentários de uma tarefa.
     */
    public function index(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $comments = $task->comments()
            ->with(['user.role'])
            ->orderBy('created_at', 'asc')
            ->get();

        return ApiResponse::success(
            data: TaskCommentResource::collection($comments),
            message: 'Comentários recuperados com sucesso.'
        );
    }

    /**
     * Adiciona um novo comentário à tarefa.
     */
    public function store(CreateTaskCommentRequest $request, Task $task, CreateTaskCommentAction $action): JsonResponse
    {
        $dto = CreateTaskCommentDTO::fromRequest(
            data: $request->validated(),
            taskId: $task->id,
            userId: $request->user()->id
        );

        $comment = $action->execute($dto);

        return ApiResponse::success(
            data: new TaskCommentResource($comment),
            message: 'Comentário adicionado com sucesso.',
            status: Response::HTTP_CREATED
        );
    }

    /**
     * Atualiza o conteúdo de um comentário da tarefa.
     */
    public function update(UpdateTaskCommentRequest $request, ...$args): JsonResponse
    {
        $comment = null;
        $action = null;

        foreach ($args as $arg) {
            if ($arg instanceof TaskComment) {
                $comment = $arg;
            } elseif ($arg instanceof UpdateTaskCommentAction) {
                $action = $arg;
            }
        }

        if (! $comment) {
            $commentId = $request->route('comment');
            $comment = $commentId instanceof TaskComment ? $commentId : TaskComment::findOrFail($commentId);
        }

        $action ??= app(UpdateTaskCommentAction::class);

        $this->authorize('update', $comment);

        $updatedComment = $action->execute($comment, (string) $request->validated('content'));

        return ApiResponse::success(
            data: new TaskCommentResource($updatedComment),
            message: 'Comentário atualizado com sucesso.'
        );
    }

    /**
     * Remove um comentário da tarefa.
     */
    public function destroy(Request $request, ...$args): JsonResponse
    {
        $comment = null;
        $action = null;

        foreach ($args as $arg) {
            if ($arg instanceof TaskComment) {
                $comment = $arg;
            } elseif ($arg instanceof DeleteTaskCommentAction) {
                $action = $arg;
            }
        }

        if (! $comment) {
            $commentId = $request->route('comment');
            $comment = $commentId instanceof TaskComment ? $commentId : TaskComment::findOrFail($commentId);
        }

        $action ??= app(DeleteTaskCommentAction::class);

        $this->authorize('delete', $comment);

        $action->execute($comment);

        return ApiResponse::success(
            data: null,
            message: 'Comentário removido com sucesso.'
        );
    }
}
