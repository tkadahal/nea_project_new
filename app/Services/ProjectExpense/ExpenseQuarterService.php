<?php

declare(strict_types=1);

namespace App\Services\ProjectExpense;

use App\Repositories\ProjectExpense\ProjectExpenseRepository;

class ExpenseQuarterService
{
    public function __construct(
        private readonly ProjectExpenseRepository $repository
    ) {}

    public function getNextUnfilledQuarter(int $projectId, int $fiscalYearId): string
    {
        $planIds = $this->repository->getAllHistoricalPlanIds($projectId, $fiscalYearId);

        if ($planIds->isEmpty()) {
            return 'q1';
        }

        $completed = [];
        $expenses = $this->repository->getExpensesByPlanIds($planIds);

        foreach ($expenses as $expense) {
            foreach ($expense->quarters as $quarter) {
                if ($quarter->quantity > 0 || $quarter->amount > 0) {
                    $completed[$quarter->quarter] = true;
                }
            }
        }

        for ($i = 1; $i <= 4; $i++) {
            if (!isset($completed[$i])) {
                return "q{$i}";
            }
        }

        return 'q4';
    }

    public function getQuarterCompletionStatus(int $projectId, int $fiscalYearId): array
    {
        $status = ['q1' => false, 'q2' => false, 'q3' => false, 'q4' => false];

        $planIds = $this->repository->getAllHistoricalPlanIds($projectId, $fiscalYearId);

        if ($planIds->isEmpty()) {
            return $status;
        }

        $expenses = $this->repository->getExpensesByPlanIds($planIds);

        foreach ($expenses as $expense) {
            foreach ($expense->quarters as $quarter) {
                if ($quarter->amount > 0 || $quarter->quantity > 0) {
                    $status["q{$quarter->quarter}"] = true;
                }
            }
        }

        return $status;
    }

    public function extractQuarterNumber(string $quarter): int
    {
        return (int) substr($quarter, 1);
    }

    public function validateQuarter(string $quarter): bool
    {
        return in_array($quarter, ['q1', 'q2', 'q3', 'q4']);
    }
}
