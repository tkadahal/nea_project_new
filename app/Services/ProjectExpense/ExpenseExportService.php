<?php

declare(strict_types=1);

namespace App\Services\ProjectExpense;

use App\Models\Project;
use App\Models\FiscalYear;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Templates\ExpenseTemplateExport;
use App\Exports\Reports\ProgramExpenseReportExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseExportService
{
    public function __construct(
        private readonly ExpenseQuarterService $quarterService
    ) {}

    public function downloadTemplate(
        Project $project,
        FiscalYear $fiscalYear,
        string $quarter
    ): BinaryFileResponse {
        $quarterNumber = $this->quarterService->extractQuarterNumber($quarter);

        if (!in_array($quarterNumber, [1, 2, 3, 4])) {
            abort(400, 'Invalid quarter selected.');
        }

        $filename = $this->generateTemplateFilename($project, $fiscalYear, $quarterNumber);

        return Excel::download(
            new ExpenseTemplateExport(
                $project->title,
                $fiscalYear->title,
                $project->id,
                $fiscalYear->id,
                $quarterNumber
            ),
            $filename
        );
    }

    public function downloadReport(
        Project $project,
        FiscalYear $fiscalYear,
        string $quarter
    ): BinaryFileResponse {
        $quarterNumber = $this->quarterService->extractQuarterNumber($quarter);

        if (!in_array($quarterNumber, [1, 2, 3, 4])) {
            $quarterNumber = 1;
        }

        $filename = $this->generateReportFilename($project, $fiscalYear);

        return Excel::download(
            new ProgramExpenseReportExport(
                $project,
                $fiscalYear,
                $quarterNumber
            ),
            $filename
        );
    }

    private function generateTemplateFilename(
        Project $project,
        FiscalYear $fiscalYear,
        int $quarterNumber
    ): string {
        $safeProjectTitle = $this->sanitizeFilename($project->title);
        $safeFiscalTitle = $this->sanitizeFilename($fiscalYear->title);
        $quarterLabel = "Q{$quarterNumber}";

        return "Progress_Report_{$safeProjectTitle}_{$safeFiscalTitle}_{$quarterLabel}.xlsx";
    }

    private function generateReportFilename(
        Project $project,
        FiscalYear $fiscalYear
    ): string {
        $safeProjectTitle = $this->sanitizeFilename($project->title);
        $safeFiscalTitle = $this->sanitizeFilename($fiscalYear->title);

        return "ExpenseReport_{$safeProjectTitle}_{$safeFiscalTitle}.xlsx";
    }

    private function sanitizeFilename(string $filename): string
    {
        return str_replace(['/', '\\'], '_', Str::slug($filename));
    }
}
