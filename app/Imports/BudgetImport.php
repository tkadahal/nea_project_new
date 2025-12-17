<?php

declare(strict_types=1);

namespace App\Imports;

use Normalizer;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BudgetImport implements WithHeadingRow, SkipsEmptyRows
{
    public function collection($rows)
    {
        // This method is not used since we'll process the spreadsheet directly
    }

    public function import($file)
    {
        $filtered = [];
        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            Log::info('Raw Excel data', ['data' => $rows]);

            // Skip title rows (row 1: "Budget Template", row 2: "Fiscal Year: xxxx")
            // Row 3 contains headers
            if (count($rows) >= 3) {
                array_shift($rows); // Remove row 1 (title)
                array_shift($rows); // Remove row 2 (fiscal year info)
            }

            // Get headers (now row 3, which is the first row after shifting)
            $headers = array_shift($rows);

            // Filter out null values and convert to lowercase safely
            $headerMap = array_flip(array_map(function ($header) {
                return strtolower(trim($header ?? ''));
            }, array_filter($headers, function ($header) {
                return $header !== null && $header !== '';
            })));

            Log::info('Header map', ['headers' => $headerMap]);

            // Define column mappings based on new template structure
            $columnKeys = [
                's_n' => ['s.n.', 'sn', 's.n', 'serial'],
                'project' => ['project', 'project title', 'project_title'],
                'gov_loan' => ['gov loan', 'government loan', 'government_loan'],
                'gov_share' => ['gov share', 'government share', 'government_share'],
                'foreign_loan' => ['foreign loan', 'foreign_loan', 'foreign_loan_budget'],
                'foreign_loan_source' => ['source'], // First "Source" column (after Foreign Loan)
                'foreign_subsidy' => ['foreign subsidy', 'foreign_subsidy', 'foreign_subsidy_budget'],
                'foreign_subsidy_source' => ['source'], // Second "Source" column (after Foreign Subsidy)
                'nea' => ['nea', 'internal budget', 'internal_budget'],
                'total' => ['total', 'total budget', 'total_budget'],
            ];

            // Find column indices for each field
            $columnIndices = [];
            foreach ($columnKeys as $field => $possibleKeys) {
                foreach ($possibleKeys as $key) {
                    $lowerKey = strtolower(trim($key));
                    if (isset($headerMap[$lowerKey])) {
                        $columnIndices[$field] = $headerMap[$lowerKey];
                        break;
                    }
                }
            }

            // Handle duplicate "Source" columns - they appear at positions F and H
            // We need to identify them by position since they have the same name
            $sourceColumns = [];
            foreach ($headers as $index => $header) {
                if ($header !== null && strtolower(trim($header)) === 'source') {
                    $sourceColumns[] = $index;
                }
            }

            if (count($sourceColumns) >= 2) {
                $columnIndices['foreign_loan_source'] = $sourceColumns[0]; // Column F (after Foreign Loan)
                $columnIndices['foreign_subsidy_source'] = $sourceColumns[1]; // Column H (after Foreign Subsidy)
            }

            Log::info('Column indices', ['indices' => $columnIndices]);

            foreach ($rows as $index => $row) {
                // Stop processing if we hit the instructions section
                if (isset($row[0]) && is_string($row[0]) && strpos($row[0], 'Instructions:') === 0) {
                    Log::info('Instructions row detected, stopping processing');
                    break;
                }

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Get project title
                $projectTitle = isset($columnIndices['project']) ? trim($row[$columnIndices['project']] ?? '') : '';

                if (empty($projectTitle)) {
                    Log::warning('Skipping row due to empty project title', [
                        'index' => $index + 1,
                        'row' => $row,
                    ]);
                    continue;
                }

                // Normalize project title
                $projectTitle = preg_replace('/\s+/', ' ', $projectTitle);
                $projectTitle = normalizer_normalize($projectTitle, Normalizer::FORM_C);

                // Extract budget values using calculated values from Excel
                $rowNumber = $index + 4; // Actual row number in Excel (3 header rows + current index + 1)

                $budgetData = [
                    'government_loan' => 0.00,
                    'government_share' => 0.00,
                    'foreign_loan_budget' => 0.00,
                    'foreign_loan_source' => '',
                    'foreign_subsidy_budget' => 0.00,
                    'foreign_subsidy_source' => '',
                    'internal_budget' => 0.00,
                    'total_budget' => 0.00,
                ];

                // Map numeric columns
                $numericFields = [
                    'gov_loan' => 'government_loan',
                    'gov_share' => 'government_share',
                    'foreign_loan' => 'foreign_loan_budget',
                    'foreign_subsidy' => 'foreign_subsidy_budget',
                    'nea' => 'internal_budget',
                    'total' => 'total_budget',
                ];

                foreach ($numericFields as $colKey => $dbField) {
                    if (isset($columnIndices[$colKey])) {
                        $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndices[$colKey] + 1) . $rowNumber;
                        $calculatedValue = $worksheet->getCell($cellCoordinate)->getCalculatedValue();
                        $budgetData[$dbField] = round(floatval($calculatedValue ?? 0), 2);
                    }
                }

                // Map text source columns
                if (isset($columnIndices['foreign_loan_source'])) {
                    $budgetData['foreign_loan_source'] = trim($row[$columnIndices['foreign_loan_source']] ?? '');
                }

                if (isset($columnIndices['foreign_subsidy_source'])) {
                    $budgetData['foreign_subsidy_source'] = trim($row[$columnIndices['foreign_subsidy_source']] ?? '');
                }

                Log::info('Processed row', [
                    'index' => $index + 1,
                    'row_number' => $rowNumber,
                    'project_title' => $projectTitle,
                    'budget_data' => $budgetData,
                ]);

                $filtered[] = [
                    'project_title' => $projectTitle,
                    'government_loan' => $budgetData['government_loan'],
                    'government_share' => $budgetData['government_share'],
                    'foreign_loan_budget' => $budgetData['foreign_loan_budget'],
                    'foreign_loan_source' => $budgetData['foreign_loan_source'],
                    'foreign_subsidy_budget' => $budgetData['foreign_subsidy_budget'],
                    'foreign_subsidy_source' => $budgetData['foreign_subsidy_source'],
                    'internal_budget' => $budgetData['internal_budget'],
                    'total_budget' => $budgetData['total_budget'],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error processing Excel file', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        Log::info('Import completed', ['total_records' => count($filtered)]);
        return collect($filtered);
    }

    public function headingRow(): int
    {
        return 3; // Headers are now in row 3
    }
}
