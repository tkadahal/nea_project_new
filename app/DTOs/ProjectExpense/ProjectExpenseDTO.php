<?php

namespace App\DTO\ProjectExpense;

class ActivityExpenseDTO
{
    public function __construct(
        public int $activityId,
        public ?int $parentActivityId,
        public float $quantity,
        public float $amount,
        public ?string $description = null
    ) {}
}
