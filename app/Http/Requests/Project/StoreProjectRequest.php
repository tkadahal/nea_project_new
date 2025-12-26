<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('project_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        return [
            'directorate_id' => ['required', 'exists:directorates,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'budget_heading_id' => ['nullable', 'exists:budget_headings,id'],
            'lmbis_id' => [
                'nullable',
                'integer',
                // Unique lmbis_id per budget_heading_id
                Rule::unique('projects', 'lmbis_id')
                    ->where('budget_heading_id', $this->budget_heading_id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status_id' => ['required', 'exists:statuses,id'],
            'priority_id' => ['required', 'exists:priorities,id'],
            'project_manager' => ['nullable', 'exists:users,id'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }
}
