<?php

declare(strict_types=1);

namespace App\Exports\Reports\Consolidated;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;
use App\Exports\Reports\Consolidated\BudgetSheets\BudgetSummaryReportExport;
use App\Exports\Reports\Consolidated\BudgetSheets\BudgetReportSummaryExport;
use App\Exports\Reports\Consolidated\BudgetSheets\BudgetProjectWiseReport;
use App\Exports\Reports\Consolidated\BudgetSheets\BudgetReportExport;

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
            new BudgetSummaryReportExport($this->projects, $this->fiscalYear), // Sheet 1: Summary
            new BudgetReportSummaryExport($this->projects, $this->fiscalYear), // Sheet 1: Summary
            new BudgetProjectWiseReport($this->projects, $this->fiscalYear),   // Sheet 2: Project Wise
            new BudgetReportExport($this->projects, $this->fiscalYear),        // Sheet 3: Detailed
        ];
    }
}
