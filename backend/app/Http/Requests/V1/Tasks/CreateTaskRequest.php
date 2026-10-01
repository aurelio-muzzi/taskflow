<?php

namespace App\Http\Requests\V1\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            $project = Project::find($project);
        }

        if (! $project) {
            return false;
        }

        return $this->user()?->can('create', [Task::class, $project]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', 'string', Rule::enum(TaskPriority::class)],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Custom message for validation errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'O título da tarefa é obrigatório.',
            'assigned_to.exists' => 'O usuário responsável selecionado é inválido.',
            'estimated_hours.numeric' => 'As horas estimadas devem ser um valor numérico válido.',
            'estimated_hours.min' => 'As horas estimadas não podem ser negativas.',
        ];
    }
}
