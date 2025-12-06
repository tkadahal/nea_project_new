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

            // Get headers (first row)
            $headers = array_shift($rows);
            $headerMap = array_flip(array_map('strtolower', $headers));

            // Define possible header variations
            $fiscalYearKeys = ['fiscal_year', 'Fiscal Year', 'fiscal_year_pre_filled_with_current_fiscal_year_change_if_needed', 'आर्थिक वर्ष'];
            $projectTitleKeys = ['project_title', 'Project Title'];
            $budgetKeys = [
                'government_loan' => ['government_loan', 'Government Loan'],
                'government_share' => ['government_share', 'Government Share'],
                'foreign_loan_budget' => ['foreign_loan_budget', 'Foreign Loan Budget'],
                'foreign_subsidy_budget' => ['foreign_subsidy_budget', 'Foreign Subsidy Budget'],
                'internal_budget' => ['internal_budget', 'Internal Budget'],
                'total_budget' => ['total_budget', 'Total Budget'],
            ];

            // Find the actual header keys used in the file
            $fiscalYearKey = null;
            foreach ($fiscalYearKeys as $key) {
                if (isset($headerMap[strtolower($key)])) {
                    $fiscalYearKey = $key;
                    break;
                }
            }

            $projectTitleKey = null;
            foreach ($projectTitleKeys as $key) {
                if (isset($headerMap[strtolower($key)])) {
                    $projectTitleKey = $key;
                    break;
                }
            }

            $budgetColumns = [];
            foreach ($budgetKeys as $field => $possibleKeys) {
                foreach ($possibleKeys as $key) {
                    if (isset($headerMap[strtolower($key)])) {
                        $budgetColumns[$field] = $headerMap[strtolower($key)];
                        break;
                    }
                }
            }

            foreach ($rows as $index => $row) {
                // Stop processing if the row contains the instructions header
                $fiscalYear = isset($headerMap[strtolower($fiscalYearKey)]) ? trim($row[$headerMap[strtolower($fiscalYearKey)]]) : '';
                if (strpos($fiscalYear, 'Instructions:') === 0) {
                    Log::info('Instructions row detected, stopping processing', ['row' => $row]);
                    break;
                }

                // Map fiscal year
                $fiscalYear = '';
                foreach ($fiscalYearKeys as $key) {
                    if (isset($headerMap[strtolower($key)]) && !empty(trim($row[$headerMap[strtolower($key)]]))) {
                        $fiscalYear = trim($row[$headerMap[strtolower($key)]]);
                        break;
                    }
                }

                // Normalize project title
                $projectTitle = '';
                foreach ($projectTitleKeys as $key) {
                    if (isset($headerMap[strtolower($key)]) && !empty(trim($row[$headerMap[strtolower($key)]]))) {
                        $projectTitle = trim($row[$headerMap[strtolower($key)]]);
                        break;
                    }
                }
                $projectTitle = preg_replace('/\s+/', ' ', $projectTitle);
                $projectTitle = normalizer_normalize($projectTitle, Normalizer::FORM_C);

                // Get budget values using computed values from Excel
                $budgetData = [];
                foreach ($budgetKeys as $field => $possibleKeys) {
                    if (isset($budgetColumns[$field])) {
                        $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($budgetColumns[$field] + 1) . ($index + 2);
                        $calculatedValue = $worksheet->getCell($cellCoordinate)->getCalculatedValue();
                        $budgetData[$field] = round(floatval($calculatedValue ?? 0), 2); // Round to 2 decimal places
                    } else {
                        $budgetData[$field] = 0.00;
                    }
                }

                Log::info('Processed row', [
                    'index' => $index + 1,
                    'fiscal_year' => $fiscalYear,
                    'project_title' => $projectTitle,
                    'budget_data' => $budgetData,
                    'raw_row' => $row,
                ]);

                if (empty($fiscalYear) || empty($projectTitle)) {
                    Log::warning('Skipping row due to empty fiscal year or project title', [
                        'index' => $index + 1,
                        'row' => $row,
                    ]);
                    continue;
                }

                $filtered[] = [
                    'fiscal_year' => $fiscalYear,
                    'project_title' => $projectTitle,
                    'government_loan' => $budgetData['government_loan'],
                    'government_share' => $budgetData['government_share'],
                    'foreign_loan_budget' => $budgetData['foreign_loan_budget'],
                    'foreign_subsidy_budget' => $budgetData['foreign_subsidy_budget'],
                    'internal_budget' => $budgetData['internal_budget'],
                    'total_budget' => $budgetData['total_budget'],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error processing Excel file', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }

        return collect($filtered);
    }

    public function headingRow(): int
    {
        return 1;
    }
}
