<?php

namespace App\Http\Requests\V1\Comments;

use App\Models\TaskComment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $comment = $this->route('comment');

        if (! $comment instanceof TaskComment) {
            $comment = TaskComment::find($comment);
        }

        if (! $comment) {
            return false;
        }

        return $this->user()?->can('update', $comment) ?? false;
    }

    /**
     * Prepara os dados para validação aceitando tanto 'body' quanto 'content'.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('body') && ! $this->has('content')) {
            $this->merge(['content' => $this->input('body')]);
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
            'content' => ['required', 'string', 'min:2', 'max:5000'],
        ];
    }

    /**
     * Custom message for validation errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'O conteúdo do comentário é obrigatório.',
            'content.min' => 'O comentário deve conter pelo menos 2 caracteres.',
            'content.max' => 'O comentário não pode exceder 5000 caracteres.',
        ];
    }
}
