<?php

namespace App\Http\Requests\BudgetQuaterAllocation;

use App\Models\Budget;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class StoreBudgetQuaterAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('budgetQuaterAllocation_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        $rules = [
            'project_id' => ['required', 'exists:projects,id'],
            'fiscal_year_id' => ['required', 'exists:fiscal_years,id'],
            'budget_ids' => ['required', 'array', 'min:1'],
            'budget_ids.*' => ['required', 'exists:budgets,id'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => [
                'required',
                'string',
                'max:255',
                Rule::in(['total_budget', 'internal_budget', 'government_share', 'government_loan', 'foreign_loan', 'foreign_subsidy'])
            ],
            'amounts' => ['required', 'array', 'min:1'],
            'amounts.*' => ['required', 'numeric', 'min:0'],
            'q1_allocations' => ['required', 'array', 'min:1'],
            'q1_allocations.*' => ['required', 'numeric', 'min:0'],
            'q2_allocations' => ['required', 'array', 'min:1'],
            'q2_allocations.*' => ['required', 'numeric', 'min:0'],
            'q3_allocations' => ['required', 'array', 'min:1'],
            'q3_allocations.*' => ['required', 'numeric', 'min:0'],
            'q4_allocations' => ['required', 'array', 'min:1'],
            'q4_allocations.*' => ['required', 'numeric', 'min:0'],
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'budget_ids.*.exists' => 'The selected budget ID is invalid.',
            'fields.*.required' => 'Field name is required.',
            'fields.*.in' => 'Field must be one of: total_budget, internal_budget, government_share, government_loan, foreign_loan, foreign_subsidy.',
            'amounts.*.required' => 'Amount is required.',
            'amounts.*.numeric' => 'Amount must be a number.',
            'amounts.*.min' => 'Amount must be at least 0.',
            'q1_allocations.*.required' => 'Q1 allocation is required.',
            'q1_allocations.*.numeric' => 'Q1 allocation must be a number.',
            'q1_allocations.*.min' => 'Q1 allocation must be at least 0.',
            'q2_allocations.*.required' => 'Q2 allocation is required.',
            'q2_allocations.*.numeric' => 'Q2 allocation must be a number.',
            'q2_allocations.*.min' => 'Q2 allocation must be at least 0.',
            'q3_allocations.*.required' => 'Q3 allocation is required.',
            'q3_allocations.*.numeric' => 'Q3 allocation must be a number.',
            'q3_allocations.*.min' => 'Q3 allocation must be at least 0.',
            'q4_allocations.*.required' => 'Q4 allocation is required.',
            'q4_allocations.*.numeric' => 'Q4 allocation must be a number.',
            'q4_allocations.*.min' => 'Q4 allocation must be at least 0.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $uniqueBudgetIds = array_unique($this->get('budget_ids', []));
            foreach ($uniqueBudgetIds as $budgetId) {
                if (!Budget::where('id', $budgetId)
                    ->where('project_id', $this->get('project_id'))
                    ->where('fiscal_year_id', $this->get('fiscal_year_id'))
                    ->exists()) {
                    $validator->errors()->add('budget_ids.*', "Budget ID {$budgetId} does not belong to the selected project or fiscal year.");
                }
            }

            $budgetIds = $this->get('budget_ids', []);
            $count = count($budgetIds);

            $arraysToCheck = [
                'fields' => $this->get('fields', []),
                'amounts' => $this->get('amounts', []),
                'q1_allocations' => $this->get('q1_allocations', []),
                'q2_allocations' => $this->get('q2_allocations', []),
                'q3_allocations' => $this->get('q3_allocations', []),
                'q4_allocations' => $this->get('q4_allocations', []),
            ];

            foreach ($arraysToCheck as $key => $array) {
                if (count($array) !== $count) {
                    $validator->errors()->add($key, "The {$key} array must have exactly {$count} elements.");
                }
            }

            $amounts = $this->get('amounts', []);
            $q1 = $this->get('q1_allocations', []);
            $q2 = $this->get('q2_allocations', []);
            $q3 = $this->get('q3_allocations', []);
            $q4 = $this->get('q4_allocations', []);

            foreach ($budgetIds as $index => $budgetId) {
                $quarterSum = (float) ($q1[$index] ?? 0) + (float) ($q2[$index] ?? 0) + (float) ($q3[$index] ?? 0) + (float) ($q4[$index] ?? 0);
                $amount = (float) ($amounts[$index] ?? 0);

                if (abs($quarterSum - $amount) > 0.01) {
                    $validator->errors()->add(
                        'q4_allocations.*',
                        "Row " . ($index + 1) . ": Quarters sum (" . number_format($quarterSum, 2) . ") must equal amount (" . number_format($amount, 2) . ")."
                    );
                }
            }
        });
    }
}
