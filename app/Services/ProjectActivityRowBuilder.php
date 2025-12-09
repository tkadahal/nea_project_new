<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\ProjectActivityPlan;

class ProjectActivityRowBuilder
{
    public function buildFromDefinitions($definitions, string $type, ?int $fiscalYearId): array
    {
        $rows = [];
        $index = 1;

        $allDefinitionIds = $this->collectAllDefinitionIds($definitions);
        $plans = $this->loadPlans($allDefinitionIds, $fiscalYearId);

        $this->buildRowsRecursive($definitions, $rows, $index, 0, null, $plans);
        $this->setRowNumbers($rows);

        return $rows;
    }

    public function buildFromPlans($plans, string $section, int $fiscalYearId): array
    {
        $rows = [];
        $index = 1;

        foreach ($plans as $plan) {
            $def = $plan->activityDefinition;
            $rows[] = $this->buildRowFromPlan($plan, $index++, 0, null);
            $this->addChildRowsFromPlans($def->children, $rows, end($rows)['index'], 1, $fiscalYearId);
        }

        $this->setRowNumbers($rows);
        return $rows;
    }

    private function collectAllDefinitionIds($definitions): Collection
    {
        $ids = collect();

        foreach ($definitions as $def) {
            $ids->push($def->id);
            if ($def->children) {
                foreach ($def->children as $child) {
                    $ids->push($child->id);
                    if ($child->children) {
                        foreach ($child->children as $grandchild) {
                            $ids->push($grandchild->id);
                        }
                    }
                }
            }
        }

        return $ids;
    }

    private function loadPlans(Collection $definitionIds, ?int $fiscalYearId): Collection
    {
        if (!$fiscalYearId || $definitionIds->isEmpty()) {
            return collect();
        }

        return ProjectActivityPlan::whereIn('activity_definition_id', $definitionIds->unique())
            ->where('fiscal_year_id', $fiscalYearId)
            ->get()
            ->keyBy('activity_definition_id');
    }

    private function buildRowsRecursive($nodes, array &$rows, int &$index, int $depth, ?int $parentIndex, $plans): void
    {
        foreach ($nodes as $node) {
            $plan = $plans->get($node->id);

            $rows[] = [
                'depth' => $depth,
                'index' => $index,
                'parent_id' => $parentIndex, // FIXED: Changed from parent_index to parent_id
                'program' => $plan?->program_override ?? $node->program,
                // CHANGED: Fixed totals from DEFINITION (not plan)
                'total_budget_quantity' => $node->total_quantity ?? '',
                'total_budget' => $node->total_budget ?? '',
                'total_expense_quantity' => $plan?->completed_quantity ?? '',
                'total_expense' => $plan?->total_expense ?? '',
                'planned_budget_quantity' => $plan?->planned_quantity ?? '',
                'planned_budget' => $plan?->planned_budget ?? '',
                'q1_quantity' => $plan?->q1_quantity ?? '',
                'q1' => $plan?->q1_amount ?? '',
                'q2_quantity' => $plan?->q2_quantity ?? '',
                'q2' => $plan?->q2_amount ?? '',
                'q3_quantity' => $plan?->q3_quantity ?? '',
                'q3' => $plan?->q3_amount ?? '',
                'q4_quantity' => $plan?->q4_quantity ?? '',
                'q4' => $plan?->q4_amount ?? '',
            ];

            if ($node->children && $node->children->isNotEmpty()) {
                $childParentIndex = $index;
                $index++;
                $this->buildRowsRecursive($node->children, $rows, $index, $depth + 1, $childParentIndex, $plans);
            } else {
                $index++;
            }
        }
    }

    private function buildRowFromPlan($plan, int $index, int $depth, ?int $parentIndex): array
    {
        $def = $plan->activityDefinition;

        return [
            'index' => $index,
            'depth' => $depth,
            'parent_id' => $parentIndex, // FIXED: Changed from parent_index to parent_id
            'number' => (string) $index,
            'program' => $def->program,  // Or use $plan->effective_program if override
            // CHANGED: Fixed totals from DEFINITION (not plan)
            'total_budget_quantity' => $def->total_quantity ?? '',
            'total_budget' => $def->total_budget ?? '',
            'total_expense_quantity' => $plan->completed_quantity ?? '',
            'total_expense' => $plan->total_expense ?? '',
            'planned_budget_quantity' => $plan->planned_quantity ?? '',
            'planned_budget' => $plan->planned_budget ?? '',
            'q1_quantity' => $plan->q1_quantity ?? '',
            'q1' => $plan->q1_amount ?? '',
            'q2_quantity' => $plan->q2_quantity ?? '',
            'q2' => $plan->q2_amount ?? '',
            'q3_quantity' => $plan->q3_quantity ?? '',
            'q3' => $plan->q3_amount ?? '',
            'q4_quantity' => $plan->q4_quantity ?? '',
            'q4' => $plan->q4_amount ?? '',
        ];
    }

    private function addChildRowsFromPlans($children, &$rows, $parentIndex, $depth, $fiscalYearId): void
    {
        if ($children->isEmpty()) return;

        foreach ($children as $child) {
            $plan = $child->plans->where('fiscal_year_id', $fiscalYearId)->first();

            $rows[] = [
                'index' => count($rows) + 1,
                'depth' => $depth,
                'parent_id' => $parentIndex, // FIXED: Changed from parent_index to parent_id
                'number' => '',
                'program' => $child->program,  // Or $plan?->program_override if exists
                // CHANGED: Fixed totals from DEFINITION (not plan)
                'total_budget_quantity' => $child->total_quantity ?? '',
                'total_budget' => $child->total_budget ?? '',
                'total_expense_quantity' => $plan?->completed_quantity ?? '',
                'total_expense' => $plan?->total_expense ?? '',
                'planned_budget_quantity' => $plan?->planned_quantity ?? '',
                'planned_budget' => $plan?->planned_budget ?? '',
                'q1_quantity' => $plan?->q1_quantity ?? '',
                'q1' => $plan?->q1_amount ?? '',
                'q2_quantity' => $plan?->q2_quantity ?? '',
                'q2' => $plan?->q2_amount ?? '',
                'q3_quantity' => $plan?->q3_quantity ?? '',
                'q3' => $plan?->q3_amount ?? '',
                'q4_quantity' => $plan?->q4_quantity ?? '',
                'q4' => $plan?->q4_amount ?? '',
            ];

            if ($depth < 2) {
                $this->addChildRowsFromPlans($child->children, $rows, end($rows)['index'], $depth + 1, $fiscalYearId);
            }
        }
    }

    private function setRowNumbers(array &$rows): void
    {
        $topLevelCount = 0;
        $levelOneCounts = [];
        $levelTwoCounts = [];

        foreach ($rows as &$row) {
            $depth = $row['depth'];
            $parentId = $row['parent_id']; // FIXED: Changed from parent_index to parent_id

            if ($depth === 0) {
                $topLevelCount++;
                $row['number'] = (string) $topLevelCount;
                $levelOneCounts[$topLevelCount] = 0;
            } elseif ($depth === 1) {
                $parentNumber = $this->findParentNumber($rows, $parentId); // FIXED: Changed variable name
                $levelOneCounts[$parentNumber] = ($levelOneCounts[$parentNumber] ?? 0) + 1;
                $row['number'] = $parentNumber . '.' . $levelOneCounts[$parentNumber];
                $levelTwoCounts[$row['number']] = 0;
            } elseif ($depth === 2) {
                $parentNumber = $this->findParentNumber($rows, $parentId); // FIXED: Changed variable name
                $levelTwoCounts[$parentNumber] = ($levelTwoCounts[$parentNumber] ?? 0) + 1;
                $row['number'] = $parentNumber . '.' . $levelTwoCounts[$parentNumber];
            }
        }
    }

    private function findParentNumber(array $rows, ?int $parentId): string // FIXED: Changed parameter name
    {
        if ($parentId === null) {
            return '';
        }

        foreach ($rows as $row) {
            if ($row['index'] === $parentId) {
                return $row['number'];
            }
        }
        return '';
    }
}
