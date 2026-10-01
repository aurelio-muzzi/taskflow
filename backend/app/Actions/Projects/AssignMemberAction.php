<?php

namespace App\Actions\Projects;

use App\DTOs\Projects\AssignMemberDTO;
use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Validation\ValidationException;

class AssignMemberAction
{
    /**
     * Adiciona ou atualiza a função de um membro na equipe do projeto.
     *
     * @throws ValidationException
     */
    public function execute(Project $project, AssignMemberDTO $dto): ProjectMember
    {
        // Se for o responsável pelo projeto, ele deve permanecer OWNER
        if ($project->owner_id === $dto->userId && $dto->role !== ProjectRole::OWNER) {
            throw ValidationException::withMessages([
                'role' => ['O responsável principal pelo projeto deve manter a função de Proprietário.'],
            ]);
        }

        /** @var ProjectMember $member */
        $member = $project->memberRecords()->updateOrCreate(
            ['user_id' => $dto->userId],
            ['role' => $dto->role]
        );

        return $member->load('user.role');
    }
}
