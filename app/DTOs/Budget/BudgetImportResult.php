<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

class BudgetImportResult
{
    public function __construct(
        public readonly int $created = 0,
        public readonly int $updated = 0,
        public readonly int $skipped = 0,
        public readonly array $errors = [],
        public readonly ?string $fiscalYearTitle = null,
    ) {}

    public function getMessage(): string
    {
        $message = '';

        if ($this->fiscalYearTitle) {
            $message .= "Import completed for fiscal year '{$this->fiscalYearTitle}'. ";
        }

        if ($this->created > 0) {
            $message .= "Created budgets for {$this->created} project(s). ";
        }

        if ($this->updated > 0) {
            $message .= "Updated budgets for {$this->updated} project(s). ";
        }

        if ($this->skipped > 0) {
            $message .= "Skipped {$this->skipped} project(s) with zero budget. ";
        }

        if (empty($message)) {
            $message = 'No budgets were created or updated.';
        }

        return trim($message);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
