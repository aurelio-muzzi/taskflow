<?php

namespace App\Http\Requests\V1\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user()?->can('update', $project) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->route('project');
        $projectId = $project instanceof Project ? $project->id : (int) $project;

        $startDate = $this->input('start_date') ?: ($project instanceof Project ? $project->start_date?->format('Y-m-d') : null);

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', 'alpha_dash', Rule::unique('projects')->ignore($projectId)],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', 'string', Rule::enum(ProjectStatus::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', $startDate ? "after_or_equal:{$startDate}" : 'nullable'],
            'owner_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Custom message for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do projeto é obrigatório.',
            'code.required' => 'O código do projeto é obrigatório.',
            'code.unique' => 'Este código de projeto já está em uso.',
            'due_date.after_or_equal' => 'A data prevista de conclusão deve ser igual ou posterior à data de início.',
            'owner_id.exists' => 'O responsável selecionado é inválido.',
        ];
    }
}
