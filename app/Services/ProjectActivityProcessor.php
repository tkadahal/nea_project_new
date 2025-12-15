<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProjectActivityDefinition;
use App\Models\ProjectActivityPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProjectActivityProcessor
{
    /**
     * Process a section's plans (create/update based on validated data keys as definition IDs)
     * - Assumes $validated[$section] keys are real definition IDs (post-resolution from service)
     * - Validates sums per row and parent-child if hierarchy info present
     * - Upserts plans without definition fields (total_budget etc. handled in service)
     */
    public function processSection(array &$validated, string $section, int $projectId, int $fiscalYearId, int $expenditureId): void
    {
        Log::info("Plans: METHOD ENTERED for section {$section}, project {$projectId}, FY {$fiscalYearId}, exp {$expenditureId}");
        Log::info("Plans: Section {$section}, data keys: " . implode(', ', array_keys($validated[$section] ?? [])));
        $sectionData = $validated[$section] ?? [];
        if (empty($sectionData)) {
            Log::info("Plans: No data for section {$section}; skipping plans.");
            return;
        }

        // FIXED: Build parent map with int keys/values to avoid type mismatches
        $parentMap = [];
        foreach ($sectionData as $defId => $rowData) {
            $intDefId = (int) $defId;
            $parentId = (int) ($rowData['parent_id'] ?? 0);
            if ($parentId > 0) {
                $parentMap[$intDefId] = $parentId;
            }
        }
        Log::info("Plans: Built parentMap with " . count($parentMap) . " entries");

        // Verify all keys are valid definition IDs
        $invalidKeys = [];
        foreach (array_keys($sectionData) as $key) {
            if (!is_numeric($key) || !$this->isValidDefinition((int) $key, $projectId, $expenditureId)) {
                $invalidKeys[] = $key;
            }
        }

        if (!empty($invalidKeys)) {
            Log::warning("Plans: Invalid definition IDs for {$section}: " . implode(', ', $invalidKeys));
            foreach ($invalidKeys as $key) {
                unset($sectionData[$key]);
            }
            $validated[$section] = $sectionData; // Update validated
            Log::info("Plans: Filtered sectionData now has " . count($sectionData) . " valid rows");
        }

        DB::transaction(function () use ($sectionData, $fiscalYearId, $parentMap) {
            Log::info("Plans: Starting transaction loop for " . count($sectionData) . " rows");
            foreach ($sectionData as $definitionId => $rowData) {
                Log::info("Plans: Entering loop for key {$definitionId}, row keys: " . implode(', ', array_keys($rowData)));
                $definitionId = (int) $definitionId;

                // Validate row sums
                $this->validateRowSums($rowData, $definitionId);

                // Validate child sums if has parent
                $parentId = $parentMap[$definitionId] ?? null;
                if ($parentId) {
                    Log::info("Plans: Validating child sums for child {$definitionId}, parent {$parentId}");
                    $this->validateChildSums($sectionData, $definitionId, $parentId);
                }

                // Prepare plan data (only plan fields)
                $planData = $this->preparePlanData($rowData, $definitionId);
                Log::info("Plans: Prepared plan data for definition {$definitionId}, FY {$fiscalYearId}", $planData);

                // Upsert: create if not exists, update if exists
                $plan = ProjectActivityPlan::updateOrCreate(
                    [
                        'activity_definition_id' => $definitionId,
                        'fiscal_year_id' => $fiscalYearId,
                    ],
                    $planData
                );

                Log::info("Plans: Plan upserted: ID={$plan->id} for definition {$definitionId}, planned_budget={$plan->planned_budget}");
            }
            Log::info("Plans: Transaction loop completed");
        });

        Log::info("Plans: Processed {$section} plans: " . count($sectionData) . " rows successfully");
    }

    /**
     * Validate quarter sums for a single row
     */
    private function validateRowSums(array $rowData, int $definitionId): void
    {
        Log::info("Plans: Validating row sums for def {$definitionId}");
        $quarterAmountSum = (float) ($rowData['q1'] ?? 0) + (float) ($rowData['q2'] ?? 0) +
            (float) ($rowData['q3'] ?? 0) + (float) ($rowData['q4'] ?? 0);
        $plannedBudget = (float) ($rowData['planned_budget'] ?? 0);

        if (abs($quarterAmountSum - $plannedBudget) > 0.01) {
            $error = "Planned budget must equal sum of quarters for definition {$definitionId}. (Sum: {$quarterAmountSum}, Planned: {$plannedBudget})";
            Log::error("Plans: Row sum validation failed for def {$definitionId}: {$error}");
            throw ValidationException::withMessages([
                "planned_budget" => $error
            ]);
        }

        $quarterQuantitySum = (float) ($rowData['q1_quantity'] ?? 0) + (float) ($rowData['q2_quantity'] ?? 0) +
            (float) ($rowData['q3_quantity'] ?? 0) + (float) ($rowData['q4_quantity'] ?? 0);
        $plannedQuantity = (float) ($rowData['planned_budget_quantity'] ?? 0);

        if (abs($quarterQuantitySum - $plannedQuantity) > 0.01) {
            $error = "Planned quantity must equal sum of quarter quantities for definition {$definitionId}. (Sum: {$quarterQuantitySum}, Planned: {$plannedQuantity})";
            Log::error("Plans: Row quantity sum validation failed for def {$definitionId}: {$error}");
            throw ValidationException::withMessages([
                "planned_budget_quantity" => $error
            ]);
        }

        Log::info("Plans: Row sums validated OK for def {$definitionId}");
    }

    /**
     * Validate that child row sums <= parent (for amount and quantity fields, using submitted data)
     */
    private function validateChildSums(array $sectionData, int $childDefId, int $parentDefId): void
    {
        $parentRow = $sectionData[$parentDefId] ?? null;
        if (!$parentRow) {
            Log::warning("Plans: No parent row found for validation (def {$parentDefId}); skipping child sums for {$childDefId}");
            return;
        }

        // Amount fields to check
        $amountFields = ['q1', 'q2', 'q3', 'q4', 'planned_budget', 'total_expense'];

        foreach ($amountFields as $field) {
            $childValue = (float) ($sectionData[$childDefId][$field] ?? 0);
            $parentValue = (float) ($parentRow[$field] ?? 0);

            // Calculate sibling sum (other children of same parent)
            $siblingSum = $this->calculateSiblingSumForField($sectionData, $childDefId, $parentDefId, $field);

            $totalChildSum = $childValue + $siblingSum;

            if ($totalChildSum > $parentValue + 0.01) {
                $error = "Children sum ({$totalChildSum}) exceeds parent value ({$parentValue}) for field '{$field}' under definition {$parentDefId}.";
                Log::error("Plans: Child sum validation failed for field {$field}, child {$childDefId}: {$error}");
                throw ValidationException::withMessages([
                    "{$field}" => $error
                ]);
            }
        }

        // Quantity fields to check
        $quantityFields = ['q1_quantity', 'q2_quantity', 'q3_quantity', 'q4_quantity', 'planned_budget_quantity', 'total_expense_quantity'];

        foreach ($quantityFields as $field) {
            $childValue = (float) ($sectionData[$childDefId][$field] ?? 0);
            $parentValue = (float) ($parentRow[$field] ?? 0);

            // Calculate sibling sum (other children of same parent)
            $siblingSum = $this->calculateSiblingSumForField($sectionData, $childDefId, $parentDefId, $field);

            $totalChildSum = $childValue + $siblingSum;

            if ($totalChildSum > $parentValue + 0.01) {
                $error = "Children quantity sum ({$totalChildSum}) exceeds parent quantity ({$parentValue}) for field '{$field}' under definition {$parentDefId}.";
                Log::error("Plans: Child quantity sum validation failed for field {$field}, child {$childDefId}: {$error}");
                throw ValidationException::withMessages([
                    "{$field}" => $error
                ]);
            }
        }

        Log::info("Plans: Child sums validated OK for child {$childDefId} under parent {$parentDefId}");
    }

    /**
     * FIXED: Calculate sum of siblings for a specific field (cast parent_id to int for comparison)
     */
    private function calculateSiblingSumForField(array $sectionData, int $currentDefId, int $parentDefId, string $field): float
    {
        $sum = 0;
        foreach ($sectionData as $defId => $rowData) {
            if ((int) $defId != $currentDefId && (int) ($rowData['parent_id'] ?? 0) == $parentDefId) {
                $sum += (float) ($rowData[$field] ?? 0);
            }
        }
        return $sum;
    }

    /**
     * Prepare data for plan upsert (only plan fields, map form keys)
     */
    private function preparePlanData(array $rowData, int $definitionId): array
    {
        Log::info("Plans: Preparing plan data for def {$definitionId}");
        $activityDef = ProjectActivityDefinition::findOrFail($definitionId);
        Log::info("Plans: Found def {$definitionId}, program='{$activityDef->program}'");

        $programOverride = null;
        $overrideModifiedAt = null;

        $submittedProgram = trim($rowData['program_override'] ?? '');  // FIXED: Use 'program_override' key if present, else def program
        if ($submittedProgram !== '' && $submittedProgram !== $activityDef->program) {
            $programOverride = $submittedProgram;
            $overrideModifiedAt = now();
            Log::info("Plans: Setting program override '{$programOverride}' with modified_at");
        } else {
            Log::info("Plans: No program override needed");
        }

        $planData = [
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

        Log::info("Plans: Prepared plan data", $planData);
        return $planData;
    }

    /**
     * Check if definition ID is valid for project/expenditure
     */
    private function isValidDefinition(int $definitionId, int $projectId, int $expenditureId): bool
    {
        $exists = ProjectActivityDefinition::where('id', $definitionId)
            ->where('project_id', $projectId)
            ->where('expenditure_id', $expenditureId)
            ->exists();
        Log::info("Plans: Validating def {$definitionId} for project {$projectId}, exp {$expenditureId}: " . ($exists ? 'YES' : 'NO'));
        return $exists;
    }
}
