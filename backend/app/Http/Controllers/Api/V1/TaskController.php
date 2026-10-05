<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tasks\CreateTaskAction;
use App\Actions\Tasks\DeleteTaskAction;
use App\Actions\Tasks\ReorderTasksAction;
use App\Actions\Tasks\UpdateTaskAction;
use App\Actions\Tasks\UpdateTaskStatusAction;
use App\DTOs\Tasks\CreateTaskDTO;
use App\DTOs\Tasks\ReorderTasksDTO;
use App\DTOs\Tasks\UpdateTaskDTO;
use App\DTOs\Tasks\UpdateTaskStatusDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Tasks\CreateTaskRequest;
use App\Http\Requests\V1\Tasks\ReorderTasksRequest;
use App\Http\Requests\V1\Tasks\UpdateTaskRequest;
use App\Http\Requests\V1\Tasks\UpdateTaskStatusRequest;
use App\Http\Resources\V1\TaskResource;
use App\Http\Responses\ApiResponse;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    /**
     * Lista tarefas globais do usuário com filtros, busca e paginação.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $query = Task::with(['project', 'assignee.role', 'creator.role']);

        // Controle de escopo: Usuário só visualiza tarefas de projetos aos quais tem acesso
        if (! $currentUser->isAdmin()) {
            $query->whereHas('project', function ($pq) use ($currentUser) {
                $pq->where('owner_id', $currentUser->id)
                    ->orWhereHas('memberRecords', fn ($mq) => $mq->where('user_id', $currentUser->id));
            });
        }

        // Filtro por projeto
        if ($projectId = $request->input('project_id')) {
            $query->where('project_id', $projectId);
        }

        // Filtros combinados e busca
        $query->search($request->input('q'))
            ->filterStatus($request->input('status'))
            ->filterPriority($request->input('priority'))
            ->filterAssignedTo($request->input('assigned_to'));

        // Ordenação
        $allowedSorts = ['title', 'status', 'priority', 'due_date', 'order', 'created_at'];
        $sortBy = in_array($request->input('sort_by'), $allowedSorts, true) ? $request->input('sort_by') : 'order';
        $direction = strtolower((string) $request->input('direction')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $direction);

        $perPage = min((int) $request->input('per_page', 15), 100);
        $tasks = $query->paginate($perPage);

        return ApiResponse::paginated(
            TaskResource::collection($tasks->items()),
            $tasks,
            'Tarefas recuperadas com sucesso.'
        );
    }

    /**
     * Lista tarefas de um projeto específico.
     */
    public function indexByProject(Request $request, Project $project): JsonResponse
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $query = $project->tasks()->with(['assignee.role', 'creator.role']);

        $query->search($request->input('q'))
            ->filterStatus($request->input('status'))
            ->filterPriority($request->input('priority'))
            ->filterAssignedTo($request->input('assigned_to'));

        $query->orderBy('order', 'asc')->orderBy('created_at', 'desc');

        if ($request->boolean('all')) {
            $tasks = $query->get();

            return ApiResponse::success(
                TaskResource::collection($tasks),
                'Tarefas do projeto recuperadas com sucesso.'
            );
        }

        $perPage = min((int) $request->input('per_page', 25), 100);
        $tasks = $query->paginate($perPage);

        return ApiResponse::paginated(
            TaskResource::collection($tasks->items()),
            $tasks,
            'Tarefas do projeto recuperadas com sucesso.'
        );
    }

    /**
     * Cria uma nova tarefa dentro de um projeto.
     */
    public function store(CreateTaskRequest $request, Project $project, CreateTaskAction $action): JsonResponse
    {
        $dto = CreateTaskDTO::fromRequest(
            data: $request->validated(),
            projectId: $project->id,
            createdBy: $request->user()->id
        );

        $task = $action->execute($dto);
        $task->load(['project', 'assignee.role', 'creator.role']);

        return ApiResponse::success(
            data: new TaskResource($task),
            message: 'Tarefa criada com sucesso.',
            status: Response::HTTP_CREATED
        );
    }

    /**
     * Exibe os detalhes de uma tarefa específica.
     */
    public function show(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->load(['project', 'assignee.role', 'creator.role']);

        return ApiResponse::success(
            data: new TaskResource($task),
            message: 'Detalhes da tarefa recuperados com sucesso.'
        );
    }

    /**
     * Atualiza uma tarefa existente.
     */
    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $action): JsonResponse
    {
        $dto = UpdateTaskDTO::fromRequest($request->validated());

        $updatedTask = $action->execute($task, $dto);

        return ApiResponse::success(
            data: new TaskResource($updatedTask),
            message: 'Tarefa atualizada com sucesso.'
        );
    }

    /**
     * Atualiza rapidamente o status e/ou ordenação da tarefa.
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task, UpdateTaskStatusAction $action): JsonResponse
    {
        $dto = UpdateTaskStatusDTO::fromRequest($request->validated());

        $updatedTask = $action->execute($task, $dto);

        return ApiResponse::success(
            data: new TaskResource($updatedTask),
            message: 'Status da tarefa atualizado com sucesso.'
        );
    }

    /**
     * Remove (soft delete) uma tarefa.
     */
    public function destroy(Request $request, Task $task, DeleteTaskAction $action): JsonResponse
    {
        $this->authorize('delete', $task);

        $action->execute($task);

        return ApiResponse::success(
            data: null,
            message: 'Tarefa removida com sucesso.'
        );
    }

    /**
     * Reordena tarefas e atualiza status em lote no quadro Kanban.
     */
    public function reorder(ReorderTasksRequest $request, Project $project, ReorderTasksAction $action): JsonResponse
    {
        $dto = ReorderTasksDTO::fromRequest($request->validated());

        $updatedTasks = $action->execute($project, $dto);

        return ApiResponse::success(
            data: TaskResource::collection($updatedTasks),
            message: 'Tarefas reordenadas com sucesso.'
        );
    }
}
