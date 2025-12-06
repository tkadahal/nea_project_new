<?php

declare(strict_types=1);

namespace App\Http\Requests\ProjectActivity;

use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method array all($keys = null)
 * @method void merge(array $input)
 */
class UpdateProjectActivityRequest extends FormRequest
{
    private const NUMERIC_FIELDS = ['total_budget', 'total_expense', 'planned_budget', 'q1', 'q2', 'q3', 'q4'];
    private const QUARTER_FIELDS = ['q1', 'q2', 'q3', 'q4'];
    private const FLOAT_PRECISION = 0.01;
    private const MAX_HIERARCHY_DEPTH = 2;

    public function authorize(): bool
    {
        abort_if(Gate::denies('projectActivity_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        return true;
    }

    public function rules(): array
    {
        $sectionRules = $this->getSectionRules();

        return [
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'total_budget' => 'nullable|numeric|min:0',
            'total_planned_budget' => 'nullable|numeric|min:0',
            'capital' => $sectionRules,
            'capital.*.id' => 'nullable|exists:project_activities,id',
            'capital.*.program' => 'required|string|max:255',
            'capital.*.total_budget' => 'nullable|numeric|min:0',
            'capital.*.total_expense' => 'nullable|numeric|min:0',
            'capital.*.planned_budget' => 'nullable|numeric|min:0',
            'capital.*.q1' => 'nullable|numeric|min:0',
            'capital.*.q2' => 'nullable|numeric|min:0',
            'capital.*.q3' => 'nullable|numeric|min:0',
            'capital.*.q4' => 'nullable|numeric|min:0',
            'capital.*.parent_id' => 'nullable|integer',
            'recurrent' => $sectionRules,
            'recurrent.*.id' => 'nullable|exists:project_activities,id',
            'recurrent.*.program' => 'required|string|max:255',
            'recurrent.*.total_budget' => 'nullable|numeric|min:0',
            'recurrent.*.total_expense' => 'nullable|numeric|min:0',
            'recurrent.*.planned_budget' => 'nullable|numeric|min:0',
            'recurrent.*.q1' => 'nullable|numeric|min:0',
            'recurrent.*.q2' => 'nullable|numeric|min:0',
            'recurrent.*.q3' => 'nullable|numeric|min:0',
            'recurrent.*.q4' => 'nullable|numeric|min:0',
            'recurrent.*.parent_id' => 'nullable|integer',
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
                $this->validatePlannedBudgetEqualsQuarters($value, $section, $fail);
                $this->validateParentEqualsChildrenSum($value, $section, $fail);
            },
        ];
    }

    private function validatePlannedBudgetEqualsQuarters(array $data, string $section, Closure $fail): void
    {
        foreach ($data as $index => $row) {
            $quarterSum = $this->sumQuarters($row);
            $plannedBudget = (float) ($row['planned_budget'] ?? 0);

            if (abs($quarterSum - $plannedBudget) > self::FLOAT_PRECISION) {
                $rowNum = $index + 1;
                $fail("Planned budget must equal sum of quarters for {$section} row {$rowNum}. " .
                    "Quarters sum: {$quarterSum}, Planned budget: {$plannedBudget}");
            }
        }
    }

    private function validateParentEqualsChildrenSum(array $data, string $section, Closure $fail): void
    {
        $childrenMap = $this->buildChildrenMap($data);

        foreach ($childrenMap as $parentIndex => $childIndices) {
            if (!isset($data[$parentIndex])) {
                continue;
            }

            $this->validateParentRow($data, $parentIndex, $childIndices, $section, $fail);
        }
    }

    private function validateParentRow(array $data, int $parentIndex, array $childIndices, string $section, Closure $fail): void
    {
        $parentRow = $data[$parentIndex];
        $rowNum = $parentIndex + 1;

        foreach (self::NUMERIC_FIELDS as $field) {
            $parentValue = (float) ($parentRow[$field] ?? 0);
            $childrenSum = $this->sumChildrenField($data, $childIndices, $field);

            if (abs($parentValue - $childrenSum) > self::FLOAT_PRECISION) {
                $fail("Parent must equal sum of children for {$section} row {$rowNum}, field '{$field}'. " .
                    "Parent: {$parentValue}, Children sum: {$childrenSum}");
            }
        }
    }

    private function validateSectionHierarchy(array $data, string $section, Closure $fail): void
    {
        $depthMap = [];

        foreach ($data as $index => $row) {
            $parentId = $row['parent_id'] ?? null;

            if ($this->isNullParent($parentId)) {
                $depthMap[$index] = 0;
                continue;
            }

            $parentIndex = (int) $parentId;

            if (!$this->isValidParent($parentIndex, $index, $data)) {
                $rowNum = $index + 1;
                $fail("Invalid parent_id for {$section} row {$rowNum}: Parent activity not found or comes after child");
                continue;
            }

            $parentDepth = $depthMap[$parentIndex] ?? 0;
            $depthMap[$index] = $parentDepth + 1;

            if ($depthMap[$index] > self::MAX_HIERARCHY_DEPTH) {
                $rowNum = $index + 1;
                $fail("Maximum hierarchy depth exceeded for {$section} row {$rowNum}. Maximum allowed depth is " . self::MAX_HIERARCHY_DEPTH . ".");
            }
        }
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
        $input = $this->all();

        foreach (['capital', 'recurrent'] as $section) {
            if (array_key_exists($section, $input)) {
                $this->processSection($input[$section], $section);
            }
        }
    }

    private function processSection(array $sectionData, string $sectionName): void
    {
        $filteredData = $this->filterEmptyRows($sectionData);
        $mapping = $this->createIndexMapping($sectionData, $filteredData);
        $reindexedData = array_values($filteredData);
        $finalData = $this->remapParentIds($reindexedData, $mapping);

        $this->merge([$sectionName => $finalData]);
    }

    // Helper methods

    private function sumQuarters(array $row): float
    {
        return array_reduce(
            self::QUARTER_FIELDS,
            fn($sum, $field) => $sum + (float) ($row[$field] ?? 0),
            0
        );
    }

    private function buildChildrenMap(array $data): array
    {
        $childrenMap = [];

        foreach ($data as $index => $row) {
            if (isset($row['parent_id']) && $row['parent_id'] !== '' && $row['parent_id'] !== null) {
                $parentIndex = (int) $row['parent_id'];
                $childrenMap[$parentIndex][] = $index;
            }
        }

        return $childrenMap;
    }

    private function sumChildrenField(array $data, array $childIndices, string $field): float
    {
        return array_reduce(
            $childIndices,
            fn($sum, $childIndex) => $sum + (float) ($data[$childIndex][$field] ?? 0),
            0
        );
    }

    private function isNullParent($parentId): bool
    {
        return $parentId === null || $parentId === '' || $parentId === 'null';
    }

    private function isValidParent(int $parentIndex, int $currentIndex, array $data): bool
    {
        return $parentIndex >= 0 &&
            $parentIndex < $currentIndex &&
            array_key_exists($parentIndex, $data);
    }

    private function filterEmptyRows(array $sectionData): array
    {
        return array_filter($sectionData, fn($row) => $this->isRowNotEmpty($row));
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

    private function createIndexMapping(array $originalData, array $filteredData): array
    {
        $mapping = [];
        $newIndex = 0;

        foreach ($originalData as $oldIndex => $exp) {
            if (isset($filteredData[$oldIndex])) {
                $mapping[$oldIndex] = $newIndex++;
            }
        }

        return $mapping;
    }

    private function remapParentIds(array $data, array $mapping): array
    {
        foreach ($data as &$row) {
            if (
                !isset($row['parent_id']) ||
                $row['parent_id'] === '' ||
                $row['parent_id'] === null ||
                $row['parent_id'] === 'null' ||
                !is_numeric($row['parent_id'])
            ) {
                unset($row['parent_id']);
                continue;
            }

            $oldParentIndex = (int) $row['parent_id'];

            if (isset($mapping[$oldParentIndex])) {
                $row['parent_id'] = $mapping[$oldParentIndex];
            } else {
                unset($row['parent_id']);
            }
        }

        return $data;
    }

    private function getFieldMessages(string $section): array
    {
        return [
            "{$section}.*.id.exists" => "Activity ID does not exist for {$section} row.",
            "{$section}.*.program.required" => "Program name is required for {$section} row.",
            "{$section}.*.program.max" => "Program name may not be greater than 255 characters.",
            "{$section}.*.total_budget.numeric" => "Total budget must be a number for {$section} row.",
            "{$section}.*.total_budget.min" => "Total budget must be at least 0 for {$section} row.",
            "{$section}.*.total_expense.numeric" => "Expenses till date must be a number for {$section} row.",
            "{$section}.*.total_expense.min" => "Expenses till date must be at least 0 for {$section} row.",
            "{$section}.*.planned_budget.numeric" => "Planned budget must be a number for {$section} row.",
            "{$section}.*.planned_budget.min" => "Planned budget must be at least 0 for {$section} row.",
            "{$section}.*.q1.numeric" => "Q1 budget must be a number for {$section} row.",
            "{$section}.*.q1.min" => "Q1 budget must be at least 0 for {$section} row.",
            "{$section}.*.q2.numeric" => "Q2 budget must be a number for {$section} row.",
            "{$section}.*.q2.min" => "Q2 budget must be at least 0 for {$section} row.",
            "{$section}.*.q3.numeric" => "Q3 budget must be a number for {$section} row.",
            "{$section}.*.q3.min" => "Q3 budget must be at least 0 for {$section} row.",
            "{$section}.*.q4.numeric" => "Q4 budget must be a number for {$section} row.",
            "{$section}.*.q4.min" => "Q4 budget must be at least 0 for {$section} row.",
            "{$section}.*.parent_id.integer" => "Parent ID must be a valid integer for {$section} row.",
        ];
    }
}
