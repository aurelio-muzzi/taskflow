<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Projects\AssignMemberAction;
use App\Actions\Projects\RemoveMemberAction;
use App\DTOs\Projects\AssignMemberDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Projects\AssignMemberRequest;
use App\Http\Resources\V1\ProjectMemberResource;
use App\Http\Responses\ApiResponse;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProjectMemberController extends Controller
{
    /**
     * Lista todos os membros vinculados ao projeto.
     */
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $members = $project->memberRecords()->with('user.role')->get();

        return ApiResponse::success(
            ProjectMemberResource::collection($members),
            'Membros do projeto recuperados com sucesso.'
        );
    }

    /**
     * Adiciona ou atualiza a função de um membro na equipe do projeto.
     */
    public function store(AssignMemberRequest $request, Project $project, AssignMemberAction $action): JsonResponse
    {
        $dto = AssignMemberDTO::fromRequest($request->validated());
        $member = $action->execute($project, $dto);

        return ApiResponse::success(
            new ProjectMemberResource($member),
            'Membro vinculado ao projeto com sucesso.',
            Response::HTTP_CREATED
        );
    }

    /**
     * Atualiza a função de um membro na equipe do projeto.
     */
    public function update(AssignMemberRequest $request, Project $project, int $userId, AssignMemberAction $action): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $userId;

        $dto = AssignMemberDTO::fromRequest($data);
        $member = $action->execute($project, $dto);

        return ApiResponse::success(
            new ProjectMemberResource($member),
            'Função do membro atualizada com sucesso.'
        );
    }

    /**
     * Remove um membro da equipe do projeto.
     */
    public function destroy(Project $project, int $userId, RemoveMemberAction $action): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $action->execute($project, $userId);

        return ApiResponse::success(null, 'Membro removido da equipe do projeto com sucesso.');
    }
}
