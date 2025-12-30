<?php

declare(strict_types=1);

namespace App\DTOs\ProjectExpense;

class ExpenseImportResultDTO
{
    public function __construct(
        public readonly int $quarterNumber,
        public readonly int $processedCount,
        public readonly array $expenseIds = []
    ) {}
}
