<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Projects\CreateProjectAction;
use App\Actions\Projects\UpdateProjectAction;
use App\DTOs\Projects\CreateProjectDTO;
use App\DTOs\Projects\UpdateProjectDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Projects\CreateProjectRequest;
use App\Http\Requests\V1\Projects\UpdateProjectRequest;
use App\Http\Resources\V1\ProjectResource;
use App\Http\Responses\ApiResponse;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    /**
     * Lista projetos com busca, filtros de status, ordenação e paginação.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        /** @var User $currentUser */
        $currentUser = $request->user();
        $query = Project::with(['owner.role', 'memberRecords.user.role']);

        // Controle de escopo de visualização (RBAC):
        // Admins visualizam todos; Gerentes e Usuários visualizam projetos onde são responsáveis ou membros.
        if (! $currentUser->isAdmin()) {
            $query->where(function ($q) use ($currentUser) {
                $q->where('owner_id', $currentUser->id)
                    ->orWhereHas('memberRecords', fn ($mq) => $mq->where('user_id', $currentUser->id));
            });
        }

        // Busca por nome ou código
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filtro por status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Ordenação segura
        $allowedSorts = ['name', 'code', 'status', 'due_date', 'created_at'];
        $sortBy = in_array($request->input('sort_by'), $allowedSorts, true) ? $request->input('sort_by') : 'created_at';
        $direction = strtolower((string) $request->input('direction')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $direction);

        // Paginação
        $perPage = min(max((int) $request->input('per_page', 12), 1), 100);
        $paginated = $query->paginate($perPage);

        return ApiResponse::success([
            'items' => ProjectResource::collection($paginated->items()),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ], 'Projetos listados com sucesso.');
    }

    /**
     * Cria um novo projeto e vincula o usuário criador como OWNER.
     */
    public function store(CreateProjectRequest $request, CreateProjectAction $action): JsonResponse
    {
        $dto = CreateProjectDTO::fromRequest($request->validated(), (int) $request->user()?->id);
        $project = $action->execute($dto);

        return ApiResponse::success(
            new ProjectResource($project),
            'Projeto criado com sucesso.',
            Response::HTTP_CREATED
        );
    }

    /**
     * Exibe os detalhes de um projeto específico.
     */
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project->load(['owner.role', 'memberRecords.user.role']);

        return ApiResponse::success(
            new ProjectResource($project),
            'Projeto recuperado com sucesso.'
        );
    }

    /**
     * Atualiza os dados de um projeto existente.
     */
    public function update(UpdateProjectRequest $request, Project $project, UpdateProjectAction $action): JsonResponse
    {
        $dto = UpdateProjectDTO::fromRequest($request->validated());
        $updatedProject = $action->execute($project, $dto);

        return ApiResponse::success(
            new ProjectResource($updatedProject),
            'Projeto atualizado com sucesso.'
        );
    }

    /**
     * Remove logicamente (Soft Delete) um projeto.
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return ApiResponse::success(null, 'Projeto removido com sucesso.');
    }
}
