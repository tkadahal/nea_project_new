<?php

namespace App\Exports\Reports;

use App\Exports\Sheets\BudgetHeadingSummarySheet;
use App\Exports\Sheets\DirectorateGroupedSheet;
use App\Exports\Sheets\QuarterlyAllocationSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PreBudgetMultiSheetExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new DirectorateGroupedSheet,
            new BudgetHeadingSummarySheet,
            new QuarterlyAllocationSheet,
        ];
    }
}
