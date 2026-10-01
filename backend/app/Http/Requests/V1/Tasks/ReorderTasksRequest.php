<?php

namespace App\Http\Requests\V1\Tasks;

use App\Enums\ProjectRole;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderTasksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $project = $this->route('project');

        if (! $project instanceof Project) {
            $project = Project::find($project);
        }

        if (! $project) {
            return false;
        }

        if ($project->owner_id === $user->id) {
            return true;
        }

        $role = $project->getUserRole($user);

        return in_array($role, [ProjectRole::OWNER, ProjectRole::MANAGER, ProjectRole::MEMBER], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tasks' => ['required', 'array', 'min:1'],
            'tasks.*.id' => ['required', 'integer', 'exists:tasks,id'],
            'tasks.*.order' => ['required', 'integer', 'min:0'],
            'tasks.*.status' => ['nullable', 'string', Rule::enum(TaskStatus::class)],
        ];
    }

    /**
     * Custom message for validation errors.
     */
    public function messages(): array
    {
        return [
            'tasks.required' => 'A lista de tarefas a reordenar é obrigatória.',
            'tasks.array' => 'A lista de tarefas deve ser um array válido.',
            'tasks.*.id.required' => 'O ID de cada tarefa é obrigatório.',
            'tasks.*.id.exists' => 'Uma ou mais tarefas informadas não foram encontradas.',
            'tasks.*.order.required' => 'A ordem de cada tarefa é obrigatória.',
        ];
    }
}
