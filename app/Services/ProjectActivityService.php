<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectActivityPlan;
use Illuminate\Support\Facades\Log;
use App\Models\ProjectActivityDefinition;
use Illuminate\Validation\ValidationException;

class ProjectActivityService
{
    public function __construct(
        private readonly ProjectActivityRowBuilder $rowBuilder,
        private readonly ProjectActivityProcessor $processor
    ) {}

    public function buildRowsForProject(Project $project, ?int $fiscalYearId): array
    {
        // MODIFIED: getDefinitions loads total_budget/total_quantity from definitions; rowBuilder uses them for fixed display
        // Always load base definitions (ignore FY for pure create mode)
        $capitalDefinitions = $this->getDefinitions($project, 1);
        $recurrentDefinitions = $this->getDefinitions($project, 2);

        $capitalRows = $this->rowBuilder->buildFromDefinitions($capitalDefinitions, 'capital', $fiscalYearId);
        $recurrentRows = $this->rowBuilder->buildFromDefinitions($recurrentDefinitions, 'recurrent', $fiscalYearId);

        // FIXED: Ensure ID consistency for hierarchy (cast to string in output arrays)
        $capitalRows = $this->castIdsToString($capitalRows);
        $recurrentRows = $this->castIdsToString($recurrentRows);

        return [$capitalRows, $recurrentRows];
    }

    public function buildRowsForEdit(Project $project, int $fiscalYearId): array
    {
        // MODIFIED: getDefinitionsWithPlans loads total_budget/total_quantity from definitions; planned from plans
        $capitalDefinitions = $this->getDefinitionsWithPlans($project, 1, $fiscalYearId);
        $recurrentDefinitions = $this->getDefinitionsWithPlans($project, 2, $fiscalYearId);

        $capitalRows = $this->rowBuilder->buildFromDefinitions($capitalDefinitions, 'capital', $fiscalYearId);
        $recurrentRows = $this->rowBuilder->buildFromDefinitions($recurrentDefinitions, 'recurrent', $fiscalYearId);

        // FIXED: Ensure ID consistency for hierarchy (cast to string in output arrays)
        $capitalRows = $this->castIdsToString($capitalRows);
        $recurrentRows = $this->castIdsToString($recurrentRows);

        return [$capitalRows, $recurrentRows];
    }

    public function getActivityDataForAjax(Project $project, ?int $fiscalYearId): array
    {
        // Handle null FY gracefully (fallback to definitions only)
        if (!$fiscalYearId) {
            return $this->buildRowsForProject($project, null);
        }

        $capitalPlans = $this->getPlansForExpenditureType($project->id, $fiscalYearId, 1);
        $recurrentPlans = $this->getPlansForExpenditureType($project->id, $fiscalYearId, 2);

        $isEditMode = $capitalPlans->isNotEmpty() || $recurrentPlans->isNotEmpty();

        if ($isEditMode) {
            // MODIFIED: buildFromPlans uses total from defs, planned from plans
            $capitalRows = $this->rowBuilder->buildFromPlans($capitalPlans, 'capital', $fiscalYearId);
            $recurrentRows = $this->rowBuilder->buildFromPlans($recurrentPlans, 'recurrent', $fiscalYearId);

            // FIXED: Ensure ID consistency for hierarchy (cast to string in output arrays)
            $capitalRows = $this->castIdsToString($capitalRows);
            $recurrentRows = $this->castIdsToString($recurrentRows);

            return [$capitalRows, $recurrentRows];
        }

        return $this->buildRowsForProject($project, $fiscalYearId);
    }

    /**
     * UPDATED: Handle temp IDs for new rows, resolve hierarchy, create definitions, then process plans via processor
     */
    public function storeActivities(array $validated): void
    {
        Log::info('storeActivities started', ['project_id' => $validated['project_id'], 'fiscal_year_id' => $validated['fiscal_year_id']]);

        // $this->validateBudget($validated);  // Uncomment if needed

        try {
            DB::transaction(function () use ($validated) {
                // FIXED: Cast to int to match method signature (strict_types)
                $projectId = (int) $validated['project_id'];
                $fiscalYearId = (int) $validated['fiscal_year_id'];

                Log::info('Processing definitions for capital (projectId cast to ' . $projectId . ')');
                $this->processDefinitionsSection($validated, 'capital', $projectId, 1);
                Log::info('Capital defs done. Validated capital keys: ' . implode(', ', array_keys($validated['capital'] ?? [])));

                Log::info('Processing plans for capital');
                $this->processor->processSection($validated, 'capital', $projectId, $fiscalYearId, 1);
                Log::info('Capital plans done');

                // Skip recurrent if empty
                if (!empty($validated['recurrent'] ?? [])) {
                    Log::info('Processing definitions for recurrent');
                    $this->processDefinitionsSection($validated, 'recurrent', $projectId, 2);
                    Log::info('Recurrent defs done');

                    Log::info('Processing plans for recurrent');
                    $this->processor->processSection($validated, 'recurrent', $projectId, $fiscalYearId, 2);
                    Log::info('Recurrent plans done');
                } else {
                    Log::info('Skipping recurrent (empty)');
                }

                // Cleanup only if data present (safer)
                if (!empty($validated['capital'] ?? []) || !empty($validated['recurrent'] ?? [])) {
                    Log::info('Cleaning orphans');
                    $this->cleanupOrphanedDefinitions($projectId, [1, 2], array_keys($validated['capital'] ?? []), array_keys($validated['recurrent'] ?? []));
                }
            });

            Log::info('Transaction committed successfully');
        } catch (\Exception $e) {
            Log::error('storeActivities failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'validated' => $validated
            ]);
            throw $e;  // Re-throw for controller to handle (back with error)
        }
    }

    /**
     * UPDATED: Similar to store, but assumes existing definitions; updates them and resolves temps for new children
     */
    public function updateActivities(array $validated, int $projectId, int $fiscalYearId): void
    {
        DB::transaction(function () use ($validated, $projectId, $fiscalYearId) {
            // FIXED: Process definitions and update $validated with real IDs/structure intact
            $this->processDefinitionsSection($validated, 'capital', $projectId, 1);
            $this->processDefinitionsSection($validated, 'recurrent', $projectId, 2);

            // Process plans via existing processor
            $this->processor->processSection($validated, 'capital', $projectId, $fiscalYearId, 1);
            $this->processor->processSection($validated, 'recurrent', $projectId, $fiscalYearId, 2);

            // Clean up orphaned definitions
            $this->cleanupOrphanedDefinitions($projectId, [1, 2], array_keys($validated['capital'] ?? []), array_keys($validated['recurrent'] ?? []));
        });
    }

    /**
     * FIXED: Process definitions section (create new from temps, update existing, resolve parents, re-index)
     * - Removed expense fields from definition updates (they belong to plans)
     * - Fixed $validated rekeying to preserve structure (no overwriting values)
     */
    private function processDefinitionsSection(array &$validated, string $section, int $projectId, int $expenditureId): void
    {
        // TEST: Immediate entry log - if this doesn't show, method not entered
        Log::info("Defs: METHOD ENTERED for {$section}, project {$projectId}, exp {$expenditureId}");

        $sectionData = $validated[$section] ?? [];
        Log::info("Defs: Section data after fetch: " . (empty($sectionData) ? 'EMPTY' : count($sectionData) . ' rows'));
        if (empty($sectionData)) {
            Log::info("Defs: Empty {$section}, skipping");
            return;
        }

        // FIXED: Cast keys to string to avoid int key issues in PHP assoc arrays
        $allRows = [];
        $tempToId = [];  // Map temp -> real ID

        Log::info("Defs: Building allRows...");
        foreach ($sectionData as $key => $rowData) {
            $strKey = (string) $key;  // Cast to string
            Log::info("Defs: Processing key {$strKey} (original type: " . gettype($key) . "), parent_id: " . ($rowData['parent_id'] ?? 'null'));
            $rowData['id'] = $strKey;  // Key as ID (temp or real)
            $rowData['project_id'] = $projectId;
            $rowData['expenditure_id'] = $expenditureId;
            $rowData['sort_index'] = $rowData['sort_index'] ?? '';
            $rowData['parent_id'] = $rowData['parent_id'] ?? null;
            $rowData['depth'] = (int) ($rowData['depth'] ?? 0);
            $allRows[$strKey] = $rowData;
        }
        Log::info("Defs: allRows built with " . count($allRows) . " entries, keys: " . implode(', ', array_keys($allRows)));

        // Identify new rows (temp keys)
        // Identify new rows (temp keys)
        $newRows = array_filter($allRows, fn($row) => str_starts_with((string)$row['id'], 'temp_'));
        Log::info("Defs: New rows count: " . count($newRows));

        if (!empty($newRows)) {
            Log::info("Defs: Found new rows; topo-sorting");
            // Topo-sort new rows: parents before children (by depth, then sort_index)
            uasort($newRows, [$this, 'compareRowsForTopoSort']);

            // Create new definitions in order
            foreach ($newRows as $tempKey => $rowData) {
                try {
                    Log::info("Defs: Creating new def for temp {$tempKey}, parent {$rowData['parent_id']}, depth {$rowData['depth']}");
                    // Resolve parent_id if temp
                    $parentId = $rowData['parent_id'];
                    if ($parentId && str_starts_with((string)$parentId, 'temp_')) {
                        $rowData['parent_id'] = $tempToId[$parentId] ?? null;
                        Log::info("Defs: Resolved parent for {$tempKey} to {$rowData['parent_id']}");
                    }

                    // FIXED: Only definition fields (no expenses)
                    $definition = ProjectActivityDefinition::create([
                        'project_id' => $projectId,
                        'expenditure_id' => $expenditureId,
                        'parent_id' => $rowData['parent_id'],
                        'sort_index' => $rowData['sort_index'],  // Temp; will re-index later
                        'depth' => $rowData['depth'],
                        'program' => $rowData['program'] ?? null,
                        'total_budget' => (float) ($rowData['total_budget'] ?? 0),
                        'total_quantity' => (int) ($rowData['total_budget_quantity'] ?? 0),
                        // 'status' => 'active',
                    ]);

                    $tempToId[$tempKey] = $definition->id;
                    Log::info("Defs: Created new def ID {$definition->id} for temp {$tempKey}");

                    // FIXED: Properly rekey $validated: remove old, add new with intact rowData
                    unset($validated[$section][$tempKey]);
                    $rowData['id'] = $definition->id;  // Update rowData ID for allRows/re-index
                    $validated[$section][$definition->id] = $rowData;  // Add with real ID, preserve structure
                    $allRows[$definition->id] = $rowData;  // Update allRows
                } catch (\Exception $e) {
                    Log::error("Defs: Failed to create new def for temp {$tempKey}: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                        'rowData' => $rowData
                    ]);
                    throw $e;
                }
            }
        } else {
            Log::info("Defs: No new rows (temps)");
        }

        // Update existing definitions (numeric keys)
        // FIXED: Filter with string cast to avoid int key issues
        $existingKeys = array_filter(array_keys($allRows), fn($key) => is_numeric((string)$key));
        Log::info("Defs: Found " . count($existingKeys) . " existing keys: " . implode(', ', $existingKeys));

        foreach ($existingKeys as $id) {
            $strId = (string) $id;  // Cast to string for consistency
            try {
                $rowData = $allRows[$strId];
                Log::info("Defs: Processing existing ID {$strId}, parent {$rowData['parent_id']}, program '{$rowData['program']}'");

                // Resolve parent_id if temp (unlikely in update, but safe)
                $parentId = $rowData['parent_id'];
                if ($parentId && str_starts_with((string)$parentId, 'temp_')) {
                    $rowData['parent_id'] = $tempToId[$parentId] ?? null;
                    Log::info("Defs: Resolved temp parent for {$strId} to {$rowData['parent_id']}");
                }

                // FIXED: Use int ID for query
                $intId = (int) $strId;
                $definition = ProjectActivityDefinition::findOrFail($intId);
                Log::info("Defs: Found def {$intId}: project={$definition->project_id}, exp={$definition->expenditure_id}, current program='{$definition->program}'");

                // FIXED: Only definition fields (no expenses)
                $updated = $definition->update([
                    'parent_id' => $rowData['parent_id'],
                    'program' => $rowData['program'] ?? null,
                    'total_budget' => (float) ($rowData['total_budget'] ?? 0),
                    'total_quantity' => (int) ($rowData['total_budget_quantity'] ?? 0),
                ]);
                Log::info("Defs: Update for {$intId} succeeded: " . ($updated ? 'yes' : 'no changes'));

                // Update validated for processor (structure intact)
                $rowData['id'] = $intId;  // Ensure ID is set
                $validated[$section][$intId] = $rowData;
            } catch (\Exception $e) {
                Log::error("Defs: Failed to update existing def {$strId}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'rowData' => $rowData
                ]);
                throw $e;
            }
        }

        // Re-assign sort_indices based on submitted hierarchy/order
        Log::info("Defs: Starting reindex for {$section}");
        $this->reassignSortIndices($allRows, $projectId, $expenditureId);
        Log::info("Defs: Reindex complete for {$section}. Final validated keys: " . implode(', ', array_keys($validated[$section] ?? [])));
    }

    /**
     * FIXED: Clean up orphaned definitions (not submitted)
     */
    private function cleanupOrphanedDefinitions(int $projectId, array $expenditureIds, array $capitalKeys, array $recurrentKeys): void
    {
        $submittedIds = array_merge(
            array_filter($capitalKeys, fn($k) => is_numeric($k)),
            array_filter($recurrentKeys, fn($k) => is_numeric($k))
        );

        foreach ($expenditureIds as $exId) {
            $sectionKeys = $exId === 1 ? $capitalKeys : $recurrentKeys;
            $submittedForSection = array_filter($sectionKeys, fn($k) => is_numeric($k));

            $orphaned = ProjectActivityDefinition::where('project_id', $projectId)
                ->where('expenditure_id', $exId)
                ->whereNotIn('id', $submittedForSection)
                ->get();

            $orphaned->each->delete();  // Cascade deletes children if set
        }
    }

    /**
     * FIXED: Re-assign sort_indices recursively based on hierarchy
     * - Use updated allRows with real IDs
     */
    private function reassignSortIndices(array $allRows, int $projectId, int $expenditureId): void
    {
        // Get top-level rows (null parent)
        $topLevel = array_filter($allRows, fn($row) => !$row['parent_id']);

        // Sort top-level by original sort_index (numeric)
        usort($topLevel, [$this, 'compareSortIndex']);

        $topIndex = 1;
        foreach ($topLevel as $row) {
            $this->assignSortIndexRecursive($row, $allRows, $projectId, $expenditureId, $topIndex, 0);
            $topIndex++;
        }
    }

    /**
     * Helper: Assign sort_index recursively
     */
    private function assignSortIndexRecursive(array $row, array $allRows, int $projectId, int $expenditureId, int $levelIndex, int $depth): void
    {
        $id = $row['id'];
        $definition = ProjectActivityDefinition::where('id', $id)->first();
        if (!$definition) {
            return;
        }

        $sortIndex = $depth === 0 ? (string) $levelIndex : implode('.', [$this->getParentSortIndex($definition->parent_id), $levelIndex]);

        $definition->update([
            'sort_index' => $sortIndex,
            'depth' => $depth,
        ]);

        // Find and sort children
        $children = array_filter($allRows, fn($r) => $r['parent_id'] == $id);
        if (!empty($children)) {
            usort($children, [$this, 'compareSortIndex']);  // Sort by submitted sort_index
            $childIndex = 1;
            foreach ($children as $childRow) {
                $this->assignSortIndexRecursive($childRow, $allRows, $projectId, $expenditureId, $childIndex, $depth + 1);
                $childIndex++;
            }
        }
    }

    /**
     * Helper: Get parent sort_index for child assignment
     */
    private function getParentSortIndex(?int $parentId): string
    {
        if (!$parentId) {
            return '';
        }
        $parent = ProjectActivityDefinition::find($parentId);
        return $parent ? $parent->sort_index : '';
    }

    /**
     * Helper: Compare sort_index (e.g., '1.2' < '2')
     */
    private function compareSortIndex(array $a, array $b): int
    {
        $aIndex = $a['sort_index'] ?? '0';
        $bIndex = $b['sort_index'] ?? '0';
        return $this->naturalCompare($aIndex, $bIndex);
    }

    /**
     * Helper: Topo sort comparator (lower depth first, then sort_index)
     */
    private function compareRowsForTopoSort(array $a, array $b): int
    {
        if ($a['depth'] !== $b['depth']) {
            return $a['depth'] <=> $b['depth'];
        }
        return $this->compareSortIndex($a, $b);
    }

    /**
     * Helper: Natural string compare for indices
     */
    private function naturalCompare(string $a, string $b): int
    {
        $partsA = explode('.', $a);
        $partsB = explode('.', $b);
        $maxLen = max(count($partsA), count($partsB));

        for ($i = 0; $i < $maxLen; $i++) {
            $aPart = (int) ($partsA[$i] ?? 0);
            $bPart = (int) ($partsB[$i] ?? 0);
            if ($aPart !== $bPart) {
                return $aPart <=> $bPart;
            }
        }
        return 0;
    }

    private function getDefinitions(Project $project, int $expenditureId)
    {
        // MODIFIED: Definitions now include total_budget/total_quantity for fixed values
        // FIXED: Eager load full hierarchy (up to depth 2) for complete tree
        return $project->activityDefinitions()
            ->with([
                'children' => function ($query) {
                    $query->with([
                        'children' => function ($subQuery) {
                            $subQuery->with('children'); // Depth 2
                        }
                    ]);
                }
            ])
            ->whereNull('parent_id')
            ->where('expenditure_id', $expenditureId)
            // ->where('status', 'active')
            ->get();
    }

    private function getDefinitionsWithPlans(Project $project, int $expenditureId, int $fiscalYearId)
    {
        return $project->activityDefinitions()
            ->with([
                'children' => function ($query) use ($fiscalYearId) {
                    $query->with([
                        'children.plans' => function ($pQ) use ($fiscalYearId) {
                            $pQ->where('fiscal_year_id', $fiscalYearId);
                        },
                        'plans' => function ($pQ) use ($fiscalYearId) {
                            $pQ->where('fiscal_year_id', $fiscalYearId);
                        }
                    ]);
                },
                'plans' => function ($pQ) use ($fiscalYearId) {
                    $pQ->where('fiscal_year_id', $fiscalYearId);
                }
            ])
            ->whereNull('parent_id')
            ->where('expenditure_id', $expenditureId)
            // ->where('status', 'active')
            ->get();
    }

    private function getPlansForExpenditureType(int $projectId, int $fiscalYearId, int $expenditureId)
    {
        // MODIFIED: Plans load without total_budget/total_quantity; defs provide them via relations
        // FIXED: Ensure full hierarchy via recursive with on definitions
        return ProjectActivityPlan::whereHas('activityDefinition', function ($q) use ($projectId, $expenditureId) {
            $q->where('project_id', $projectId)
                ->where('expenditure_id', $expenditureId);
            // ->where('status', 'active');
        })
            ->where('fiscal_year_id', $fiscalYearId)
            ->with([
                'activityDefinition' => function ($q) {
                    $q->with([
                        'children' => function ($subQ) {
                            $subQ->with([
                                'children.plans',
                                'plans'
                            ]);
                        },
                        'children.plans'
                    ]);
                }
            ])
            ->get();
    }

    /**
     * FIXED: New helper to cast IDs to strings in row arrays for JS consistency (prevents data-index/data-parent mismatch)
     */
    private function castIdsToString(array $rows): array
    {
        return array_map(function ($row) {
            if (isset($row->id)) {
                $row->id = (string) $row->id;
            }
            if (isset($row->parent_id)) {
                $row->parent_id = $row->parent_id !== null ? (string) $row->parent_id : null;
            }
            return $row;
        }, $rows);
    }

    private function validateBudget(array $validated): void
    {
        // MODIFIED: Validation unchanged, as it checks total_planned_budget (sum of planned_budget from plans)
        $budget = \App\Models\Budget::where('project_id', $validated['project_id'])
            ->where('fiscal_year_id', $validated['fiscal_year_id'])
            ->first();

        $remainingBudget = $budget ? (float) $budget->total_budget : 0.0;
        $totalPlannedBudget = (float) ($validated['total_planned_budget'] ?? 0);

        if ($totalPlannedBudget > $remainingBudget) {
            throw ValidationException::withMessages([
                'total_planned_budget' => 'Planned budget exceeds remaining budget for this fiscal year.'
            ]);
        }
    }
}
