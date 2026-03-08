<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\DirectorateGroupedSheet;
use App\Exports\Sheets\BudgetHeadingSummarySheet;
use App\Exports\Sheets\QuarterlyAllocationSheet;

class PreBudgetMultiSheetExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new DirectorateGroupedSheet(),
            new BudgetHeadingSummarySheet(),
            new QuarterlyAllocationSheet(),
        ];
    }
}
