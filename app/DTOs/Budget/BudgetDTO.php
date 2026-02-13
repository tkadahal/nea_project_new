<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

class BudgetDTO
{
    public function __construct(
        public readonly int $fiscalYearId,
        public readonly int $projectId,
        public readonly float $internalBudget = 0,
        public readonly float $governmentShare = 0,
        public readonly float $governmentLoan = 0,
        public readonly float $foreignLoanBudget = 0,
        public readonly ?string $foreignLoanSource = null,
        public readonly float $foreignSubsidyBudget = 0,
        public readonly ?string $foreignSubsidySource = null,
        public readonly float $totalBudget = 0,
        public readonly ?string $decisionDate = null,
        public readonly ?string $remarks = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            fiscalYearId: (int) $data['fiscal_year_id'],
            projectId: (int) $data['project_id'],
            internalBudget: (float) ($data['internal_budget'] ?? 0),
            governmentShare: (float) ($data['government_share'] ?? 0),
            governmentLoan: (float) ($data['government_loan'] ?? 0),
            foreignLoanBudget: (float) ($data['foreign_loan_budget'] ?? 0),
            foreignLoanSource: $data['foreign_loan_source'] ?? null,
            foreignSubsidyBudget: (float) ($data['foreign_subsidy_budget'] ?? 0),
            foreignSubsidySource: $data['foreign_subsidy_source'] ?? null,
            totalBudget: (float) ($data['total_budget'] ?? 0),
            decisionDate: $data['decision_date'] ?? null,
            remarks: $data['remarks'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'fiscal_year_id' => $this->fiscalYearId,
            'project_id' => $this->projectId,
            'internal_budget' => $this->internalBudget,
            'government_share' => $this->governmentShare,
            'government_loan' => $this->governmentLoan,
            'foreign_loan_budget' => $this->foreignLoanBudget,
            'foreign_loan_source' => $this->foreignLoanSource,
            'foreign_subsidy_budget' => $this->foreignSubsidyBudget,
            'foreign_subsidy_source' => $this->foreignSubsidySource,
            'total_budget' => $this->totalBudget,
            'decision_date' => $this->decisionDate,
            'remarks' => $this->remarks,
        ];
    }

    public function hasNonZeroBudget(): bool
    {
        return ($this->internalBudget +
            $this->governmentShare +
            $this->governmentLoan +
            $this->foreignLoanBudget +
            $this->foreignSubsidyBudget) > 0;
    }
}
