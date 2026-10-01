<?php

namespace App\Actions\Projects;

use App\Models\Project;
use Illuminate\Validation\ValidationException;

class RemoveMemberAction
{
    /**
     * Remove um membro da equipe do projeto.
     *
     * @throws ValidationException
     */
    public function execute(Project $project, int $userId): void
    {
        if ($project->owner_id === $userId) {
            throw ValidationException::withMessages([
                'user_id' => ['Não é possível remover o proprietário responsável pelo projeto.'],
            ]);
        }

        $project->memberRecords()->where('user_id', $userId)->delete();
    }
}
