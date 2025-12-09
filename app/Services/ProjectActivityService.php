<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectActivityPlan;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectActivityDefinition;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;

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

        // Fallback to definitions if no plans (create mode even with FY selected)
        return $this->buildRowsForProject($project, $fiscalYearId);
    }

    public function storeActivities(array $validated): void
    {
        $this->validateBudget($validated);

        DB::transaction(function () use ($validated) {
            // MODIFIED: processSection now saves total_budget/total_quantity to definitions; planned_budget etc. to plans
            $this->processor->processSection(
                $validated,
                'capital',
                $validated['project_id'],
                $validated['fiscal_year_id'],
                1
            );

            $this->processor->processSection(
                $validated,
                'recurrent',
                $validated['project_id'],
                $validated['fiscal_year_id'],
                2
            );
        });
    }

    public function updateActivities(array $validated, int $projectId, int $fiscalYearId): void
    {
        DB::transaction(function () use ($validated, $projectId, $fiscalYearId) {
            // MODIFIED: processSection updates total_budget/total_quantity in definitions if provided; planned in plans
            $this->processor->processSection($validated, 'capital', $projectId, $fiscalYearId, 1);
            $this->processor->processSection($validated, 'recurrent', $projectId, $fiscalYearId, 2);
        });
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
            ->where('status', 'active')
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
            ->where('status', 'active')
            ->get();
    }

    private function getPlansForExpenditureType(int $projectId, int $fiscalYearId, int $expenditureId)
    {
        // MODIFIED: Plans load without total_budget/total_quantity; defs provide them via relations
        // FIXED: Ensure full hierarchy via recursive with on definitions
        return ProjectActivityPlan::whereHas('activityDefinition', function ($q) use ($projectId, $expenditureId) {
            $q->where('project_id', $projectId)
                ->where('expenditure_id', $expenditureId)
                ->where('status', 'active');
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
            if (isset($row['id'])) {
                $row['id'] = (string) $row['id'];
            }
            if (isset($row['parent_id'])) {
                $row['parent_id'] = $row['parent_id'] ? (string) $row['parent_id'] : null;
            }
            // Recurse for children if present (full hierarchy)
            if (isset($row['children']) && is_array($row['children'])) {
                $row['children'] = $this->castIdsToString($row['children']);
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
