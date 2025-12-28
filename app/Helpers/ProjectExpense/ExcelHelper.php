<?php

declare(strict_types=1);

namespace App\Helpers\ProjectExpense;

use App\DTO\ProjectExpense\ActivityExpenseDTO;
use App\Models\ProjectActivityPlan;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImportHelper
{
    /**
     * Extract quarter number from uploaded Excel file (based on header in A3)
     */
    public function extractQuarter(UploadedFile $file): ?int
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $header = trim((string) $sheet->getCell('A3')->getValue());

            $map = [
                'पहिलो' => 1,
                'दोस्रो' => 2,
                'तेस्रो' => 3,
                'चौथो' => 4,
            ];

            foreach ($map as $nepaliWord => $quarterNum) {
                if (str_contains($header, $nepaliWord) && str_contains($header, 'त्रैमास')) {
                    return $quarterNum;
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse Excel rows into ActivityExpenseDTO collection
     */
    public function parseRowsToActivities(array $rows, int $projectId, int $fiscalYearId): array
    {
        $activities = [];

        for ($i = 0; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Expected columns:
            // [0] => Serial (e.g., 1.1.2)
            // [1] => Activity title
            // [6] => Quantity
            // [7] => Amount
            // [8] => Depth/level
            // [9] => Plan ID (hidden, filled by template)

            $depth = $row[8] ?? 0;
            $title = trim($row[1] ?? '');
            if ($depth < 0 || empty($title) || str_contains(strtolower($title), 'जम्मा')) {
                continue;
            }

            $planId = $row[9] ?? null;

            // Fallback: try to find plan by title if ID not present
            if (!$planId) {
                $planId = ProjectActivityPlan::where('fiscal_year_id', $fiscalYearId)
                    ->whereHas('definitionVersion', function ($q) use ($projectId, $title) {
                        $q->where('project_id', $projectId)
                            ->whereRaw('LOWER(program) LIKE ?', ['%' . strtolower($title) . '%']);
                    })
                    ->value('id');
            }

            if (!$planId) {
                continue; // Skip if no matching plan
            }

            $activities[] = new ActivityExpenseDTO(
                activityId: (int) $planId,
                parentActivityId: $this->deriveParentIdFromRow($i, $rows),
                quantity: (float) ($row[6] ?? 0),
                amount: (float) ($row[7] ?? 0),
                description: null
            );
        }

        return $activities;
    }

    /**
     * Derive parent plan ID based on serial number or depth
     */
    private function deriveParentIdFromRow(int $rowIndex, array $rows): ?int
    {
        $currentRow = $rows[$rowIndex];
        $currentDepth = $currentRow[8] ?? 0;
        $currentSerial = trim($currentRow[0] ?? '');

        if ($currentDepth <= 0 || empty($currentSerial)) {
            return null;
        }

        // Method 1: Use serial number (e.g., 1.2.3 → parent is 1.2)
        $parts = explode('.', $currentSerial);
        if (count($parts) > 1) {
            array_pop($parts);
            $parentSerial = implode('.', $parts);

            foreach (array_slice($rows, 0, $rowIndex) as $prevRow) {
                if (trim($prevRow[0] ?? '') === $parentSerial) {
                    return $prevRow[9] ?? null;
                }
            }
        }

        // Method 2: Fallback to previous row with depth - 1
        for ($i = $rowIndex - 1; $i >= 0; $i--) {
            if (($rows[$i][8] ?? 0) === $currentDepth - 1) {
                return $rows[$i][9] ?? null;
            }
        }

        return null;
    }
}
