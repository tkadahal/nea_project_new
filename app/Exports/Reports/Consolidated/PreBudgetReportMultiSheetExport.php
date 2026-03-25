<?php

declare(strict_types=1);

namespace App\Exports\Reports\Consolidated;

use App\Exports\Reports\Consolidated\PreBudgetSheets\PreBudgetProjectWiseReport;
use App\Exports\Reports\Consolidated\PreBudgetSheets\PreBudgetReportExport;
use App\Exports\Reports\Consolidated\PreBudgetSheets\PreBudgetReportSummaryExport;
use App\Exports\Reports\Consolidated\PreBudgetSheets\PreBudgetSummaryReportExport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PreBudgetReportMultiSheetExport implements WithMultipleSheets
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
            new PreBudgetSummaryReportExport($this->projects, $this->fiscalYear), // Sheet 1: Summary
            new PreBudgetReportSummaryExport($this->projects, $this->fiscalYear), // Sheet 1: Summary
            new PreBudgetProjectWiseReport($this->projects, $this->fiscalYear),   // Sheet 2: Project Wise
            new PreBudgetReportExport($this->projects, $this->fiscalYear),        // Sheet 3: Detailed
        ];
    }
}
