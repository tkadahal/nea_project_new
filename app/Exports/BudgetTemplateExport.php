<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\FiscalYear;

class BudgetTemplateExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $projects;

    public function __construct($projects)
    {
        $this->projects = $projects;
    }

    public function collection()
    {
        $options = collect(FiscalYear::getFiscalYearOptions());
        $currentFiscalYear = $options->firstWhere('selected', true)['label'] ?? '';

        $rows = $this->projects->map(function ($project, $index) use ($currentFiscalYear) {
            // Row number in Excel (header is row 1, data starts at row 2)
            $rowNumber = $index + 2;
            return [
                $currentFiscalYear, // Fiscal Year (pre-filled with current fiscal year)
                $project->title, // Project Title (pre-filled)
                0.00, // Government Loan
                0.00, // Government Share
                0.00, // Foreign Loan Budget
                0.00, // Foreign Subsidy Budget
                0.00, // Internal Budget
                "=SUM(C{$rowNumber}:G{$rowNumber})", // Total Budget (dynamic formula for this row)
            ];
        });

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Fiscal Year',
            'Project Title',
            'Government Loan',
            'Government Share',
            'Foreign Loan Budget',
            'Foreign Subsidy Budget',
            'Internal Budget',
            'Total Budget',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        $sheet->getStyle('B2:B' . (1 + $this->projects->count()))->getAlignment()->setHorizontal('left');
        $sheet->getStyle('C2:H' . (1 + $this->projects->count()))->getAlignment()->setHorizontal('right');
        $sheet->getStyle('C2:H' . (1 + $this->projects->count()))->getNumberFormat()->setFormatCode('#,##0.00');

        // Note about fiscal year
        $sheet->setCellValue('A1', 'Fiscal Year');
        $sheet->mergeCells('A1:A1');
        $sheet->getStyle('A1')->getAlignment()->setWrapText(true);

        // Instructions
        $instructionStartRow = 2 + $this->projects->count();
        $sheet->setCellValue('A' . $instructionStartRow, 'Instructions:');
        $sheet->setCellValue('A' . ($instructionStartRow + 1), '- Fiscal Year is pre-filled with the current year; modify if needed (must match exact title from system)');
        $sheet->setCellValue('A' . ($instructionStartRow + 2), '- Enter budget amounts in the respective columns');
        $sheet->setCellValue('A' . ($instructionStartRow + 3), '- Total Budget will auto-calculate as the sum of the five budget components');
        $sheet->getStyle('A' . $instructionStartRow . ':A' . ($instructionStartRow + 3))->getFont()->setItalic(true);

        return [];
    }

    public function title(): string
    {
        return 'Budget Template';
    }
}
