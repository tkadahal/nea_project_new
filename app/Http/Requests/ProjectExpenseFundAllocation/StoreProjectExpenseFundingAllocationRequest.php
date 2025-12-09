<?php

declare(strict_types=1);

namespace App\Http\Requests\ProjectExpenseFundAllocation;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class StoreProjectExpenseFundingAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // abort_if(Gate::denies('projectExpenseFundingAllocation_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'quarter' => 'required|integer|between:1,4',
            'total_amount' => 'required|numeric|min:0',
            'internal_allocations' => 'required|numeric|min:0',
            'gov_share_allocations' => 'required|numeric|min:0',
            'gov_loan_allocations' => 'required|numeric|min:0',
            'foreign_loan_allocations' => 'required|numeric|min:0',
            'foreign_subsidy_allocations' => 'required|numeric|min:0',
            'activity_details' => 'required|json',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Strip commas from numeric fields to handle formatted inputs (e.g., "1,234.56" -> 1234.56)
        $this->merge([
            'total_amount' => preg_replace('/[^0-9.-]/', '', $this->input('total_amount')),
            'internal_allocations' => preg_replace('/[^0-9.-]/', '', $this->input('internal_allocations')),
            'gov_share_allocations' => preg_replace('/[^0-9.-]/', '', $this->input('gov_share_allocations')),
            'gov_loan_allocations' => preg_replace('/[^0-9.-]/', '', $this->input('gov_loan_allocations')),
            'foreign_loan_allocations' => preg_replace('/[^0-9.-]/', '', $this->input('foreign_loan_allocations')),
            'foreign_subsidy_allocations' => preg_replace('/[^0-9.-]/', '', $this->input('foreign_subsidy_allocations')),
        ]);
    }

    public function messages(): array
    {
        return [
            'total_amount.numeric' => 'The total amount must be a valid number.',
            'internal_allocations.numeric' => 'The internal allocation must be a valid number.',
            'gov_share_allocations.numeric' => 'The government share allocation must be a valid number.',
            'gov_loan_allocations.numeric' => 'The government loan allocation must be a valid number.',
            'foreign_loan_allocations.numeric' => 'The foreign loan allocation must be a valid number.',
            'foreign_subsidy_allocations.numeric' => 'The foreign subsidy allocation must be a valid number.',
            // Add more custom messages as needed
        ];
    }
}
