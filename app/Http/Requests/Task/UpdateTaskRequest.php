<?php

declare(strict_types=1);

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('task_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

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
            ],
            'projects' => [
                'nullable',
                'array',
            ],
            'users.*' => [
                'integer',
            ],
            'users' => [
                'required',
                'array',
            ],
        ];
    }
}
