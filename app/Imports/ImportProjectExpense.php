<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ImportProjectExpense implements ToCollection
{
    protected $projectId;
    protected $fiscalYearId;
    protected $quarterNumber;

    public function __construct($projectId, $fiscalYearId, $quarterNumber)
    {
        $this->projectId = $projectId;
        $this->fiscalYearId = $fiscalYearId;
        $this->quarterNumber = $quarterNumber;
    }

    public function collection(Collection $rows)
    {
        // Skip first 7 rows (metadata/headers from export)
        $dataRows = $rows->slice(7);

        Log::info('Excel import: Raw rows loaded', [
            'total_rows' => $rows->count(),
            'data_rows_count' => $dataRows->count(),
            'sample_first_data_row' => $dataRows->first()?->toArray() ?? 'No data rows'
        ]);

        return $dataRows;
    }
}
