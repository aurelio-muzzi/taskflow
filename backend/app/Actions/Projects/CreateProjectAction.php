<?php

namespace App\Actions\Projects;

use App\DTOs\Projects\CreateProjectDTO;
use App\Enums\ProjectRole;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class CreateProjectAction
{
    /**
     * Cria um projeto e vincula seu responsável como OWNER da equipe.
     */
    public function execute(CreateProjectDTO $dto): Project
    {
        return DB::transaction(function () use ($dto) {
            $project = Project::create([
                'owner_id' => $dto->ownerId,
                'name' => $dto->name,
                'code' => $dto->code,
                'description' => $dto->description,
                'status' => $dto->status,
                'start_date' => $dto->startDate,
                'due_date' => $dto->dueDate,
            ]);

            // Garante que o responsável seja registrado como OWNER do projeto
            $project->memberRecords()->create([
                'user_id' => $dto->ownerId,
                'role' => ProjectRole::OWNER,
            ]);

            // Registra membros adicionais se informados
            foreach ($dto->members as $memberData) {
                $userId = (int) ($memberData['user_id'] ?? 0);
                if ($userId && $userId !== $dto->ownerId) {
                    $role = isset($memberData['role']) ? ProjectRole::from((string) $memberData['role']) : ProjectRole::MEMBER;
                    $project->memberRecords()->firstOrCreate(
                        ['user_id' => $userId],
                        ['role' => $role]
                    );
                }
            }

            return $project->load(['owner.role', 'memberRecords.user.role']);
        });
    }
}
