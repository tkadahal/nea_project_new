<?php

namespace App\Http\Requests\ProjectExpense;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'selected_quarter' => 'required|in:q1,q2,q3,q4', // ✅ ADD THIS

            // Capital expenses
            'capital' => 'nullable|array',
            'capital.*' => 'array',
            'capital.*.activity_id' => 'required|exists:project_activity_plans,id',
            'capital.*.parent_id' => 'nullable|exists:project_activity_plans,id',
            'capital.*.description' => 'nullable|string|max:500',

            // Dynamic quarter validation for capital
            'capital.*.q1_qty' => 'nullable|numeric|min:0',
            'capital.*.q1_amt' => 'nullable|numeric|min:0',
            'capital.*.q2_qty' => 'nullable|numeric|min:0',
            'capital.*.q2_amt' => 'nullable|numeric|min:0',
            'capital.*.q3_qty' => 'nullable|numeric|min:0',
            'capital.*.q3_amt' => 'nullable|numeric|min:0',
            'capital.*.q4_qty' => 'nullable|numeric|min:0',
            'capital.*.q4_amt' => 'nullable|numeric|min:0',

            // Recurrent expenses
            'recurrent' => 'nullable|array',
            'recurrent.*' => 'array',
            'recurrent.*.activity_id' => 'required|exists:project_activity_plans,id',
            'recurrent.*.parent_id' => 'nullable|exists:project_activity_plans,id',
            'recurrent.*.description' => 'nullable|string|max:500',

            // Dynamic quarter validation for recurrent
            'recurrent.*.q1_qty' => 'nullable|numeric|min:0',
            'recurrent.*.q1_amt' => 'nullable|numeric|min:0',
            'recurrent.*.q2_qty' => 'nullable|numeric|min:0',
            'recurrent.*.q2_amt' => 'nullable|numeric|min:0',
            'recurrent.*.q3_qty' => 'nullable|numeric|min:0',
            'recurrent.*.q3_amt' => 'nullable|numeric|min:0',
            'recurrent.*.q4_qty' => 'nullable|numeric|min:0',
            'recurrent.*.q4_amt' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'project_id.required' => 'Please select a project.',
            'project_id.exists' => 'The selected project is invalid.',
            'fiscal_year_id.required' => 'Please select a fiscal year.',
            'fiscal_year_id.exists' => 'The selected fiscal year is invalid.',
            'selected_quarter.required' => 'Please select a quarter.',
            'selected_quarter.in' => 'The selected quarter must be Q1, Q2, Q3, or Q4.',

            'capital.*.activity_id.required' => 'Activity ID is required for capital expenses.',
            'capital.*.activity_id.exists' => 'The selected capital activity is invalid.',

            'recurrent.*.activity_id.required' => 'Activity ID is required for recurrent expenses.',
            'recurrent.*.activity_id.exists' => 'The selected recurrent activity is invalid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'project_id' => 'project',
            'fiscal_year_id' => 'fiscal year',
            'selected_quarter' => 'quarter',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up numeric inputs - remove commas and format
        $this->cleanNumericInputs('capital');
        $this->cleanNumericInputs('recurrent');
    }

    /**
     * Clean numeric inputs by removing commas and formatting
     */
    private function cleanNumericInputs(string $section): void
    {
        if ($this->has($section) && is_array($this->input($section))) {
            $cleaned = [];

            foreach ($this->input($section) as $key => $activityData) {
                $cleaned[$key] = $activityData;

                // Clean all quarter qty and amt fields
                foreach (['q1', 'q2', 'q3', 'q4'] as $quarter) {
                    if (isset($activityData["{$quarter}_qty"])) {
                        $cleaned[$key]["{$quarter}_qty"] = $this->cleanNumber($activityData["{$quarter}_qty"]);
                    }
                    if (isset($activityData["{$quarter}_amt"])) {
                        $cleaned[$key]["{$quarter}_amt"] = $this->cleanNumber($activityData["{$quarter}_amt"]);
                    }
                }
            }

            $this->merge([$section => $cleaned]);
        }
    }

    /**
     * Clean a number by removing commas and converting empty strings to null
     */
    private function cleanNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Remove commas and convert to float
        return (float) str_replace(',', '', $value);
    }
}
