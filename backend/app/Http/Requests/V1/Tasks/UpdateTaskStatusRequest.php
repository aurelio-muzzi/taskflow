<?php

namespace App\Http\Requests\V1\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('task');

        if (! $task instanceof Task) {
            $task = Task::find($task);
        }

        if (! $task) {
            return false;
        }

        return $this->user()?->can('updateStatus', $task) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(TaskStatus::class)],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Custom message for validation errors.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'O status da tarefa é obrigatório.',
        ];
    }
}
