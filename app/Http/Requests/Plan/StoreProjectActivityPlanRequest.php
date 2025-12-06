<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class StoreProjectActivityPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('activityPlan_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        return [
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'program_override' => 'nullable|string|max:255',
            'override_modified_at' => 'nullable|date',
            'planned_budget' => 'required|numeric|min:0',
            'planned_quantity' => 'required|numeric|min:0',
            'q1_amount' => 'required|numeric|min:0',
            'q2_amount' => 'required|numeric|min:0',
            'q3_amount' => 'required|numeric|min:0',
            'q4_amount' => 'required|numeric|min:0',
            'q1_quantity' => 'required|numeric|min:0',
            'q2_quantity' => 'required|numeric|min:0',
            'q3_quantity' => 'required|numeric|min:0',
            'q4_quantity' => 'required|numeric|min:0',
        ];
    }

    // Custom validation: Ensure quarterly sums match planned
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $budgetSum = $this->q1_amount + $this->q2_amount + $this->q3_amount + $this->q4_amount;
            $qtySum = $this->q1_quantity + $this->q2_quantity + $this->q3_quantity + $this->q4_quantity;
            if (abs($budgetSum - $this->planned_budget) > 0.01) { // Tolerance for floats
                $validator->errors()->add('planned_budget', 'Must equal sum of quarterly budgets.');
            }
            if (abs($qtySum - $this->planned_quantity) > 0.01) {
                $validator->errors()->add('planned_quantity', 'Must equal sum of quarterly quantities.');
            }
        });
    }
}
