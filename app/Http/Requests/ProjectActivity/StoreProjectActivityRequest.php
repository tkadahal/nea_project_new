<?php

declare(strict_types=1);

namespace App\Http\Requests\ProjectActivity;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method array all($keys = null)
 * @method void merge(array $input)
 */
class StoreProjectActivityRequest extends FormRequest
{
    private const AMOUNT_FIELDS = ['total_budget', 'total_expense', 'planned_budget', 'q1', 'q2', 'q3', 'q4'];
    private const QTY_FIELDS = ['total_budget_quantity', 'total_expense_quantity', 'planned_budget_quantity', 'q1_quantity', 'q2_quantity', 'q3_quantity', 'q4_quantity'];
    private const NUMERIC_FIELDS = ['total_budget', 'total_budget_quantity', 'total_expense', 'total_expense_quantity', 'planned_budget', 'planned_budget_quantity', 'q1', 'q1_quantity', 'q2', 'q2_quantity', 'q3', 'q3_quantity', 'q4', 'q4_quantity'];
    private const QUARTER_AMOUNT_FIELDS = ['q1', 'q2', 'q3', 'q4'];
    private const QUARTER_QTY_FIELDS = ['q1_quantity', 'q2_quantity', 'q3_quantity', 'q4_quantity'];
    private const FLOAT_PRECISION = 0.01;
    private const MAX_HIERARCHY_DEPTH = 2;

    public function authorize(): bool
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        return true;
    }

    public function rules(Request $request): array
    {
        // dd($request->all());
        $sectionRules = $this->getSectionRules();

        return [
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'total_budget' => 'nullable|numeric|min:0',
            'total_planned_budget' => 'nullable|numeric|min:0',
            'capital' => $sectionRules,
            'capital.*.program' => 'required|string|max:255',
            'capital.*.sort_index' => 'nullable|string|max:50',
            'capital.*.parent_id' => 'nullable|string',
            'capital.*.depth' => 'nullable|integer|min:0|max:' . self::MAX_HIERARCHY_DEPTH,
            'capital.*.total_budget' => 'nullable|numeric|min:0',
            'capital.*.total_budget_quantity' => 'nullable|numeric|min:0',
            'capital.*.total_expense' => 'nullable|numeric|min:0',
            'capital.*.total_expense_quantity' => 'nullable|numeric|min:0',
            'capital.*.planned_budget' => 'nullable|numeric|min:0',
            'capital.*.planned_budget_quantity' => 'nullable|numeric|min:0',
            'capital.*.q1' => 'nullable|numeric|min:0',
            'capital.*.q1_quantity' => 'nullable|numeric|min:0',
            'capital.*.q2' => 'nullable|numeric|min:0',
            'capital.*.q2_quantity' => 'nullable|numeric|min:0',
            'capital.*.q3' => 'nullable|numeric|min:0',
            'capital.*.q3_quantity' => 'nullable|numeric|min:0',
            'capital.*.q4' => 'nullable|numeric|min:0',
            'capital.*.q4_quantity' => 'nullable|numeric|min:0',
            'recurrent' => $sectionRules,
            'recurrent.*.program' => 'required|string|max:255',
            'recurrent.*.sort_index' => 'nullable|string|max:50',
            'recurrent.*.parent_id' => 'nullable|string',
            'recurrent.*.depth' => 'nullable|integer|min:0|max:' . self::MAX_HIERARCHY_DEPTH,
            'recurrent.*.total_budget' => 'nullable|numeric|min:0',
            'recurrent.*.total_budget_quantity' => 'nullable|numeric|min:0',
            'recurrent.*.total_expense' => 'nullable|numeric|min:0',
            'recurrent.*.total_expense_quantity' => 'nullable|numeric|min:0',
            'recurrent.*.planned_budget' => 'nullable|numeric|min:0',
            'recurrent.*.planned_budget_quantity' => 'nullable|numeric|min:0',
            'recurrent.*.q1' => 'nullable|numeric|min:0',
            'recurrent.*.q1_quantity' => 'nullable|numeric|min:0',
            'recurrent.*.q2' => 'nullable|numeric|min:0',
            'recurrent.*.q2_quantity' => 'nullable|numeric|min:0',
            'recurrent.*.q3' => 'nullable|numeric|min:0',
            'recurrent.*.q3_quantity' => 'nullable|numeric|min:0',
            'recurrent.*.q4' => 'nullable|numeric|min:0',
            'recurrent.*.q4_quantity' => 'nullable|numeric|min:0',
        ];
    }

    private function getSectionRules(): array
    {
        return [
            'sometimes',
            'array',
            function (string $attribute, array $value, Closure $fail) {
                $section = explode('.', $attribute)[0];

                $this->validateSectionHierarchy($value, $section, $fail);
                $this->validatePlannedEqualsQuarters($value, $section, $fail);
                $this->validateParentEqualsChildrenSum($value, $section, $fail);
            },
        ];
    }

    private function validatePlannedEqualsQuarters(array $data, string $section, Closure $fail): void
    {
        foreach ($data as $key => $row) {
            $strKey = (string) $key;

            // Validate amounts
            $quarterAmountSum = $this->sumQuarters($row, self::QUARTER_AMOUNT_FIELDS);
            $plannedBudget = (float) ($row['planned_budget'] ?? 0);

            if (abs($quarterAmountSum - $plannedBudget) > self::FLOAT_PRECISION) {
                $fail("Planned budget must equal sum of quarters for {$section} row {$strKey}. " .
                    "Quarters sum: {$quarterAmountSum}, Planned budget: {$plannedBudget}");
            }

            // Validate quantities
            $quarterQtySum = $this->sumQuarters($row, self::QUARTER_QTY_FIELDS);
            $plannedBudgetQty = (float) ($row['planned_budget_quantity'] ?? 0);

            if (abs($quarterQtySum - $plannedBudgetQty) > self::FLOAT_PRECISION) {
                $fail("Planned budget quantity must equal sum of quarter quantities for {$section} row {$strKey}. " .
                    "Quarter quantities sum: {$quarterQtySum}, Planned budget quantity: {$plannedBudgetQty}");
            }
        }
    }

    private function validateParentEqualsChildrenSum(array $data, string $section, Closure $fail): void
    {
        $childrenMap = $this->buildChildrenMap($data);

        foreach ($childrenMap as $parentKey => $childKeys) {
            if (!isset($data[$parentKey])) {
                continue;
            }

            $this->validateParentRow($data, $parentKey, $childKeys, $section, $fail);
        }
    }

    private function validateParentRow(array $data, int|string $parentKey, array $childKeys, string $section, Closure $fail): void
    {
        $parentRow = $data[$parentKey];

        foreach (self::AMOUNT_FIELDS as $field) {
            $parentValue = (float) ($parentRow[$field] ?? 0);
            $childrenSum = $this->sumChildrenField($data, $childKeys, $field);

            if (abs($parentValue - $childrenSum) > self::FLOAT_PRECISION) {
                $fail("Parent must equal sum of children for {$section} row " . $parentKey . ", field '{$field}'. " .
                    "Parent: {$parentValue}, Children sum: {$childrenSum}");
            }
        }
    }

    private function validateSectionHierarchy(array $data, string $section, Closure $fail): void
    {
        $depthMap = [];
        $visited = [];  // For cycle detection

        foreach ($data as $key => $row) {
            $strKey = (string) $key;
            $parentId = $row['parent_id'] ?? null;
            $strParentId = $this->isNullParent($parentId) ? null : (string) $parentId;

            if ($this->isNullParent($parentId)) {
                $depthMap[$strKey] = 0;
                $visited[$strKey] = true;
                continue;
            }

            // Ensure parent_id is a valid key in data
            if (!array_key_exists($strParentId, $data)) {
                $fail("Invalid parent_id for {$section} row {$strKey}: Parent activity not found");
                continue;
            }

            // Cycle detection: simple path check (for small depth)
            if (isset($visited[$strParentId]) && $this->hasCycle($strParentId, $strKey, $data)) {
                $fail("Cycle detected in hierarchy for {$section} row {$strKey}");
                continue;
            }

            $parentDepth = $depthMap[$strParentId] ?? -1;
            if ($parentDepth === -1) {
                $fail("Parent depth not resolved for {$section} row {$strKey} (possible cycle or order issue)");
                continue;
            }

            $depth = $parentDepth + 1;
            $depthMap[$strKey] = $depth;

            if ($depth > self::MAX_HIERARCHY_DEPTH) {
                $fail("Maximum hierarchy depth exceeded for {$section} row {$strKey}. Maximum allowed depth is " . self::MAX_HIERARCHY_DEPTH . ".");
            }

            $visited[$strKey] = true;
        }
    }

    /**
     * Simple cycle check: follow parents, see if we loop back
     */
    private function hasCycle(string $startKey, string $currentKey, array $data): bool
    {
        $path = [$startKey];
        $checkKey = $data[$startKey]['parent_id'] ?? null;

        while ($checkKey !== null && $this->isNotNullParent($checkKey)) {
            if ($checkKey === $currentKey || in_array($checkKey, $path)) {
                return true;  // Cycle
            }
            $path[] = $checkKey;
            if (!array_key_exists($checkKey, $data)) {
                return false;  // Invalid, but no cycle
            }
            $checkKey = $data[$checkKey]['parent_id'] ?? null;
        }

        return false;
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Project is required.',
            'project_id.exists' => 'Selected project does not exist.',
            'fiscal_year_id.required' => 'Fiscal year is required.',
            'fiscal_year_id.exists' => 'Selected fiscal year does not exist.',
            ...array_merge(
                $this->getFieldMessages('capital'),
                $this->getFieldMessages('recurrent')
            ),
        ];
    }

    protected function prepareForValidation(): void
    {
        // UPDATED: No reindexing; preserve original keys (temp/real IDs) for service resolution
        // Just filter empty rows in-place if needed, but validation handles assoc arrays
        foreach (['capital', 'recurrent'] as $section) {
            if (array_key_exists($section, $this->all())) {
                $filtered = $this->filterEmptyRows($this->all()[$section]);
                $this->merge([$section => $filtered]);  // Keep keys, just remove empties
            }
        }
    }

    // Helper methods (updated for assoc keys and full field names)

    private function sumQuarters(array $row, array $quarterFields): float
    {
        return array_reduce(
            $quarterFields,
            fn($sum, $field) => $sum + (float) ($row[$field] ?? 0),
            0
        );
    }

    private function buildChildrenMap(array $data): array
    {
        $childrenMap = [];

        foreach ($data as $key => $row) {
            $strKey = (string) $key;
            $parentId = $row['parent_id'] ?? null;
            $strParentId = $this->isNotNullParent($parentId) ? (string) $parentId : null;

            if ($strParentId && array_key_exists($strParentId, $data)) {
                $childrenMap[$strParentId][] = $strKey;
            }
        }

        return $childrenMap;
    }

    private function sumChildrenField(array $data, array $childKeys, string $field): float
    {
        return array_reduce(
            $childKeys,
            fn($sum, $childKey) => $sum + (float) ($data[$childKey][$field] ?? 0),
            0
        );
    }

    private function isNullParent($parentId): bool
    {
        return $parentId === null || $parentId === '' || $parentId === 'null';
    }

    private function isNotNullParent($parentId): bool
    {
        return !$this->isNullParent($parentId);
    }

    /**
     * UPDATED: No order check (assoc keys); just existence and no cycle (handled separately)
     */
    private function isValidParent(string $parentId, string $currentKey, array $data): bool
    {
        return array_key_exists($parentId, $data) && $parentId !== $currentKey;
    }

    private function filterEmptyRows(array $sectionData): array
    {
        return array_filter($sectionData, fn($row) => $this->isRowNotEmpty($row), ARRAY_FILTER_USE_BOTH);
    }

    private function isRowNotEmpty(array $row): bool
    {
        if (!empty(trim($row['program'] ?? ''))) {
            return true;
        }

        foreach (self::NUMERIC_FIELDS as $field) {
            if (($row[$field] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    private function getFieldMessages(string $section): array
    {
        return [
            "{$section}.*.program.required" => "Program name is required for {$section} row.",
            "{$section}.*.program.max" => "Program name may not be greater than 255 characters.",
            "{$section}.*.sort_index.string" => "Sort index must be a string for {$section} row.",
            "{$section}.*.sort_index.max" => "Sort index may not be greater than 50 characters for {$section} row.",
            "{$section}.*.parent_id.string" => "Parent ID must be a valid string for {$section} row.",
            "{$section}.*.depth.integer" => "Depth must be an integer for {$section} row.",
            "{$section}.*.depth.min" => "Depth must be at least 0 for {$section} row.",
            "{$section}.*.depth.max" => "Depth may not exceed " . self::MAX_HIERARCHY_DEPTH . " for {$section} row.",
            "{$section}.*.total_budget.numeric" => "Total budget must be a number for {$section} row.",
            "{$section}.*.total_budget.min" => "Total budget must be at least 0 for {$section} row.",
            "{$section}.*.total_budget_quantity.numeric" => "Total budget quantity must be a number for {$section} row.",
            "{$section}.*.total_budget_quantity.min" => "Total budget quantity must be at least 0 for {$section} row.",
            "{$section}.*.total_expense.numeric" => "Expenses till date must be a number for {$section} row.",
            "{$section}.*.total_expense.min" => "Expenses till date must be at least 0 for {$section} row.",
            "{$section}.*.total_expense_quantity.numeric" => "Total expense quantity must be a number for {$section} row.",
            "{$section}.*.total_expense_quantity.min" => "Total expense quantity must be at least 0 for {$section} row.",
            "{$section}.*.planned_budget.numeric" => "Planned budget must be a number for {$section} row.",
            "{$section}.*.planned_budget.min" => "Planned budget must be at least 0 for {$section} row.",
            "{$section}.*.planned_budget_quantity.numeric" => "Planned budget quantity must be a number for {$section} row.",
            "{$section}.*.planned_budget_quantity.min" => "Planned budget quantity must be at least 0 for {$section} row.",
            "{$section}.*.q1.numeric" => "Q1 budget must be a number for {$section} row.",
            "{$section}.*.q1.min" => "Q1 budget must be at least 0 for {$section} row.",
            "{$section}.*.q1_quantity.numeric" => "Q1 quantity must be a number for {$section} row.",
            "{$section}.*.q1_quantity.min" => "Q1 quantity must be at least 0 for {$section} row.",
            "{$section}.*.q2.numeric" => "Q2 budget must be a number for {$section} row.",
            "{$section}.*.q2.min" => "Q2 budget must be at least 0 for {$section} row.",
            "{$section}.*.q2_quantity.numeric" => "Q2 quantity must be a number for {$section} row.",
            "{$section}.*.q2_quantity.min" => "Q2 quantity must be at least 0 for {$section} row.",
            "{$section}.*.q3.numeric" => "Q3 budget must be a number for {$section} row.",
            "{$section}.*.q3.min" => "Q3 budget must be at least 0 for {$section} row.",
            "{$section}.*.q3_quantity.numeric" => "Q3 quantity must be a number for {$section} row.",
            "{$section}.*.q3_quantity.min" => "Q3 quantity must be at least 0 for {$section} row.",
            "{$section}.*.q4.numeric" => "Q4 budget must be a number for {$section} row.",
            "{$section}.*.q4.min" => "Q4 budget must be at least 0 for {$section} row.",
            "{$section}.*.q4_quantity.numeric" => "Q4 quantity must be a number for {$section} row.",
            "{$section}.*.q4_quantity.min" => "Q4 quantity must be at least 0 for {$section} row.",
        ];
    }
}
