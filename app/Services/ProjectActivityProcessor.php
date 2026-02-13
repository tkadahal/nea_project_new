<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProjectActivityDefinition;
use App\Models\ProjectActivityPlan;
use Illuminate\Validation\ValidationException;

class ProjectActivityProcessor
{
    public function processSection(array &$validated, string $section, int $projectId, int $fiscalYearId, int $expenditureId): void
    {
        $sectionData = $validated[$section] ?? [];

        if (empty($sectionData)) {
            return;
        }

        $fromExcel = $validated['from_excel'] ?? false;

        foreach ($sectionData as $defId => $rowData) {
            $defId = (int) $defId;

            if (!$this->isValidVersionedDefinition($defId, $projectId, $expenditureId)) {
                continue;
            }

            if (!$fromExcel) {
                try {
                    $this->validateRowSums($rowData, $defId);
                } catch (ValidationException $e) {
                    throw $e;
                }
            }

            $planData = $this->preparePlanData($rowData, $defId);

            $definitionVersion = ProjectActivityDefinition::where('id', $defId)
                ->where('project_id', $projectId)
                ->where('expenditure_id', $expenditureId)
                ->where('is_current', true)
                ->firstOrFail();

            ProjectActivityPlan::updateOrCreate(
                [
                    'activity_definition_version_id' => $definitionVersion->id,
                    'fiscal_year_id' => $fiscalYearId,
                ],
                $planData
            );
        }
    }

    private function validateRowSums(array $rowData, $identifier): void
    {
        $quarterAmountSum = (float) ($rowData['q1'] ?? 0) + (float) ($rowData['q2'] ?? 0) +
            (float) ($rowData['q3'] ?? 0) + (float) ($rowData['q4'] ?? 0);
        $plannedBudget = (float) ($rowData['planned_budget'] ?? 0);

        if (abs($quarterAmountSum - $plannedBudget) > 0.01) {
            throw ValidationException::withMessages([
                'planned_budget' => "Quarter amounts must sum to planned budget (row {$identifier})"
            ]);
        }

        $quarterQuantitySum = (float) ($rowData['q1_quantity'] ?? 0) + (float) ($rowData['q2_quantity'] ?? 0) +
            (float) ($rowData['q3_quantity'] ?? 0) + (float) ($rowData['q4_quantity'] ?? 0);
        $plannedQuantity = (float) ($rowData['planned_budget_quantity'] ?? 0);

        if (abs($quarterQuantitySum - $plannedQuantity) > 0.01) {
            throw ValidationException::withMessages([
                'planned_budget_quantity' => "Quarter quantities must sum to planned quantity (row {$identifier})"
            ]);
        }
    }

    private function validateChildSums(array $sectionData, $childKey, $parentKey): void
    {
        $parentRow = $sectionData[$parentKey] ?? null;
        if (!$parentRow) return;

        $fields = ['q1', 'q2', 'q3', 'q4', 'planned_budget', 'total_expense'];
        foreach ($fields as $field) {
            $childValue = (float) ($sectionData[$childKey][$field] ?? 0);
            $siblingSum = $this->calculateSiblingSum($sectionData, $childKey, $parentKey, $field);
            $total = $childValue + $siblingSum;
            $parentValue = (float) ($parentRow[$field] ?? 0);

            if ($total > $parentValue + 0.01) {
                throw ValidationException::withMessages([
                    $field => "Children exceed parent for {$field} (parent {$parentKey})"
                ]);
            }
        }

        $qtyFields = ['q1_quantity', 'q2_quantity', 'q3_quantity', 'q4_quantity', 'planned_budget_quantity', 'total_expense_quantity'];
        foreach ($qtyFields as $field) {
            $childValue = (float) ($sectionData[$childKey][$field] ?? 0);
            $siblingSum = $this->calculateSiblingSum($sectionData, $childKey, $parentKey, $field);
            $total = $childValue + $siblingSum;
            $parentValue = (float) ($parentRow[$field] ?? 0);

            if ($total > $parentValue + 0.01) {
                throw ValidationException::withMessages([
                    $field => "Children exceed parent quantity for {$field} (parent {$parentKey})"
                ]);
            }
        }
    }

    private function calculateSiblingSum(array $sectionData, $currentKey, $parentKey, string $field): float
    {
        $sum = 0;
        foreach ($sectionData as $key => $row) {
            if ($key !== $currentKey && ($row['parent_id'] ?? null) === $parentKey) {
                $sum += (float) ($row[$field] ?? 0);
            }
        }
        return $sum;
    }

    private function preparePlanData(array $rowData, int $definitionId): array
    {
        $definition = ProjectActivityDefinition::findOrFail($definitionId);

        $programOverride = null;
        $overrideModifiedAt = null;

        $submittedProgram = trim($rowData['program'] ?? '');
        if ($submittedProgram !== '' && $submittedProgram !== $definition->program) {
            $programOverride = $submittedProgram;
            $overrideModifiedAt = now();
        }

        return [
            'program_override' => $programOverride,
            'override_modified_at' => $overrideModifiedAt,
            'planned_budget' => (float) ($rowData['planned_budget'] ?? 0),
            'planned_quantity' => (float) ($rowData['planned_budget_quantity'] ?? 0),
            'q1_amount' => (float) ($rowData['q1'] ?? 0),
            'q1_quantity' => (float) ($rowData['q1_quantity'] ?? 0),
            'q2_amount' => (float) ($rowData['q2'] ?? 0),
            'q2_quantity' => (float) ($rowData['q2_quantity'] ?? 0),
            'q3_amount' => (float) ($rowData['q3'] ?? 0),
            'q3_quantity' => (float) ($rowData['q3_quantity'] ?? 0),
            'q4_amount' => (float) ($rowData['q4'] ?? 0),
            'q4_quantity' => (float) ($rowData['q4_quantity'] ?? 0),
            'total_expense' => (float) ($rowData['total_expense'] ?? 0),
            'completed_quantity' => (float) ($rowData['total_expense_quantity'] ?? 0),
        ];
    }

    private function isValidVersionedDefinition(int $definitionId, int $projectId, int $expenditureId): bool
    {
        return ProjectActivityDefinition::where('id', $definitionId)
            ->where('project_id', $projectId)
            ->where('expenditure_id', $expenditureId)
            ->where('is_current', true)
            ->exists();
    }
}
