<?php

declare(strict_types=1);

namespace App\DTOs\ProjectExpense;

class ExpenseTreeNodeDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?int $parentId,
        public readonly int $sortIndex,
        public readonly array $children,
        public readonly float $plannedQuantity,
        public readonly float $plannedBudget,
        public readonly float $totalBudget,
        public readonly float $totalExpense,
        public readonly array $quarterData,
        public readonly array $subtreeData
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'parent_id' => $this->parentId,
            'sort_index' => $this->sortIndex,
            'children' => $this->children,
            'planned_quantity' => $this->plannedQuantity,
            'planned_budget' => $this->plannedBudget,
            'total_budget' => $this->totalBudget,
            'total_expense' => $this->totalExpense,
            ...$this->quarterData,
            ...$this->subtreeData,
        ];
    }
}
