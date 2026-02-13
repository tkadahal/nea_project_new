<?php

declare(strict_types=1);

namespace App\Http\Requests\ProjectActivity;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProjectActivityDefinition;
use Symfony\Component\HttpFoundation\Response;

class StoreProjectActivityRequest extends FormRequest
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

                $filtered = array_filter($data, function ($row) {
                    return !empty(trim($row['program'] ?? '')) ||
                        collect(self::NUMERIC_FIELDS)->some(fn($f) => ($row[$f] ?? 0) > 0);
                });

                $this->merge([$section => $filtered]);
            }
        }
    }

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

            $rules["{$section}.*.program"] = 'required|string|max:1000';
            $rules["{$section}.*.depth"] = 'nullable|integer|min:0|max:' . self::MAX_HIERARCHY_DEPTH;
            $rules["{$section}.*.sort_index"] = 'required|string|regex:/^\d+(\.\d+)*$/';

            // Validate parent_id: nullable, integer, exists in same project and expenditure type
            $rules["{$section}.*.parent_id"] = [
                'nullable',
                'integer',
                function (string $attribute, $value, Closure $fail) use ($projectId, $expenditureId) {
                    if ($value === null) return;

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

    private function getSectionRules(): array
    {
        return [
            'sometimes',
            'array',
            function (string $attribute, array $value, Closure $fail) {
                $section = $attribute;

                $this->validatePlannedEqualsQuarters($value, $section, $fail);
                $this->validateParentEqualsChildrenSum($value, $section, $fail);
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

    public function messages(): array
    {
        return [
            'project_id.required' => 'Please select a project.',
            'fiscal_year_id.required' => 'Please select a fiscal year.',
            'capital.*.program.required' => 'Program name is required in Capital section.',
            'recurrent.*.program.required' => 'Program name is required in Recurrent section.',
            'capital.*.parent_id.*' => 'Invalid parent selected in Capital section.',
            'recurrent.*.parent_id.*' => 'Invalid parent selected in Recurrent section.',
        ];
    }
}
