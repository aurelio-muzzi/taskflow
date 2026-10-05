<?php

namespace App\Http\Requests\V1\Projects;

use App\Enums\ProjectRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user()?->can('manageMembers', $project) ?? false;
    }

    /**
     * Prepara os dados para validação mesclando parâmetro de rota se necessário.
     */
    protected function prepareForValidation(): void
    {
        if ($this->route('user') && ! $this->has('user_id')) {
            $userParam = $this->route('user');
            $userId = is_object($userParam) ? $userParam->id : (int) $userParam;
            $this->merge(['user_id' => $userId]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'string', Rule::enum(ProjectRole::class)],
        ];
    }

    /**
     * Custom message for validation errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'O usuário é obrigatório.',
            'user_id.exists' => 'O usuário selecionado não existe.',
            'role.required' => 'O papel do membro no projeto é obrigatório.',
        ];
    }
}
