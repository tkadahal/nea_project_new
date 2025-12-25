<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectActivityDefinition;
use App\Models\ProjectActivityPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProjectActivityService
{
    public function __construct(
        private readonly ProjectActivityRowBuilder $rowBuilder,
        private readonly ProjectActivityProcessor $processor
    ) {}

    /* -----------------------------------------------------------------
     | Public Row Building Methods
     |----------------------------------------------------------------- */

    public function buildRowsForProject(Project $project, ?int $fiscalYearId): array
    {
        $capitalDefs = ProjectActivityDefinition::currentVersion($project->id)
            ->where('expenditure_id', 1)
            ->get();

        $recurrentDefs = ProjectActivityDefinition::currentVersion($project->id)
            ->where('expenditure_id', 2)
            ->get();

        $capitalRows = $this->rowBuilder->buildFromDefinitions($capitalDefs, 'capital', $fiscalYearId);
        $recurrentRows = $this->rowBuilder->buildFromDefinitions($recurrentDefs, 'recurrent', $fiscalYearId);

        return [$capitalRows, $recurrentRows];
    }

    public function buildRowsForEdit(Project $project, int $fiscalYearId): array
    {
        $capitalDefs = ProjectActivityDefinition::currentVersion($project->id)
            ->where('expenditure_id', 1)
            ->whereNull('parent_id')
            ->with([
                'children.children',
                'plans' => fn($q) => $q->where('fiscal_year_id', $fiscalYearId),
                'children.plans' => fn($q) => $q->where('fiscal_year_id', $fiscalYearId),
                'children.children.plans' => fn($q) => $q->where('fiscal_year_id', $fiscalYearId),
            ])
            ->ordered()
            ->get();

        $recurrentDefs = ProjectActivityDefinition::currentVersion($project->id)
            ->where('expenditure_id', 2)
            ->whereNull('parent_id') // Only top-level
            ->with([
                'children.children', // Eager load nested children
                'plans' => fn($q) => $q->where('fiscal_year_id', $fiscalYearId),
                'children.plans' => fn($q) => $q->where('fiscal_year_id', $fiscalYearId),
                'children.children.plans' => fn($q) => $q->where('fiscal_year_id', $fiscalYearId),
            ])
            ->ordered()
            ->get();

        $capitalRows = $this->rowBuilder->buildFromDefinitions($capitalDefs, 'capital', $fiscalYearId);
        $recurrentRows = $this->rowBuilder->buildFromDefinitions($recurrentDefs, 'recurrent', $fiscalYearId);

        return [$capitalRows, $recurrentRows];
    }

    public function getActivityDataForAjax(Project $project, ?int $fiscalYearId): array
    {
        if (!$fiscalYearId) {
            return $this->buildRowsForProject($project, null);
        }

        $hasPlans = ProjectActivityPlan::whereHas('definitionVersion', fn($q) => $q->where('project_id', $project->id))
            ->where('fiscal_year_id', $fiscalYearId)
            ->exists();

        return $hasPlans
            ? $this->buildRowsForEdit($project, $fiscalYearId)
            : $this->buildRowsForProject($project, $fiscalYearId);
    }

    /* -----------------------------------------------------------------
     | Store / Update Activities
     |----------------------------------------------------------------- */

    public function storeActivities(array $validated): void
    {
        DB::transaction(function () use ($validated) {
            $projectId = (int) $validated['project_id'];
            $fiscalYearId = (int) $validated['fiscal_year_id'];

            $this->processSection($validated, 'capital', $projectId, $fiscalYearId, 1);

            if (!empty($validated['recurrent'] ?? [])) {
                $this->processSection($validated, 'recurrent', $projectId, $fiscalYearId, 2);
            }

            $this->cleanupOrphanedDefinitions($projectId, $validated);
        });
    }

    public function updateActivities(array $validated, int $projectId, int $fiscalYearId): void
    {
        DB::transaction(function () use ($validated, $projectId, $fiscalYearId) {
            Log::info("=== UPDATE ACTIVITIES START ===", [
                'project_id' => $projectId,
                'fiscal_year_id' => $fiscalYearId
            ]);

            $this->processSection($validated, 'capital', $projectId, $fiscalYearId, 1);
            $this->processSection($validated, 'recurrent', $projectId, $fiscalYearId, 2);

            $this->cleanupOrphanedDefinitions($projectId, $validated);

            Log::info("=== UPDATE ACTIVITIES COMPLETE ===");
        });
    }

    /* -----------------------------------------------------------------
     | Private Processing Logic
     |----------------------------------------------------------------- */

    private function processSection(
        array &$validated,
        string $section,
        int $projectId,
        int $fiscalYearId,
        int $expenditureId
    ): void {
        $sectionData = $validated[$section] ?? [];

        if (empty($sectionData)) {
            return;
        }

        // Detect if this is the first time setup for this fiscal year + expenditure type
        // If no plans exist yet → we are in definition setup mode → allow updating definition fields
        $isFirstTimeSetup = !ProjectActivityPlan::whereHas('definitionVersion', function ($q) use ($projectId, $expenditureId) {
            $q->where('project_id', $projectId)
                ->where('expenditure_id', $expenditureId);
        })->where('fiscal_year_id', $fiscalYearId)->exists();

        Log::info("Processing {$section} section for FY {$fiscalYearId} - " .
            ($isFirstTimeSetup ? 'FIRST TIME SETUP → will update definitions' : 'PLANNING MODE → definitions locked'));

        $rows = $this->normalizeSectionData($sectionData, $projectId, $expenditureId);

        $tempToRealId = $this->createNewDefinitions($rows, $projectId, $expenditureId);

        // Only update definition fields (program, total_budget, total_quantity) during first-time setup
        if ($isFirstTimeSetup) {
            $this->updateExistingDefinitions($rows, $tempToRealId);
        } else {
            Log::info("Skipping definition field updates (program, total_budget, total_budget_quantity) — already in planning phase");
        }

        $this->reassignSortIndices($rows, $projectId, $expenditureId);

        $this->updateValidatedWithRealIds($validated, $section, $tempToRealId);

        $remainingTempKeys = array_filter(
            array_keys($validated[$section]),
            fn($key) => str_starts_with((string) $key, 'temp_')
        );

        if (!empty($remainingTempKeys)) {
            throw new \RuntimeException("Failed to resolve temporary IDs in {$section} section.");
        }

        // Always process the plans (create on first save, update on subsequent)
        $this->processor->processSection($validated, $section, $projectId, $fiscalYearId, $expenditureId);
    }

    private function normalizeSectionData(array $sectionData, int $projectId, int $expenditureId): array
    {
        $allRows = [];
        foreach ($sectionData as $key => $rowData) {
            $strKey = (string) $key;
            $rowData['id'] = $strKey;
            $rowData['project_id'] = $projectId;
            $rowData['expenditure_id'] = $expenditureId;
            $rowData['sort_index'] = $rowData['sort_index'] ?? '';
            $rowData['parent_id'] = $rowData['parent_id'] ?? null;
            $rowData['depth'] = (int) ($rowData['depth'] ?? 0);
            $allRows[$strKey] = $rowData;
        }
        return $allRows;
    }

    private function createNewDefinitions(array $allRows, int $projectId, int $expenditureId): array
    {
        $tempToId = [];
        $newRows = array_filter($allRows, fn($row) => str_starts_with($row['id'], 'temp_'));

        if (empty($newRows)) {
            Log::info("No new rows to create (no temp_ IDs found)");
            return $tempToId;
        }

        Log::info("Creating new definitions", ['count' => count($newRows)]);

        uasort($newRows, [$this, 'compareRowsForTopoSort']);

        $currentVersion = ProjectActivityDefinition::getCurrentVersionNumber($projectId);
        Log::info("Current version for project {$projectId}: {$currentVersion}");

        foreach ($newRows as $tempKey => $rowData) {
            $parentId = $rowData['parent_id'];
            if ($parentId && str_starts_with((string)$parentId, 'temp_')) {
                $rowData['parent_id'] = $tempToId[$parentId] ?? null;
            }

            $definition = ProjectActivityDefinition::create([
                'project_id' => $projectId,
                'expenditure_id' => $expenditureId,
                'parent_id' => $rowData['parent_id'],
                'sort_index' => $rowData['sort_index'],
                'depth' => $rowData['depth'],
                'program' => $rowData['program'] ?? null,
                'total_budget' => (float) ($rowData['total_budget'] ?? 0),
                'total_quantity' => (float) ($rowData['total_budget_quantity'] ?? 0),
                'version' => $currentVersion,
                'is_current' => true,
                'versioned_at' => now(),
            ]);

            $tempToId[$tempKey] = $definition->id;
            Log::info("Created definition: {$tempKey} -> {$definition->id}");
        }

        return $tempToId;
    }

    private function updateExistingDefinitions(array $allRows, array $tempToId): void
    {
        $existing = array_filter($allRows, fn($row) => is_numeric($row['id']));

        if (empty($existing)) {
            Log::info("No existing definitions to update");
            return;
        }

        Log::info("Updating existing definitions", ['count' => count($existing)]);

        foreach ($existing as $strId => $rowData) {
            $intId = (int) $strId;

            $parentId = $rowData['parent_id'];
            if ($parentId && str_starts_with((string)$parentId, 'temp_')) {
                $rowData['parent_id'] = $tempToId[$parentId] ?? null;
            }

            $definition = ProjectActivityDefinition::findOrFail($intId);

            $definition->update([
                'parent_id' => $rowData['parent_id'],
                'program' => $rowData['program'] ?? null,
                'total_budget' => (float) ($rowData['total_budget'] ?? 0),
                'total_quantity' => (float) ($rowData['total_budget_quantity'] ?? 0),
            ]);

            Log::info("Updated definition: {$intId}");
        }
    }

    private function updateValidatedWithRealIds(array &$validated, string $section, array $tempToId): void
    {
        if (empty($tempToId)) {
            Log::info("No temp IDs to replace in section: {$section}");
            return;
        }

        Log::info("Replacing temp IDs with real IDs in section: {$section}", [
            'mapping' => $tempToId,
            'before_keys' => array_keys($validated[$section] ?? [])
        ]);

        foreach ($tempToId as $tempKey => $realId) {
            if (isset($validated[$section][$tempKey])) {
                $rowData = $validated[$section][$tempKey];
                $rowData['id'] = $realId;

                if (isset($rowData['parent_id']) && str_starts_with((string)$rowData['parent_id'], 'temp_')) {
                    $rowData['parent_id'] = $tempToId[$rowData['parent_id']] ?? null;
                }

                $validated[$section][$realId] = $rowData;
                unset($validated[$section][$tempKey]);

                Log::info("Replaced {$tempKey} -> {$realId}");
            }
        }

        foreach ($validated[$section] as $key => &$rowData) {
            if (isset($rowData['parent_id']) && str_starts_with((string)$rowData['parent_id'], 'temp_')) {
                $oldParentId = $rowData['parent_id'];
                $rowData['parent_id'] = $tempToId[$rowData['parent_id']] ?? null;
                Log::info("Updated parent_id reference in row {$key}: {$oldParentId} -> {$rowData['parent_id']}");
            }
        }

        Log::info("After replacement", ['keys' => array_keys($validated[$section] ?? [])]);
    }

    private function cleanupOrphanedDefinitions(int $projectId, array $validated): void
    {
        $submittedIds = [];
        foreach (['capital', 'recurrent'] as $section) {
            $keys = array_keys($validated[$section] ?? []);
            $submittedIds = array_merge($submittedIds, array_filter($keys, 'is_numeric'));
        }

        if (empty($submittedIds)) {
            Log::info("No submitted IDs to preserve during cleanup");
            return;
        }

        Log::info("Cleaning up orphaned definitions", [
            'project_id' => $projectId,
            'submitted_ids' => $submittedIds
        ]);

        $deleted = ProjectActivityDefinition::where('project_id', $projectId)
            ->whereIn('expenditure_id', [1, 2])
            ->whereNotIn('id', $submittedIds)
            ->where('is_current', true)
            ->delete();

        Log::info("Deleted {$deleted} orphaned definitions");
    }

    private function reassignSortIndices(array $allRows, int $projectId, int $expenditureId): void
    {
        $topLevel = array_filter($allRows, fn($row) => !$row['parent_id']);

        usort($topLevel, [$this, 'compareSortIndex']);

        $topIndex = 1;
        foreach ($topLevel as $row) {
            $this->assignSortIndexRecursive($row, $allRows, $projectId, $expenditureId, $topIndex, 0);
            $topIndex++;
        }
    }

    private function assignSortIndexRecursive(array $row, array $allRows, int $projectId, int $expenditureId, int $levelIndex, int $depth): void
    {
        $id = $row['id'];
        $definition = ProjectActivityDefinition::find($id);
        if (!$definition) {
            return;
        }

        $sortIndex = $depth === 0 ? (string) $levelIndex : $this->getParentSortIndex($definition->parent_id) . '.' . $levelIndex;

        $definition->update([
            'sort_index' => $sortIndex,
            'depth' => $depth,
        ]);

        $children = array_filter($allRows, fn($r) => $r['parent_id'] == $id);
        if (!empty($children)) {
            usort($children, [$this, 'compareSortIndex']);
            $childIndex = 1;
            foreach ($children as $childRow) {
                $this->assignSortIndexRecursive($childRow, $allRows, $projectId, $expenditureId, $childIndex, $depth + 1);
                $childIndex++;
            }
        }
    }

    private function getParentSortIndex(?int $parentId): string
    {
        if (!$parentId) {
            return '';
        }
        $parent = ProjectActivityDefinition::find($parentId);
        return $parent?->sort_index ?? '';
    }

    private function compareSortIndex(array $a, array $b): int
    {
        return $this->naturalCompare($a['sort_index'] ?? '0', $b['sort_index'] ?? '0');
    }

    private function compareRowsForTopoSort(array $a, array $b): int
    {
        if ($a['depth'] !== $b['depth']) {
            return $a['depth'] <=> $b['depth'];
        }
        return $this->compareSortIndex($a, $b);
    }

    private function naturalCompare(string $a, string $b): int
    {
        $partsA = explode('.', $a);
        $partsB = explode('.', $b);
        $max = max(count($partsA), count($partsB));

        for ($i = 0; $i < $max; $i++) {
            $aPart = (int) ($partsA[$i] ?? 0);
            $bPart = (int) ($partsB[$i] ?? 0);
            if ($aPart !== $bPart) {
                return $aPart <=> $bPart;
            }
        }
        return 0;
    }
}
