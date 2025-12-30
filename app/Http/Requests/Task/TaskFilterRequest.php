<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class TaskFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('task_access');
    }

    public function rules(): array
    {
        return [
            'directorate_id' => 'nullable|exists:directorates,id',
            'department_id'  => 'nullable|exists:departments,id',
            'priority_id'    => 'nullable|exists:priorities,id',
            'project_id'     => 'nullable|string', // 'none' or integer
            'date_start'     => 'nullable|date|required_with:date_end',
            'date_end'       => 'nullable|date|required_with:date_start|after_or_equal:date_start',
        ];
    }
}
