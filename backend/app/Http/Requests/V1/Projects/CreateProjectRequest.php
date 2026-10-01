<?php

namespace App\Http\Requests\V1\Projects;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Project::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:projects,code'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::enum(ProjectStatus::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'members' => ['nullable', 'array'],
            'members.*.user_id' => ['required_with:members', 'integer', 'exists:users,id'],
            'members.*.role' => ['nullable', 'string', Rule::enum(ProjectRole::class)],
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
            'code.alpha_dash' => 'O código do projeto deve conter apenas letras, números, hífens e sublinhados.',
            'due_date.after_or_equal' => 'A data prevista de conclusão deve ser igual ou posterior à data de início.',
            'owner_id.exists' => 'O responsável selecionado é inválido.',
        ];
    }
}
