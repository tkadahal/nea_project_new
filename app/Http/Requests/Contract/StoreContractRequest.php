<?php

declare(strict_types=1);

namespace App\Http\Requests\Contract;

use App\Models\Project;
use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('contract_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
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
            'project_id' => [
                'required',
                'integer',
                'exists:projects,id',
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
            'status_id' => [
                'required',
                'exists:statuses,id',
            ],
            'priority_id' => [
                'required',
                'exists:priorities,id',
            ],
            'progress' => [
                'required',
                'integer',
                'between:0,100',
            ],
            'contractor' => [
                'nullable',
                'string',
                'max:255',
            ],
            'contract_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'contract_variation_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'contract_agreement_date' => [
                'nullable',
                'date',
            ],
            'agreement_effective_date' => [
                'nullable',
                'date',
            ],
            'agreement_completion_date' => [
                'nullable',
                'date',
                'after_or_equal:agreement_effective_date',
            ],
            'initial_contract_period' => [
                'nullable',
                'string',
            ],
        ];
    }
}
