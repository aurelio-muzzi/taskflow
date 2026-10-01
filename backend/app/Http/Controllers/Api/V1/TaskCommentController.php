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
     * Remove um comentário da tarefa.
     */
    public function destroy(Request $request, TaskComment $comment, DeleteTaskCommentAction $action): JsonResponse
    {
        $this->authorize('delete', $comment);

        $action->execute($comment);

        return ApiResponse::success(
            data: null,
            message: 'Comentário removido com sucesso.'
        );
    }
}
