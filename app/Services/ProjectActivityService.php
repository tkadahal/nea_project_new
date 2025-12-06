<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectActivityPlan;
use Illuminate\Support\Facades\DB;
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

        return [
            $this->rowBuilder->buildFromDefinitions($capitalDefinitions, 'capital', $fiscalYearId),
            $this->rowBuilder->buildFromDefinitions($recurrentDefinitions, 'recurrent', $fiscalYearId),
        ];
    }

    public function buildRowsForEdit(Project $project, int $fiscalYearId): array
    {
        // MODIFIED: getDefinitionsWithPlans loads total_budget/total_quantity from definitions; planned from plans
        $capitalDefinitions = $this->getDefinitionsWithPlans($project, 1, $fiscalYearId);
        $recurrentDefinitions = $this->getDefinitionsWithPlans($project, 2, $fiscalYearId);

        return [
            $this->rowBuilder->buildFromDefinitions($capitalDefinitions, 'capital', $fiscalYearId),
            $this->rowBuilder->buildFromDefinitions($recurrentDefinitions, 'recurrent', $fiscalYearId),
        ];
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
            return [
                $this->rowBuilder->buildFromPlans($capitalPlans, 'capital', $fiscalYearId),
                $this->rowBuilder->buildFromPlans($recurrentPlans, 'recurrent', $fiscalYearId),
            ];
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
        return $project->activityDefinitions()
            ->with(['children' => fn($query) => $query->with('children')])
            ->whereNull('parent_id')
            ->where('expenditure_id', $expenditureId)
            ->where('status', 'active')
            ->get();
    }

    private function getDefinitionsWithPlans(Project $project, int $expenditureId, int $fiscalYearId)
    {
        return $project->activityDefinitions()
            ->with([
                'children' => fn($query) => $query->with([
                    'children.plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId),
                    'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId)
                ]),
                'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId)
            ])
            ->whereNull('parent_id')
            ->where('expenditure_id', $expenditureId)
            ->where('status', 'active')
            ->get();
    }

    private function getPlansForExpenditureType(int $projectId, int $fiscalYearId, int $expenditureId)
    {
        // MODIFIED: Plans load without total_budget/total_quantity; defs provide them via relations
        return ProjectActivityPlan::whereHas('activityDefinition', function ($q) use ($projectId, $expenditureId) {
            $q->where('project_id', $projectId)
                ->where('expenditure_id', $expenditureId)
                ->where('status', 'active');
        })
            ->where('fiscal_year_id', $fiscalYearId)
            ->with(['activityDefinition' => fn($q) => $q->with('children.plans')])
            ->get();
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
