<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class StoreProjectActivityDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('activityDefinition_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'program' => 'required|string|max:255|unique:project_activity_definitions,program,NULL,id,project_id,' . $this->project_id,
            'expenditure_id' => 'required|integer|in:1,2',
            'description' => 'nullable|string',
            'total_budget' => 'required|numeric|min:0',
            'total_quantity' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,archived',
            'parent_id' => 'nullable|exists:project_activity_definitions,id',
            'version' => 'integer|min:1', // Defaults to 1
        ];
    }
}
