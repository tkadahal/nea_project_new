<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\ProjectActivityPlan;
use App\Models\ProjectActivityDefinition;
use Illuminate\Validation\ValidationException;

class ProjectActivityProcessor
{
    public function processSection($request, string $section, int $projectId, int $fiscalYearId, int $expenditureId): void
    {
        $rows = is_array($request) ? ($request[$section] ?? []) : $request->input($section, []);

        if (empty($rows)) {
            return;
        }

        $flatRows = $this->flattenRows($rows);
        $this->resolveParentRelationships($flatRows);

        $processed = [];
        $parentMap = $this->buildParentMap($flatRows);

        foreach ($flatRows as $internalId => $rowData) {
            $this->createInOrder($flatRows, $processed, $internalId, $parentMap);
        }

        $definitionIdMap = $this->createDefinitions($flatRows, $processed, $projectId, $expenditureId);
        $this->createPlans($flatRows, $processed, $definitionIdMap, $fiscalYearId, $projectId, $section, $parentMap);
    }

    private function flattenRows(array $rows): array
    {
        $flatRows = [];
        $indexMap = [];
        $rowCounter = 0;

        foreach ($rows as $originalIndex => $rowData) {
            $flatRows[$rowCounter] = $rowData;
            $flatRows[$rowCounter]['original_index'] = $originalIndex;
            $flatRows[$rowCounter]['internal_id'] = $rowCounter;
            $indexMap[$originalIndex] = $rowCounter;
            $rowCounter++;
        }

        return ['rows' => $flatRows, 'indexMap' => $indexMap];
    }

    private function resolveParentRelationships(array &$data): void
    {
        $indexMap = $data['indexMap'];
        $flatRows = &$data['rows'];

        foreach ($flatRows as $internalId => &$rowData) {
            $submittedParentId = $rowData['parent_id'] ?? null;

            if ($submittedParentId !== null) {
                $parentInternalId = $indexMap[$submittedParentId] ?? null;

                if ($parentInternalId === null) {
                    throw new \Exception("Invalid parent_id {$submittedParentId} for row {$rowData['original_index']}.");
                }

                $rowData['resolved_parent_internal_id'] = $parentInternalId;
            }
        }
    }

    private function buildParentMap(array $data): array
    {
        $parentMap = [];
        $flatRows = $data['rows'];

        foreach ($flatRows as $internalId => $rowData) {
            if (isset($rowData['resolved_parent_internal_id'])) {
                $parentMap[$internalId] = $rowData['resolved_parent_internal_id'];
            }
        }

        return $parentMap;
    }

    private function createInOrder(array &$flatRows, array &$processed, int $internalId, array $parentMap): void
    {
        if (in_array($internalId, $processed)) {
            return;
        }

        $parentInternalId = $parentMap[$internalId] ?? null;
        if ($parentInternalId !== null) {
            $this->createInOrder($flatRows, $processed, $parentInternalId, $parentMap);
        }

        $processed[] = $internalId;
    }

    private function createDefinitions(array $data, array $processed, int $projectId, int $expenditureId): array
    {
        $definitionIdMap = [];
        $flatRows = $data['rows'];

        foreach ($processed as $internalId) {
            $rowData = $flatRows[$internalId];
            $parentDefId = null;

            if (isset($rowData['resolved_parent_internal_id'])) {
                $parentDefId = $definitionIdMap[$rowData['resolved_parent_internal_id']] ?? null;

                if ($parentDefId === null) {
                    throw new \Exception("Parent definition not created for row {$rowData['original_index']}.");
                }
            }

            $program = trim($rowData['program']);
            $definition = $this->findOrCreateDefinition($projectId, $program, $expenditureId, $parentDefId);

            $definitionIdMap[$internalId] = $definition->id;
        }

        return $definitionIdMap;
    }

    private function findOrCreateDefinition(int $projectId, string $program, int $expenditureId, ?int $parentDefId)
    {
        $existingDef = ProjectActivityDefinition::where('project_id', $projectId)
            ->where('program', $program)
            ->where('status', 'active')
            ->first();

        if ($existingDef) {
            if ($existingDef->expenditure_id !== $expenditureId || $existingDef->parent_id !== $parentDefId) {
                throw new \Exception("Conflicting definition for program '{$program}'.");
            }
            return $existingDef;
        }

        return ProjectActivityDefinition::create([
            'project_id' => $projectId,
            'program' => $program,
            'expenditure_id' => $expenditureId,
            'description' => null,
            'status' => 'active',
            'parent_id' => $parentDefId,
        ]);
    }

    private function createPlans(array $data, array $processed, array $definitionIdMap, int $fiscalYearId, int $projectId, string $section, array $parentMap): void
    {
        $flatRows = $data['rows'];

        foreach ($processed as $internalId) {
            $rowData = $flatRows[$internalId];
            $defId = $definitionIdMap[$internalId];

            $this->validateSums($rowData);
            $this->validateChildSums($flatRows, $internalId, $parentMap, $definitionIdMap, $fiscalYearId);

            $this->upsertPlan($defId, $fiscalYearId, $rowData);
        }
    }

    private function upsertPlan(int $defId, int $fiscalYearId, array $rowData): void
    {
        $activityDef = ProjectActivityDefinition::findOrFail($defId);

        $programOverride = null;
        $overrideModifiedAt = null;

        if (isset($rowData['program_override']) && $rowData['program_override'] !== $activityDef->program) {
            $programOverride = $rowData['program_override'];
            $overrideModifiedAt = now();
        }

        $planData = [
            'activity_definition_id' => $defId,
            'fiscal_year_id' => $fiscalYearId,
            'program_override' => $programOverride,
            'override_modified_at' => $overrideModifiedAt,
            'total_budget' => (float) ($rowData['total_budget'] ?? 0),
            'planned_budget' => (float) ($rowData['planned_budget'] ?? 0),
            'q1_amount' => (float) ($rowData['q1'] ?? 0),
            'q2_amount' => (float) ($rowData['q2'] ?? 0),
            'q3_amount' => (float) ($rowData['q3'] ?? 0),
            'q4_amount' => (float) ($rowData['q4'] ?? 0),
            'total_quantity' => (float) ($rowData['total_budget_quantity'] ?? 0),
            'planned_quantity' => (float) ($rowData['planned_budget_quantity'] ?? 0),
            'q1_quantity' => (float) ($rowData['q1_quantity'] ?? 0),
            'q2_quantity' => (float) ($rowData['q2_quantity'] ?? 0),
            'q3_quantity' => (float) ($rowData['q3_quantity'] ?? 0),
            'q4_quantity' => (float) ($rowData['q4_quantity'] ?? 0),
            'total_expense' => (float) ($rowData['total_expense'] ?? 0),
            'completed_quantity' => (float) ($rowData['total_expense_quantity'] ?? 0),
        ];

        ProjectActivityPlan::updateOrCreate(
            [
                'activity_definition_id' => $defId,
                'fiscal_year_id' => $fiscalYearId
            ],
            $planData
        );
    }

    private function validateSums(array $rowData): void
    {
        $quarterAmountSum = ($rowData['q1'] ?? 0) + ($rowData['q2'] ?? 0) +
            ($rowData['q3'] ?? 0) + ($rowData['q4'] ?? 0);
        $plannedBudget = $rowData['planned_budget'] ?? 0;

        if (abs($quarterAmountSum - $plannedBudget) > 0.01) {
            throw ValidationException::withMessages([
                "{$rowData['original_index']}.planned_budget" =>
                "Planned budget must equal sum of quarters."
            ]);
        }

        $quarterQuantitySum = ($rowData['q1_quantity'] ?? 0) + ($rowData['q2_quantity'] ?? 0) +
            ($rowData['q3_quantity'] ?? 0) + ($rowData['q4_quantity'] ?? 0);
        $plannedQuantity = $rowData['planned_budget_quantity'] ?? 0;

        if (abs($quarterQuantitySum - $plannedQuantity) > 0.01) {
            throw ValidationException::withMessages([
                "{$rowData['original_index']}.planned_budget_quantity" =>
                "Planned quantity must equal sum of quarter quantities."
            ]);
        }
    }

    private function validateChildSums(array $data, int $internalId, array $parentMap, array $definitionIdMap, int $fiscalYearId): void
    {
        $flatRows = $data['rows'];
        $rowData = $flatRows[$internalId];
        $parentInternalId = $parentMap[$internalId] ?? null;

        if ($parentInternalId === null) {
            return;
        }

        $parentDefId = $definitionIdMap[$parentInternalId] ?? null;
        $parentPlan = ProjectActivityPlan::where('activity_definition_id', $parentDefId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->first();

        if (!$parentPlan) {
            return;
        }

        $fields = [
            'total_budget' => 'total_budget',
            'total_expense' => 'total_expense',
            'planned_budget' => 'planned_budget',
            'q1' => 'q1_amount',
            'q2' => 'q2_amount',
            'q3' => 'q3_amount',
            'q4' => 'q4_amount',
        ];

        foreach ($fields as $rowKey => $planField) {
            $childValue = (float) ($rowData[$rowKey] ?? 0);
            $parentValue = $parentPlan->{$planField} ?? 0;

            $siblingSum = $this->calculateSiblingSum($flatRows, $internalId, $parentInternalId, $rowKey, $childValue);

            if ($siblingSum > $parentValue + 0.01) {
                throw ValidationException::withMessages([
                    "{$rowData['original_index']}.{$rowKey}" =>
                    "Children sum exceeds parent value."
                ]);
            }
        }
    }

    private function calculateSiblingSum(array $flatRows, int $currentId, int $parentId, string $field, float $currentValue): float
    {
        $sum = $currentValue;

        foreach ($flatRows as $sibId => $sibData) {
            if ($sibId !== $currentId && ($sibData['resolved_parent_internal_id'] ?? null) === $parentId) {
                $sum += (float) ($sibData[$field] ?? 0);
            }
        }

        return $sum;
    }
}
