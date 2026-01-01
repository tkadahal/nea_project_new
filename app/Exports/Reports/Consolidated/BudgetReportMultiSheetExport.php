<?php

declare(strict_types=1);

namespace App\Exports\Reports\Consolidated;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;

class BudgetReportMultiSheetExport implements WithMultipleSheets
{
    protected Collection $projects;
    protected string $fiscalYear;

    public function __construct(Collection $projects, string $fiscalYear = '२०८१/८२')
    {
        $this->projects = $projects;
        $this->fiscalYear = $fiscalYear;
    }

    public function sheets(): array
    {
        return [
            new BudgetReportExport($this->projects, $this->fiscalYear),         // Sheet 1: Detailed
            new BudgetReportSummaryExport($this->projects, $this->fiscalYear),  // Sheet 2: Summary
        ];
    }
}
