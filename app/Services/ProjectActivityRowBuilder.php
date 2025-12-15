<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\ProjectActivityPlan;
use App\Models\ProjectActivityDefinition;

class ProjectActivityRowBuilder
{
    public function buildFromDefinitions($definitions, string $type, ?int $fiscalYearId): array
    {
        $rows = [];

        $allDefinitionIds = $this->collectAllDefinitionIds($definitions);
        $plans = $this->loadPlans($allDefinitionIds, $fiscalYearId);

        $this->buildRowsRecursive($definitions, $rows, $plans);
        $objectRows = array_map(function ($row) {
            return (object) $row;
        }, $rows);

        return $objectRows;
    }

    public function buildFromPlans($plans, string $section, int $fiscalYearId): array
    {
        $rows = [];

        foreach ($plans as $plan) {
            $def = $plan->activityDefinition;
            $rows[] = $this->buildRowFromPlan($plan);
            $this->addChildRowsFromPlans($def->children, $rows, $fiscalYearId);
        }

        $objectRows = array_map(function ($row) {
            return (object) $row;
        }, $rows);

        return $objectRows;
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

    private function buildRowsRecursive($nodes, array &$rows, $plans): void
    {
        foreach ($nodes as $node) {
            $plan = $plans->get($node->id);

            $rows[] = [
                'id' => $node->id,
                'sort_index' => $node->sort_index,
                'depth' => $node->depth,
                'parent_id' => $node->parent_id,
                'program' => $plan?->program_override ?? $node->program ?? '',
                'total_budget_quantity' => $node->total_quantity !== null ? (float) $node->total_quantity : null,
                'total_budget' => $node->total_budget !== null ? (float) $node->total_budget : null,
                'total_expense_quantity' => $plan?->completed_quantity !== null ? (float) $plan->completed_quantity : null,
                'total_expense' => $plan?->total_expense !== null ? (float) $plan->total_expense : null,
                'planned_budget_quantity' => $plan?->planned_quantity !== null ? (float) $plan->planned_quantity : null,
                'planned_budget' => $plan?->planned_budget !== null ? (float) $plan->planned_budget : null,
                'q1_quantity' => $plan?->q1_quantity !== null ? (float) $plan->q1_quantity : null,
                'q1' => $plan?->q1_amount !== null ? (float) $plan->q1_amount : null,
                'q2_quantity' => $plan?->q2_quantity !== null ? (float) $plan->q2_quantity : null,
                'q2' => $plan?->q2_amount !== null ? (float) $plan->q2_amount : null,
                'q3_quantity' => $plan?->q3_quantity !== null ? (float) $plan->q3_quantity : null,
                'q3' => $plan?->q3_amount !== null ? (float) $plan->q3_amount : null,
                'q4_quantity' => $plan?->q4_quantity !== null ? (float) $plan->q4_quantity : null,
                'q4' => $plan?->q4_amount !== null ? (float) $plan->q4_amount : null,
            ];

            if ($node->children && $node->children->isNotEmpty()) {
                $this->buildRowsRecursive($node->children, $rows, $plans);
            }
        }
    }

    private function buildRowFromPlan($plan): array
    {
        $def = $plan->activityDefinition;

        return [
            'id' => $def->id,
            'sort_index' => $def->sort_index,
            'depth' => $def->depth,
            'parent_id' => $def->parent_id,
            'program' => $plan->effective_program ?? $def->program ?? '',
            'total_budget_quantity' => $def->total_quantity !== null ? (float) $def->total_quantity : null,
            'total_budget' => $def->total_budget !== null ? (float) $def->total_budget : null,
            'total_expense_quantity' => $plan->completed_quantity !== null ? (float) $plan->completed_quantity : null,
            'total_expense' => $plan->total_expense !== null ? (float) $plan->total_expense : null,
            'planned_budget_quantity' => $plan->planned_quantity !== null ? (float) $plan->planned_quantity : null,
            'planned_budget' => $plan->planned_budget !== null ? (float) $plan->planned_budget : null,
            'q1_quantity' => $plan->q1_quantity !== null ? (float) $plan->q1_quantity : null,
            'q1' => $plan->q1_amount !== null ? (float) $plan->q1_amount : null,
            'q2_quantity' => $plan->q2_quantity !== null ? (float) $plan->q2_quantity : null,
            'q2' => $plan->q2_amount !== null ? (float) $plan->q2_amount : null,
            'q3_quantity' => $plan->q3_quantity !== null ? (float) $plan->q3_quantity : null,
            'q3' => $plan->q3_amount !== null ? (float) $plan->q3_amount : null,
            'q4_quantity' => $plan->q4_quantity !== null ? (float) $plan->q4_quantity : null,
            'q4' => $plan->q4_amount !== null ? (float) $plan->q4_amount : null,
        ];
    }

    private function addChildRowsFromPlans($children, &$rows, $fiscalYearId): void
    {
        if ($children->isEmpty()) return;

        foreach ($children as $child) {
            $plan = $child->plans->where('fiscal_year_id', $fiscalYearId)->first();

            $rows[] = [
                'id' => $child->id,
                'sort_index' => $child->sort_index,
                'depth' => $child->depth,
                'parent_id' => $child->parent_id,
                'program' => $plan?->program_override ?? $child->program ?? '',
                'total_budget_quantity' => $child->total_quantity !== null ? (float) $child->total_quantity : null,
                'total_budget' => $child->total_budget !== null ? (float) $child->total_budget : null,
                'total_expense_quantity' => $plan?->completed_quantity !== null ? (float) $plan?->completed_quantity : null,
                'total_expense' => $plan?->total_expense !== null ? (float) $plan?->total_expense : null,
                'planned_budget_quantity' => $plan?->planned_quantity !== null ? (float) $plan?->planned_quantity : null,
                'planned_budget' => $plan?->planned_budget !== null ? (float) $plan?->planned_budget : null,
                'q1_quantity' => $plan?->q1_quantity !== null ? (float) $plan?->q1_quantity : null,
                'q1' => $plan?->q1_amount !== null ? (float) $plan?->q1_amount : null,
                'q2_quantity' => $plan?->q2_quantity !== null ? (float) $plan?->q2_quantity : null,
                'q2' => $plan?->q2_amount !== null ? (float) $plan?->q2_amount : null,
                'q3_quantity' => $plan?->q3_quantity !== null ? (float) $plan?->q3_quantity : null,
                'q3' => $plan?->q3_amount !== null ? (float) $plan?->q3_amount : null,
                'q4_quantity' => $plan?->q4_quantity !== null ? (float) $plan?->q4_quantity : null,
                'q4' => $plan?->q4_amount !== null ? (float) $plan?->q4_amount : null,
            ];

            if ($child->depth < 2) {
                $this->addChildRowsFromPlans($child->children, $rows, $fiscalYearId);
            }
        }
    }
}
