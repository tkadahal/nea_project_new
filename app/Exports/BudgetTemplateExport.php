<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\FiscalYear;

class BudgetTemplateExport implements FromCollection, WithStyles, WithTitle, WithEvents
{
    protected $projects;
    protected $directorateTitle;
    protected $currentFiscalYear;

    public function __construct($projects, string $directorateTitle)
    {
        $this->projects = $projects;
        $this->directorateTitle = $directorateTitle;

        $options = collect(FiscalYear::getFiscalYearOptions());
        $this->currentFiscalYear = $options->firstWhere('selected', true)['label'] ?? '';
    }

    public function collection()
    {
        $rows = collect();

        // Row 1: Title
        $rows->push([$this->directorateTitle, '', '', '', '', '', '', '', '', '']);

        // Row 2: Fiscal Year
        $rows->push(['Fiscal Year: ' . $this->currentFiscalYear, '', '', '', '', '', '', '', '', '']);

        // Row 3: Headers
        $rows->push(['S.N.', 'Project', 'Gov Loan', 'Gov Share', 'Foreign Loan', 'Source', 'Foreign Subsidy', 'Source', 'NEA', 'Total']);

        // Row 4+: Data
        $this->projects->each(function ($project, $index) use ($rows) {
            $rowNumber = $rows->count() + 1; // Current row number in Excel

            $rows->push([
                $index + 1,                   // A: S.N.
                $project->title,              // B: Project
                0.00,                         // C: Gov Loan
                0.00,                         // D: Gov Share
                0.00,                         // E: Foreign Loan
                '',                           // F: Source (Foreign Loan)
                0.00,                         // G: Foreign Subsidy
                '',                           // H: Source (Foreign Subsidy)
                0.00,                         // I: NEA
                "=C{$rowNumber}+D{$rowNumber}+E{$rowNumber}+G{$rowNumber}+I{$rowNumber}", // J: Total (fixed formula)
            ]);
        });

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $projectCount = $this->projects->count();
                $lastDataRow = 3 + $projectCount;

                // 1. Directorate Title - Row 1
                $sheet->mergeCells('A1:J1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension(1)->setRowHeight(40);

                // 2. Fiscal Year - Row 2
                $sheet->mergeCells('A2:J2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension(2)->setRowHeight(30);

                // 3. Header Row (Row 3) - Bold + Background + Text Wrapping
                $sheet->getStyle('A3:J3')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A3:J3')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E2E8F0');
                $sheet->getStyle('A3:J3')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getRowDimension(3)->setRowHeight(30);

                // 4. Table Borders (full thin borders for header + data)
                $borderStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ];
                $sheet->getStyle('A3:J' . $lastDataRow)->applyFromArray($borderStyle);

                // 5. Column Widths (fixed for nice layout)
                $sheet->getColumnDimension('A')->setWidth(8);   // S.N.
                $sheet->getColumnDimension('B')->setWidth(50);  // Project (wide for long titles)
                $sheet->getColumnDimension('C')->setWidth(15);  // Gov Loan
                $sheet->getColumnDimension('D')->setWidth(15);  // Gov Share
                $sheet->getColumnDimension('E')->setWidth(15);  // Foreign Loan
                $sheet->getColumnDimension('F')->setWidth(20);  // Source
                $sheet->getColumnDimension('G')->setWidth(17);  // Foreign Subsidy (slightly wider)
                $sheet->getColumnDimension('H')->setWidth(20);  // Source
                $sheet->getColumnDimension('I')->setWidth(15);  // NEA
                $sheet->getColumnDimension('J')->setWidth(15);  // Total

                // 6. Enable text wrapping for all data cells
                $sheet->getStyle('A4:J' . $lastDataRow)->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);

                // 7. Number Formatting (money columns)
                $sheet->getStyle('C4:E' . $lastDataRow)
                    ->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('G4:G' . $lastDataRow)
                    ->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('I4:J' . $lastDataRow)
                    ->getNumberFormat()->setFormatCode('#,##0.00');

                // 8. Horizontal Alignment
                $sheet->getStyle('A4:A' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B4:B' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('C4:E' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('F4:F' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('G4:G' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('H4:H' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('I4:J' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // 9. Auto-adjust row height for wrapped text in data rows
                for ($row = 4; $row <= $lastDataRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(-1); // Auto height
                }

                // 10. Instructions below the table
                $instructionStartRow = $lastDataRow + 2;
                $sheet->setCellValue('A' . $instructionStartRow, 'Instructions:');
                $sheet->setCellValue('A' . ($instructionStartRow + 1), '- Enter budget amounts in the numeric columns (Gov Loan, Gov Share, Foreign Loan, Foreign Subsidy, NEA)');
                $sheet->setCellValue('A' . ($instructionStartRow + 2), '- Enter source names in the "Source" columns (e.g., ADB, World Bank, KfW)');
                $sheet->setCellValue('A' . ($instructionStartRow + 3), '- Total column will auto-calculate');
                $sheet->setCellValue('A' . ($instructionStartRow + 4), '- Do not modify S.N., Project names, or add/delete rows');
                $sheet->getStyle('A' . $instructionStartRow . ':A' . ($instructionStartRow + 4))
                    ->getFont()->setItalic(true)->setSize(10);

                // 11. Data Validation - Text only for Source columns (F and H)
                $validation = $sheet->getDataValidation('F4:F' . $lastDataRow);
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_CUSTOM);
                $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Invalid Input');
                $validation->setError('Please enter text only (e.g., ADB, World Bank, KfW). Numbers are not allowed.');
                $validation->setPromptTitle('Source Name');
                $validation->setPrompt('Enter the funding source name (text only)');
                $validation->setFormula1('=NOT(ISNUMBER(F4))');

                // Copy validation to column H
                $validation2 = $sheet->getDataValidation('H4:H' . $lastDataRow);
                $validation2->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_CUSTOM);
                $validation2->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $validation2->setAllowBlank(true);
                $validation2->setShowInputMessage(true);
                $validation2->setShowErrorMessage(true);
                $validation2->setErrorTitle('Invalid Input');
                $validation2->setError('Please enter text only (e.g., ADB, World Bank, KfW). Numbers are not allowed.');
                $validation2->setPromptTitle('Source Name');
                $validation2->setPrompt('Enter the funding source name (text only)');
                $validation2->setFormula1('=NOT(ISNUMBER(H4))');
            },
        ];
    }

    public function title(): string
    {
        return 'Budget Template';
    }
}
