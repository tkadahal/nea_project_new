<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectActivityPlan;
use Illuminate\Support\Facades\Log;
use App\Models\ProjectActivityDefinition;
use App\Exceptions\StructuralChangeRequiresConfirmationException;

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
            ->orderByRaw("string_to_array(sort_index, '.')::int[]")
            ->get();

        $recurrentDefs = ProjectActivityDefinition::currentVersion($project->id)
            ->where('expenditure_id', 2)
            ->orderByRaw("string_to_array(sort_index, '.')::int[]")
            ->get();

        // Sort collections using natural sort
        $capitalDefs = $capitalDefs->sort(function ($a, $b) {
            return strnatcmp($a->sort_index, $b->sort_index);
        })->values();

        $recurrentDefs = $recurrentDefs->sort(function ($a, $b) {
            return strnatcmp($a->sort_index, $b->sort_index);
        })->values();

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
            ->orderByRaw("string_to_array(sort_index, '.')::int[]")
            ->get();

        $recurrentDefs = ProjectActivityDefinition::currentVersion($project->id)
            ->where('expenditure_id', 2)
            ->whereNull('parent_id')
            ->with([
                'children.children',
                'plans' => fn($q) => $q->where('fiscal_year_id', $fiscalYearId),
                'children.plans' => fn($q) => $q->where('fiscal_year_id', $fiscalYearId),
                'children.children.plans' => fn($q) => $q->where('fiscal_year_id', $fiscalYearId),
            ])
            ->orderByRaw("string_to_array(sort_index, '.')::int[]")
            ->get();

        // Sort collections
        $capitalDefs = $capitalDefs->sort(function ($a, $b) {
            return strnatcmp($a->sort_index, $b->sort_index);
        })->values();

        $recurrentDefs = $recurrentDefs->sort(function ($a, $b) {
            return strnatcmp($a->sort_index, $b->sort_index);
        })->values();

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
     | Store Activities
     |----------------------------------------------------------------- */

    public function storeActivities(array $validated, bool $confirmStructureChange = false): void
    {
        DB::transaction(function () use ($validated, $confirmStructureChange) {
            $projectId = (int) $validated['project_id'];
            $fiscalYearId = (int) $validated['fiscal_year_id'];

            // Check if any plans exist for this project in ANY fiscal year
            $existingPlans = ProjectActivityPlan::whereHas(
                'definitionVersion',
                fn($q) => $q->where('project_id', $projectId)
            )->exists();

            if (!$existingPlans) {
                // Case 2: First time setup - store both definitions and plans
                $this->storeNewDefinitionsAndPlans($validated, $projectId, $fiscalYearId);
                return;
            }

            // Get submitted IDs (assuming getSubmittedIds() returns a clean array of IDs)
            $submittedCapitalIds = $this->getSubmittedIds($validated['capital'] ?? []);
            $submittedRecurrentIds = $this->getSubmittedIds($validated['recurrent'] ?? []);

            // Count how many were submitted
            $submittedCapitalCount = count($submittedCapitalIds);
            $submittedRecurrentCount = count($submittedRecurrentIds);

            // Get the initial comma-separated strings from the form
            $initialCapitalString = trim($validated['initial_capital_ids'] ?? '');
            $initialRecurrentString = trim($validated['initial_recurrent_ids'] ?? '');

            // Convert initial strings to arrays and count the number of IDs
            $initialCapitalCount = $initialCapitalString === ''
                ? 0
                : count(array_filter(explode(',', $initialCapitalString)));

            $initialRecurrentCount = $initialRecurrentString === ''
                ? 0
                : count(array_filter(explode(',', $initialRecurrentString)));

            // Now compare ONLY the counts
            $capitalCountChanged = $submittedCapitalCount !== $initialCapitalCount;
            $recurrentCountChanged = $submittedRecurrentCount !== $initialRecurrentCount;

            $structureChanged = $capitalCountChanged || $recurrentCountChanged;

            // Check if plans already exist for THIS fiscal year
            $plansExistForThisFiscalYear = ProjectActivityPlan::whereHas(
                'definitionVersion',
                fn($q) => $q->where('project_id', $projectId)
            )
                ->where('fiscal_year_id', $fiscalYearId)
                ->exists();

            if ($structureChanged) {
                // Case 3: Structure changed - requires confirmation
                if (!$confirmStructureChange) {
                    throw new StructuralChangeRequiresConfirmationException(
                        'The structure of activities has changed. This will create a new version and affect all fiscal years. Please confirm to proceed.'
                    );
                }

                // User confirmed - create new version
                $this->createNewVersionForStore($validated, $projectId, $fiscalYearId);
            } else {
                // Case 1: Same structure, different fiscal year
                if ($plansExistForThisFiscalYear) {
                    // Update existing plans
                    $this->updatePlansOnly($validated, $projectId, $fiscalYearId);
                } else {
                    // Store only plans (definitions already exist)
                    $this->storePlansOnly($validated, $projectId, $fiscalYearId);
                }
            }
        });
    }

    /* -----------------------------------------------------------------
     | Update Activities
     |----------------------------------------------------------------- */
    public function updateActivities(array $validated): void
    {
        DB::transaction(function () use ($validated) {
            $projectId = (int) $validated['project_id'];
            $fiscalYearId = (int) $validated['fiscal_year_id'];

            $currentStatus = ProjectActivityPlan::whereHas('definitionVersion', fn($q) => $q->where('project_id', $projectId))
                ->where('fiscal_year_id', $fiscalYearId)
                ->value('status');

            $allowCoreFieldsUpdate = ($currentStatus === null || $currentStatus === 'draft');

            $this->processSection($validated, 'capital', $projectId, $fiscalYearId, 1, $allowCoreFieldsUpdate);
            $this->processSection($validated, 'recurrent', $projectId, $fiscalYearId, 2, $allowCoreFieldsUpdate);

            $this->cleanupOrphanedDefinitions($projectId, $validated);
        });
    }

    /* -----------------------------------------------------------------
     | Helper: Parse initial IDs from comma-separated string
     |----------------------------------------------------------------- */
    private function parseInitialIds(string $idsString): array
    {
        if (empty($idsString)) {
            return [];
        }

        $ids = array_map('intval', array_filter(explode(',', $idsString)));
        sort($ids);
        return $ids;
    }

    /* -----------------------------------------------------------------
     | Helper: Get submitted IDs from form data
     |----------------------------------------------------------------- */
    private function getSubmittedIds(array $rows): array
    {
        $ids = array_map('intval', array_keys($rows));
        sort($ids);
        return $ids;
    }

    /* -----------------------------------------------------------------
     | Helper: Check if IDs changed (added/removed)
     |----------------------------------------------------------------- */
    private function idsChanged(array $initialIds, array $submittedIds): bool
    {
        if (count($initialIds) !== count($submittedIds)) {
            return true;
        }

        return $initialIds !== $submittedIds;
    }

    /* -----------------------------------------------------------------
     | Case 1: Store plans only (definitions exist, same structure)
     |----------------------------------------------------------------- */
    private function storePlansOnly(array $validated, int $projectId, int $fiscalYearId): void
    {
        $this->processor->processSection($validated, 'capital', $projectId, $fiscalYearId, 1);
        $this->processor->processSection($validated, 'recurrent', $projectId, $fiscalYearId, 2);
    }

    /* -----------------------------------------------------------------
     | Case 2: Store new definitions and plans (first time setup)
     |----------------------------------------------------------------- */
    private function storeNewDefinitionsAndPlans(array $validated, int $projectId, int $fiscalYearId): void
    {
        $this->processSection($validated, 'capital', $projectId, $fiscalYearId, 1, true);
        $this->processSection($validated, 'recurrent', $projectId, $fiscalYearId, 2, true);
        $this->cleanupOrphanedDefinitions($projectId, $validated);
    }

    /* -----------------------------------------------------------------
     | Case 3: Create new version (structural change confirmed)
     |----------------------------------------------------------------- */
    private function createNewVersionForStore(array $validated, int $projectId, int $fiscalYearId): void
    {
        $currentVersion = ProjectActivityDefinition::where('project_id', $projectId)
            ->max('version') ?? 0;

        $newVersion = $currentVersion + 1;

        // Mark old definitions as not current
        ProjectActivityDefinition::where('project_id', $projectId)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        // Delete ALL existing plans for this project (this fiscal year only)
        ProjectActivityPlan::whereIn('activity_definition_version_id', function ($q) use ($projectId, $fiscalYearId) {
            $q->select('id')
                ->from('project_activity_definitions')
                ->where('project_id', $projectId)
                ->where('fiscal_year_id', $fiscalYearId);
        })->delete();

        // Create new versioned definitions
        $idMap = $this->createVersionedDefinitions($validated, $projectId, $newVersion, $currentVersion);

        // Update validated data with new IDs
        $validated = $this->remapValidatedIds($validated, $idMap);

        // Store plans with new definition IDs
        $this->processor->processSection($validated, 'capital', $projectId, $fiscalYearId, 1);
        $this->processor->processSection($validated, 'recurrent', $projectId, $fiscalYearId, 2);
    }

    /* -----------------------------------------------------------------
     | Update plans only (no structural change)
     |----------------------------------------------------------------- */
    private function updatePlansOnly(array $validated, int $projectId, int $fiscalYearId): void
    {
        $this->processSection($validated, 'capital', $projectId, $fiscalYearId, 1);
        $this->processSection($validated, 'recurrent', $projectId, $fiscalYearId, 2);
        $this->cleanupOrphanedDefinitions($projectId, $validated);
    }

    /* -----------------------------------------------------------------
     | Create versioned definitions and return ID mapping
     |----------------------------------------------------------------- */
    private function createVersionedDefinitions(
        array $validated,
        int $projectId,
        int $newVersion,
        int $previousVersion
    ): array {
        $idMap = [];

        foreach (['capital' => 1, 'recurrent' => 2] as $section => $expenditureId) {
            $rows = $validated[$section] ?? [];
            if (empty($rows)) continue;

            foreach ($rows as $oldId => $row) {
                $parentId = null;
                if (!empty($row['parent_id']) && isset($idMap[$row['parent_id']])) {
                    $parentId = $idMap[$row['parent_id']];
                }

                $newDef = ProjectActivityDefinition::create([
                    'project_id'          => $projectId,
                    'expenditure_id'      => $expenditureId,
                    'program'             => $row['program'] ?? null,
                    'total_budget'        => (float) ($row['total_budget'] ?? 0),
                    'total_quantity'      => (float) ($row['total_budget_quantity'] ?? 0),
                    'parent_id'           => $parentId,
                    'sort_index'          => $row['sort_index'] ?? '',
                    'depth'               => (int) ($row['depth'] ?? 0),
                    'version'             => $newVersion,
                    'previous_version_id' => $previousVersion > 0 ? $previousVersion : null,
                    'is_current'          => true,
                    'versioned_at'        => now(),
                ]);

                $idMap[$oldId] = $newDef->id;
            }
        }

        return $idMap;
    }

    /* -----------------------------------------------------------------
     | Remap validated data with new definition IDs
     |----------------------------------------------------------------- */
    private function remapValidatedIds(array $validated, array $idMap): array
    {
        foreach (['capital', 'recurrent'] as $section) {
            $newRows = [];
            foreach ($validated[$section] ?? [] as $oldId => $row) {
                $newId = $idMap[$oldId] ?? $oldId;
                $newRows[$newId] = $row;
                // Update parent_id if it exists in the map
                if (!empty($row['parent_id']) && isset($idMap[$row['parent_id']])) {
                    $newRows[$newId]['parent_id'] = $idMap[$row['parent_id']];
                }
            }
            $validated[$section] = $newRows;
        }

        return $validated;
    }

    /* -----------------------------------------------------------------
     | Private Processing Logic
     |----------------------------------------------------------------- */

    private function processSection(
        array $validated,
        string $section,
        int $projectId,
        int $fiscalYearId,
        int $expenditureId,
        bool $allowProgramUpdate = false
    ): void {
        $rows = $validated[$section] ?? [];

        if (empty($rows)) {
            return;
        }

        $isFirstTimeSetup = !ProjectActivityPlan::whereHas(
            'definitionVersion',
            fn($q) => $q
                ->where('project_id', $projectId)
                ->where('expenditure_id', $expenditureId)
        )
            ->where('fiscal_year_id', $fiscalYearId)
            ->exists();

        foreach ($rows as $id => $row) {
            $definitionId = (int) $id;

            $updateData = [
                'sort_index'     => $row['sort_index'] ?? '',
                'depth'          => (int) ($row['depth'] ?? 0),
                'parent_id'      => empty($row['parent_id']) ? null : (int) $row['parent_id'],
            ];

            if ($isFirstTimeSetup || $allowProgramUpdate) {
                $updateData += [
                    'program'        => $row['program'] ?? null,
                    'total_budget'   => (float) ($row['total_budget'] ?? 0),
                    'total_quantity' => (float) ($row['total_budget_quantity'] ?? 0),
                ];
            }

            ProjectActivityDefinition::where('id', $definitionId)
                ->where('project_id', $projectId)
                ->update($updateData);
        }

        $this->processor->processSection($validated, $section, $projectId, $fiscalYearId, $expenditureId);
    }

    private function cleanupOrphanedDefinitions(int $projectId, array $validated): void
    {
        $submittedIds = [];

        foreach (['capital', 'recurrent'] as $section) {
            foreach (array_keys($validated[$section] ?? []) as $key) {
                if (is_numeric($key)) {
                    $submittedIds[] = (int) $key;
                }
            }
        }

        if (empty($submittedIds)) {
            return;
        }

        ProjectActivityDefinition::where('project_id', $projectId)
            ->where('is_current', true)
            ->whereNotIn('id', $submittedIds)
            ->delete();
    }
}
