<?php

namespace App\Actions\Projects;

use App\DTOs\Projects\UpdateProjectDTO;
use App\Enums\ProjectRole;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class UpdateProjectAction
{
    /**
     * Atualiza os dados de um projeto existente.
     */
    public function execute(Project $project, UpdateProjectDTO $dto): Project
    {
        return DB::transaction(function () use ($project, $dto) {
            $data = [];

            if ($dto->name !== null) {
                $data['name'] = $dto->name;
            }

            if ($dto->code !== null) {
                $data['code'] = $dto->code;
            }

            if ($dto->description !== null) {
                $data['description'] = $dto->description;
            }

            if ($dto->status !== null) {
                $data['status'] = $dto->status;
            }

            if ($dto->startDate !== null) {
                $data['start_date'] = $dto->startDate;
            }

            if ($dto->dueDate !== null) {
                $data['due_date'] = $dto->dueDate;
            }

            if ($dto->ownerId !== null && $dto->ownerId !== $project->owner_id) {
                $data['owner_id'] = $dto->ownerId;

                // Garante que o novo owner tenha papel OWNER na equipe
                $project->memberRecords()->updateOrCreate(
                    ['user_id' => $dto->ownerId],
                    ['role' => ProjectRole::OWNER]
                );
            }

            if (! empty($data)) {
                $project->update($data);
            }

            return $project->fresh()->load(['owner.role', 'memberRecords.user.role']);
        });
    }
}
