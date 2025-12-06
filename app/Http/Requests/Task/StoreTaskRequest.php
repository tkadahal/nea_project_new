<?php

declare(strict_types=1);

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('task_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        return [
            'directorate_id' => [
                'required',
                'integer',
                'exists:directorates,id',
            ],
            'department_id' => [
                'nullable',
                'integer',
                'exists:departments,id',
            ],
            'parent_id' => [
                'nullable',
                'exists:tasks,id'
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'completion_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'status_id' => [
                'required',
                'exists:statuses,id',
            ],
            'priority_id' => [
                'required',
                'exists:priorities,id',
            ],
            'projects.*' => [
                'integer',
                'exists:projects,id',
            ],
            'projects' => [
                'nullable',
                'array',
            ],
            'users.*' => [
                'integer',
                'exists:users,id',
            ],
            'users' => [
                'required',
                'array',
            ],
            'subtasks' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $fail('The subtasks field must be a valid JSON string.');
                        return;
                    }
                    if (!is_array($decoded)) {
                        $fail('The subtasks field must be a JSON array.');
                        return;
                    }
                    foreach ($decoded as $index => $subtask) {
                        if (!is_array($subtask) || !isset($subtask['title']) || !is_string($subtask['title']) || empty(trim($subtask['title']))) {
                            $fail("The subtasks.{$index}.title field is required and must be a non-empty string.");
                        }
                        if (!isset($subtask['completed']) || !is_bool($subtask['completed'])) {
                            $fail("The subtasks.{$index}.completed field must be a boolean.");
                        }
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subtasks.string' => 'The subtasks field must be a valid JSON string.',
        ];
    }
}
