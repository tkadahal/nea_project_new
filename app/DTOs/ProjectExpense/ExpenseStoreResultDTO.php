<?php

declare(strict_types=1);

namespace App\DTOs\ProjectExpense;

class ExpenseStoreResultDTO
{
    public function __construct(
        public readonly int $projectId,
        public readonly int $fiscalYearId,
        public readonly int $quarterNumber,
        public readonly array $expenseIds = []
    ) {}
}
