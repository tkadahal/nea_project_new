<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\ProjectActivityPlan;

class ProjectActivityRowBuilder
{
    public function buildFromDefinitions($definitions, string $type, ?int $fiscalYearId): array
    {
        $allDefinitionIds = $this->collectAllDefinitionIds($definitions);

        $plans = $this->loadPlans($allDefinitionIds, $fiscalYearId);

        $rows = [];
        $this->buildRowsRecursive($definitions, $rows, $plans);

        return array_map(fn($row) => (object) $row, $rows);
    }

    private function collectAllDefinitionIds(Collection $definitions): Collection
    {
        $ids = collect();

        foreach ($definitions as $def) {
            $ids->push($def->id);
            $ids = $ids->merge($this->collectChildIds($def->children));
        }

        return $ids;
    }

    private function collectChildIds($children): Collection
    {
        $ids = collect();
        foreach ($children as $child) {
            $ids->push($child->id);
            if ($child->children->isNotEmpty()) {
                $ids = $ids->merge($this->collectChildIds($child->children));
            }
        }
        return $ids;
    }

    private function loadPlans(Collection $definitionIds, ?int $fiscalYearId): Collection
    {
        if (!$fiscalYearId || $definitionIds->isEmpty()) {
            return collect();
        }

        return ProjectActivityPlan::whereIn('activity_definition_version_id', $definitionIds)
            ->where('fiscal_year_id', $fiscalYearId)
            ->get()
            ->keyBy('activity_definition_version_id');
    }

    private function buildRowsRecursive($nodes, array &$rows, Collection $plans): void
    {
        $nodes = $nodes->sortBy('sort_index', SORT_NATURAL);

        foreach ($nodes as $node) {
            $plan = $plans->get($node->id);

            $rows[] = [
                'id' => (string) $node->id,
                'sort_index' => $node->sort_index,
                'depth' => $node->depth,
                'parent_id' => $node->parent_id ? (string) $node->parent_id : null,
                'program' => $plan?->program_override ?? $node->program ?? '',
                'total_quantity' => $node->total_quantity ? (float) $node->total_quantity : null,
                'total_budget' => $node->total_budget ? (float) $node->total_budget : null,
                'total_expense_quantity' => $plan?->completed_quantity ? (float) $plan->completed_quantity : null,
                'total_expense' => $plan?->total_expense ? (float) $plan->total_expense : null,
                'planned_budget_quantity' => $plan?->planned_quantity ? (float) $plan->planned_quantity : null,
                'planned_budget' => $plan?->planned_budget ? (float) $plan->planned_budget : null,
                'q1_quantity' => $plan?->q1_quantity ? (float) $plan->q1_quantity : null,
                'q1' => $plan?->q1_amount ? (float) $plan->q1_amount : null,
                'q2_quantity' => $plan?->q2_quantity ? (float) $plan->q2_quantity : null,
                'q2' => $plan?->q2_amount ? (float) $plan->q2_amount : null,
                'q3_quantity' => $plan?->q3_quantity ? (float) $plan->q3_quantity : null,
                'q3' => $plan?->q3_amount ? (float) $plan->q3_amount : null,
                'q4_quantity' => $plan?->q4_quantity ? (float) $plan->q4_quantity : null,
                'q4' => $plan?->q4_amount ? (float) $plan->q4_amount : null,
            ];

            if ($node->children->isNotEmpty()) {
                $this->buildRowsRecursive($node->children, $rows, $plans);
            }
        }
    }
}
