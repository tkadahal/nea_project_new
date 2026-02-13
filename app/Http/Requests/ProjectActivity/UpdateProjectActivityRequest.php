<?php

declare(strict_types=1);

namespace App\Http\Requests\ProjectActivity;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\ProjectActivityDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class UpdateProjectActivityRequest extends FormRequest
{
    private const AMOUNT_FIELDS = ['total_budget', 'total_expense', 'planned_budget', 'q1', 'q2', 'q3', 'q4'];
    private const QTY_FIELDS = ['total_budget_quantity', 'total_expense_quantity', 'planned_budget_quantity', 'q1_quantity', 'q2_quantity', 'q3_quantity', 'q4_quantity'];

    private const NUMERIC_FIELDS = [
        'total_budget',
        'total_budget_quantity',
        'total_expense',
        'total_expense_quantity',
        'planned_budget',
        'planned_budget_quantity',
        'q1',
        'q1_quantity',
        'q2',
        'q2_quantity',
        'q3',
        'q3_quantity',
        'q4',
        'q4_quantity'
    ];

    private const FLOAT_PRECISION = 0.01;
    private const MAX_HIERARCHY_DEPTH = 2;

    public function authorize(): bool
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        return true;
    }

    /**
     * Sanitize numeric fields by removing commas before validation
     */
    protected function prepareForValidation(): void
    {
        foreach (['capital', 'recurrent'] as $section) {
            if ($this->has($section)) {
                $data = $this->input($section);

                foreach ($data as $id => $row) {
                    foreach (self::NUMERIC_FIELDS as $field) {
                        if (isset($row[$field])) {
                            $data[$id][$field] = str_replace(',', '', (string)$row[$field]);
                        }
                    }
                }

                // Remove completely empty rows
                $filtered = array_filter($data, function ($row) {
                    return !empty(trim($row['program'] ?? '')) ||
                        collect(self::NUMERIC_FIELDS)->some(fn($f) => ($row[$f] ?? 0) > 0);
                });

                $this->merge([$section => $filtered]);
            }
        }
    }

    /**
     * Validation rules
     */
    public function rules(Request $request): array
    {
        $sectionRules = $this->getSectionRules();

        $rules = [
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'initial_capital_ids' => 'nullable|string',
            'initial_recurrent_ids' => 'nullable|string',
            'capital' => $sectionRules,
            'recurrent' => $sectionRules,
        ];

        $projectId = $request->input('project_id');

        foreach (['capital', 'recurrent'] as $section) {
            $expenditureId = $section === 'capital' ? 1 : 2;

            $rules["{$section}.*.program"] = 'required|string|max:255';
            $rules["{$section}.*.depth"] = 'nullable|integer|min:0|max:' . self::MAX_HIERARCHY_DEPTH;
            $rules["{$section}.*.sort_index"] = 'required|string|regex:/^\d+(\.\d+)*$/';

            // Validate parent_id: must be null or a valid activity in the same project and section
            $rules["{$section}.*.parent_id"] = [
                'nullable',
                'integer',
                function (string $attribute, $value, Closure $fail) use ($projectId, $expenditureId) {
                    if ($value === null) {
                        return;
                    }

                    $exists = ProjectActivityDefinition::where('id', $value)
                        ->where('project_id', $projectId)
                        ->where('expenditure_id', $expenditureId)
                        ->where('is_current', true)
                        ->exists();

                    if (!$exists) {
                        $fail("The selected parent activity is invalid or does not belong to this project/section.");
                    }
                },
            ];

            foreach (self::NUMERIC_FIELDS as $field) {
                $rules["{$section}.*.{$field}"] = 'nullable|numeric|min:0';
            }
        }

        return $rules;
    }

    /**
     * Custom validation for quarters and parent-child sums
     */
    private function getSectionRules(): array
    {
        return [
            'sometimes',
            'array',
            function (string $attribute, array $value, Closure $fail) {
                $section = $attribute;

                $this->validatePlannedEqualsQuarters($value, $section, $fail);
                //$this->validateParentEqualsChildrenSum($value, $section, $fail);
            },
        ];
    }

    private function validatePlannedEqualsQuarters(array $data, string $section, Closure $fail): void
    {
        foreach ($data as $id => $row) {
            $quarterSum = (float)($row['q1'] ?? 0) + (float)($row['q2'] ?? 0) + (float)($row['q3'] ?? 0) + (float)($row['q4'] ?? 0);
            $planned = (float)($row['planned_budget'] ?? 0);

            if (abs($quarterSum - $planned) > self::FLOAT_PRECISION) {
                $fail("The sum of quarters for row '{$row['program']}' must equal its Planned Budget.");
            }
        }
    }

    private function validateParentEqualsChildrenSum(array $data, string $section, Closure $fail): void
    {
        $childrenMap = [];
        foreach ($data as $id => $row) {
            $parentId = $row['parent_id'] ?? null;
            if ($parentId !== null && isset($data[$parentId])) {
                $childrenMap[$parentId][] = $id;
            }
        }

        foreach ($childrenMap as $parentId => $childIds) {
            foreach (self::AMOUNT_FIELDS as $field) {
                $parentVal = (float)($data[$parentId][$field] ?? 0);
                $childSum = collect($childIds)->sum(fn($cid) => (float)($data[$cid][$field] ?? 0));

                if (abs($parentVal - $childSum) > self::FLOAT_PRECISION) {
                    $fail("Parent '{$data[$parentId]['program']}' {$field} must equal the sum of its sub-activities.");
                }
            }
        }
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'project_id.required' => 'Please select a project.',
            'fiscal_year_id.required' => 'Please select a fiscal year.',
            'capital.*.program.required' => 'Program name is required in Capital section.',
            'recurrent.*.program.required' => 'Program name is required in Recurrent section.',
            'capital.*.sort_index.regex' => 'Invalid sort order format in Capital section (e.g., 1, 1.1, 1.2).',
            'recurrent.*.sort_index.regex' => 'Invalid sort order format in Recurrent section (e.g., 1, 1.1, 1.2).',
            'capital.*.parent_id.*' => 'Invalid parent selected in Capital section.',
            'recurrent.*.parent_id.*' => 'Invalid parent selected in Recurrent section.',
            'capital.*.total_budget.numeric' => 'The Total Budget in Capital row must be a number.',
            'recurrent.*.total_budget.numeric' => 'The Total Budget in Recurrent row must be a number.',
        ];
    }

    /**
     * Log detailed information when validation fails
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        \Illuminate\Support\Facades\Log::error('UpdateProjectActivityRequest validation failed', [
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_id' => Auth::id(),
            'project_id' => $this->input('project_id'),
            'fiscal_year_id' => $this->input('fiscal_year_id'),
            'validation_errors' => $validator->errors()->all(),
            'validation_errors_bag' => $validator->errors()->toArray(),
            'input_data_preview' => [
                'capital_count' => is_array($this->input('capital')) ? count($this->input('capital')) : 'not array',
                'recurrent_count' => is_array($this->input('recurrent')) ? count($this->input('recurrent')) : 'not array',
                'capital_keys' => is_array($this->input('capital')) ? array_slice(array_keys($this->input('capital')), 0, 10) : null,
                'sample_capital_row' => is_array($this->input('capital')) ? array_values($this->input('capital'))[0] ?? null : null,
            ],
            'full_input_keys' => array_keys($this->all()),
        ]);

        parent::failedValidation($validator);
    }
}
