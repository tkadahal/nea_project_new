<?php

declare(strict_types=1);

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('budget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        return [
            'fiscal_year_id' => 'required|integer|exists:fiscal_years,id',
            'project_id.*' => 'required|integer|exists:projects,id',
            'internal_budget.*' => 'nullable|numeric|min:0',
            'government_share.*' => 'nullable|numeric|min:0',
            'government_loan.*' => 'nullable|numeric|min:0',
            'foreign_loan_budget.*' => 'nullable|numeric|min:0',
            'foreign_subsidy_budget.*' => 'nullable|numeric|min:0',
            'total_budget.*' => 'nullable|numeric|min:0',
            'decision_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:255',
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
    }

    protected function prepareForValidation()
    {
        $projectIds = $this->input('project_id', []);
        $budgetFields = [
            'internal_budget',
            'government_share',
            'government_loan',
            'foreign_loan_budget',
            'foreign_subsidy_budget',
            'total_budget',
        ];

        // Filter out projects with all zero or empty budget fields
        $filteredProjectIds = [];
        foreach ($projectIds as $projectId => $value) {
            $hasNonZeroValue = false;
            foreach ($budgetFields as $field) {
                $fieldValue = $this->input("{$field}.{$projectId}", 0);
                if ($fieldValue > 0) {
                    $hasNonZeroValue = true;
                    break;
                }
            }

            if ($hasNonZeroValue) {
                $filteredProjectIds[$projectId] = $projectId;
            }
        }

        // Ensure at least one project has valid budget data
        if (empty($filteredProjectIds)) {
            throw new \Illuminate\Validation\ValidationException(
                new Validator(
                    $this->container->make(\Illuminate\Contracts\Validation\Validator::class),
                    ['project_id' => 'At least one project must have non-zero budget values.']
                )
            );
        }

        $this->merge([
            'project_id' => $filteredProjectIds,
        ]);
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            redirect()->back()->withErrors($validator)->withInput()
        );
    }

    public function messages(): array
    {
        return [
            'fiscal_year_id.required' => 'Fiscal year is required.',
            'fiscal_year_id.exists' => 'The selected fiscal year does not exist.',
            'project_id.*.required' => 'Project ID is required.',
            'project_id.*.exists' => 'The selected project does not exist.',
            'internal_budget.*.numeric' => 'Internal budget must be a number.',
            'internal_budget.*.min' => 'Internal budget must be at least 0.',
            'government_share.*.numeric' => 'Government share must be a number.',
            'government_share.*.min' => 'Government share must be at least 0.',
            'government_loan.*.numeric' => 'Government loan must be a number.',
            'government_loan.*.min' => 'Government loan must be at least 0.',
            'foreign_loan_budget.*.numeric' => 'Foreign loan budget must be a number.',
            'foreign_loan_budget.*.min' => 'Foreign loan budget must be at least 0.',
            'foreign_subsidy_budget.*.numeric' => 'Foreign subsidy budget must be a number.',
            'foreign_subsidy_budget.*.min' => 'Foreign subsidy budget must be at least 0.',
            'total_budget.*.numeric' => 'Total budget must be a number.',
            'total_budget.*.min' => 'Total budget must be at least 0.',
            'project_id' => 'At least one project must have non-zero budget values.',
            'decision_date.date' => 'Decision date must be a valid date.',
            'remarks.string' => 'Remarks must be a string.',
            'remarks.max' => 'Remarks may not be greater than 255 characters.',
            'files.*.mimes' => 'Files must be of type: jpg, jpeg, png, pdf.',
            'files.*.max' => 'Files may not be larger than 2MB.',
        ];
    }
}
