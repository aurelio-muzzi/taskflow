<?php

namespace App\Http\Requests\V1\Users;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $targetUser = $this->route('user');

        return $this->user()?->can('update', $targetUser) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $targetUser = $this->route('user');
        $targetId = $targetUser instanceof User ? $targetUser->id : (int) $targetUser;

        return [
            'role_id' => ['sometimes', 'required', 'integer', 'exists:roles,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($targetId)],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'status' => ['nullable', 'string', Rule::enum(UserStatus::class)],
            'avatar_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Custom message for validation errors.
     */
    public function messages(): array
    {
        return [
            'role_id.exists' => 'O perfil selecionado é inválido.',
            'name.required' => 'O nome do usuário é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado no sistema.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ];
    }
}
