<?php

declare(strict_types=1);

namespace App\Helpers\ProjectExpense;

use Illuminate\Database\Eloquent\Collection;

class ExpenseTreeBuilder
{
    public function formatActivityTree(
        Collection $roots,
        Collection $currentPlans,
        Collection $expenses,
        int $fiscalYearId
    ): array {
        return $roots->map(function ($definition) use ($currentPlans, $expenses, $fiscalYearId) {
            $plan = $currentPlans->get($definition->id);
            if (!$plan) return null;

            $planId = $plan->id;
            $expense = $expenses->get($planId);

            return [
                'id' => $planId,
                'title' => $plan->program_override ?? $definition->program,
                'parent_id' => $definition->parent_id ? ($currentPlans->firstWhere('activity_definition_version_id', $definition->parent_id)?->id) : null,
                'sort_index' => $definition->sort_index,
                'children' => $this->formatChildren($definition->children, $currentPlans, $expenses, $fiscalYearId),
                'planned_quantity' => (float) ($plan->planned_quantity ?? 0),
                'planned_budget' => (float) ($plan->planned_budget ?? 0),
                'total_budget' => (float) ($plan->planned_budget ?? 0),
                'total_expense' => $expense?->grand_total ?? 0,
                'q1_quantity' => (float) ($plan->q1_quantity ?? 0),
                'q1_amount' => (float) ($plan->q1_amount ?? 0),
                'q2_quantity' => (float) ($plan->q2_quantity ?? 0),
                'q2_amount' => (float) ($plan->q2_amount ?? 0),
                'q3_quantity' => (float) ($plan->q3_quantity ?? 0),
                'q3_amount' => (float) ($plan->q3_amount ?? 0),
                'q4_quantity' => (float) ($plan->q4_quantity ?? 0),
                'q4_amount' => (float) ($plan->q4_amount ?? 0),
                'subtree_total_budget' => $definition->subtreePlans($fiscalYearId)->sum('planned_budget'),
                'subtree_q1' => $definition->subtreePlans($fiscalYearId)->sum('q1_amount'),
                'subtree_q2' => $definition->subtreePlans($fiscalYearId)->sum('q2_amount'),
                'subtree_q3' => $definition->subtreePlans($fiscalYearId)->sum('q3_amount'),
                'subtree_q4' => $definition->subtreePlans($fiscalYearId)->sum('q4_amount'),
            ];
        })->filter()->values()->toArray();
    }

    public function formatChildren(
        Collection $children,
        Collection $currentPlans,
        Collection $expenses,
        int $fiscalYearId
    ): array {
        return $children->map(function ($child) use ($currentPlans, $expenses, $fiscalYearId) {
            $plan = $currentPlans->get($child->id);
            if (!$plan) return null;

            $planId = $plan->id;
            $expense = $expenses->get($planId);

            return [
                'id' => $planId,
                'title' => $plan->program_override ?? $child->program,
                'parent_id' => $child->parent_id ? ($currentPlans->firstWhere('activity_definition_version_id', $child->parent_id)?->id) : null,
                'sort_index' => $child->sort_index,
                'children' => $this->formatChildren($child->children, $currentPlans, $expenses, $fiscalYearId),
                'planned_quantity' => (float) ($plan->planned_quantity ?? 0),
                'planned_budget' => (float) ($plan->planned_budget ?? 0),
                'total_budget' => (float) ($plan->planned_budget ?? 0),
                'total_expense' => $expense?->grand_total ?? 0,
                'q1_quantity' => (float) ($plan->q1_quantity ?? 0),
                'q1_amount' => (float) ($plan->q1_amount ?? 0),
                'q2_quantity' => (float) ($plan->q2_quantity ?? 0),
                'q2_amount' => (float) ($plan->q2_amount ?? 0),
                'q3_quantity' => (float) ($plan->q3_quantity ?? 0),
                'q3_amount' => (float) ($plan->q3_amount ?? 0),
                'q4_quantity' => (float) ($plan->q4_quantity ?? 0),
                'q4_amount' => (float) ($plan->q4_amount ?? 0),
                'subtree_total_budget' => $child->subtreePlans($fiscalYearId)->sum('planned_budget'),
                'subtree_q1' => $child->subtreePlans($fiscalYearId)->sum('q1_amount'),
                'subtree_q2' => $child->subtreePlans($fiscalYearId)->sum('q2_amount'),
                'subtree_q3' => $child->subtreePlans($fiscalYearId)->sum('q3_amount'),
                'subtree_q4' => $child->subtreePlans($fiscalYearId)->sum('q4_amount'),
            ];
        })->filter()->values()->toArray();
    }

    public function calculateSubtreeAmounts(
        Collection $roots,
        array $planAmounts,
        Collection $groupedActivities,
        Collection $currentPlans
    ): array {
        $subtreeAmountTotals = [];

        $computeSubtreeAmounts = function ($defId) use (
            &$subtreeAmountTotals,
            $planAmounts,
            $groupedActivities,
            $currentPlans,
            &$computeSubtreeAmounts
        ) {
            if (isset($subtreeAmountTotals[$defId])) {
                return $subtreeAmountTotals[$defId];
            }

            $totals = ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
            $plan = $currentPlans->get($defId);
            $own = $planAmounts[$plan?->id ?? 0] ?? $totals;

            foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                $totals[$q] = $own["{$q}_amt"] ?? 0;
            }

            if (isset($groupedActivities[$defId])) {
                foreach ($groupedActivities[$defId] as $child) {
                    $childTotals = $computeSubtreeAmounts($child->id);
                    foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                        $totals[$q] += $childTotals[$q];
                    }
                }
            }

            return $subtreeAmountTotals[$defId] = $totals;
        };

        foreach ($roots as $root) {
            $computeSubtreeAmounts($root->id);
        }

        return $subtreeAmountTotals;
    }

    public function calculateSubtreeQuantities(
        Collection $roots,
        array $planAmounts,
        Collection $groupedActivities,
        Collection $currentPlans
    ): array {
        $subtreeQuantityTotals = [];

        $computeSubtreeQuantities = function ($defId) use (
            &$subtreeQuantityTotals,
            $planAmounts,
            $groupedActivities,
            $currentPlans,
            &$computeSubtreeQuantities
        ) {
            if (isset($subtreeQuantityTotals[$defId])) {
                return $subtreeQuantityTotals[$defId];
            }

            $totals = ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
            $plan = $currentPlans->get($defId);
            $own = $planAmounts[$plan?->id ?? 0] ?? $totals;

            foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                $totals[$q] = $own["{$q}_qty"] ?? 0;
            }

            if (isset($groupedActivities[$defId])) {
                foreach ($groupedActivities[$defId] as $child) {
                    $childTotals = $computeSubtreeQuantities($child->id);
                    foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                        $totals[$q] += $childTotals[$q];
                    }
                }
            }

            return $subtreeQuantityTotals[$defId] = $totals;
        };

        foreach ($roots as $root) {
            $computeSubtreeQuantities($root->id);
        }

        return $subtreeQuantityTotals;
    }
}
