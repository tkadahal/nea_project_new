<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AdminQuarterBudgetTemplateExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithEvents
{
    protected $projects;
    protected $directorateTitle;
    protected $fiscalYearTitle;

    public function __construct($projects, $directorateTitle, $fiscalYearTitle)
    {
        $this->projects = $projects;
        $this->directorateTitle = $directorateTitle;
        $this->fiscalYearTitle = $fiscalYearTitle;
    }

    public function collection()
    {
        $rows = [];

        // Row 1: Main title
        $rows[] = ['Quarterly Budget Allocation Template (Admin Only)'];
        // Row 2: Directorate
        $rows[] = ['Directorate:'];
        // Row 3: Fiscal Year
        $rows[] = ['Fiscal Year:'];
        // Row 4: Table headers
        $rows[] = ['SN', 'Project Title', 'Budget Type', 'Total Approved', 'Q1', 'Q2', 'Q3', 'Q4', 'Source (if foreign)'];

        $budgetTypes = [
            'Government Share',
            'Government Loan',
            'Foreign Loan',
            'Foreign Subsidy',
            'Internal Resources',
        ];

        if ($this->projects->isEmpty()) {
            $rows[] = ['No projects found for the selected filters. Add rows manually below if needed.', '', '', '', '', '', '', '', ''];
        } else {
            $sn = 1;
            foreach ($this->projects as $project) {
                $budget = $project->budgets->first();

                foreach ($budgetTypes as $index => $type) {
                    $total = 0;
                    $source = '';

                    if ($budget) {
                        $total = match ($type) {
                            'Government Share'     => $budget->government_share ?? 0,
                            'Government Loan'       => $budget->government_loan ?? 0,
                            'Foreign Loan'         => $budget->foreign_loan_budget ?? 0,
                            'Foreign Subsidy'      => $budget->foreign_subsidy_budget ?? 0,
                            'Internal Resources'   => $budget->internal_budget ?? 0,
                            default                => 0,
                        };

                        if (in_array($type, ['Foreign Loan', 'Foreign Subsidy'])) {
                            $source = $type === 'Foreign Loan'
                                ? ($budget->foreign_loan_source ?? '')
                                : ($budget->foreign_subsidy_source ?? '');
                        }
                    }

                    $rows[] = [
                        $index === 0 ? $sn : '',     // SN only on first row
                        $project->title,             // ← ALWAYS write project title (this is the fix)
                        $type,
                        $total,
                        '',
                        '',
                        '',
                        '',
                        $source
                    ];
                }

                $sn++;
            }
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Quarter Allocation';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            4 => [  // Now the header row
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2EFDA'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = 'I';

                // 1. Main title
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->getStyle('A1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // 2. Directorate
                $sheet->setCellValue('C2', $this->directorateTitle);
                $sheet->mergeCells('A2:B2');
                $sheet->mergeCells("C2:{$highestColumn}2");
                $sheet->getStyle("C2:{$highestColumn}2")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // 3. Fiscal Year
                $sheet->setCellValue('C3', $this->fiscalYearTitle);
                $sheet->mergeCells('A3:B3');
                $sheet->mergeCells("C3:{$highestColumn}3");
                $sheet->getStyle("C3:{$highestColumn}3")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // 4. Merge header SN and Project Title horizontally
                $sheet->mergeCells('A4:B4');
                $sheet->getStyle('A4:B4')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // 5. Vertical merge for SN (A) and Project Title (B) starting from row 5
                $currentRow = 5; // First data row now
                while ($currentRow <= $highestRow) {
                    $projectTitle = $sheet->getCell("B{$currentRow}")->getValue();

                    if (empty($projectTitle)) {
                        $currentRow++;
                        continue;
                    }

                    $startRow = $currentRow;
                    $endRow = min($startRow + 4, $highestRow);

                    // Merge SN vertically
                    $sheet->mergeCells("A{$startRow}:A{$endRow}");
                    $sheet->getStyle("A{$startRow}:A{$endRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    // Merge Project Title vertically
                    $sheet->mergeCells("B{$startRow}:B{$endRow}");
                    $sheet->getStyle("B{$startRow}:B{$endRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER)
                        ->setWrapText(true);

                    // Bold
                    $sheet->getStyle("A{$startRow}")->getFont()->setBold(true);
                    $sheet->getStyle("B{$startRow}")->getFont()->setBold(true);

                    $currentRow = $endRow + 1;
                }

                // 6. Borders for the whole table (starting from header row 4)
                $borderStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ];
                $sheet->getStyle("A4:{$highestColumn}{$highestRow}")->applyFromArray($borderStyle);

                // 7. Column widths
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(60);
                foreach (range('C', $highestColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // 8. Row heights for better vertical centering
                $currentRow = 5;
                while ($currentRow <= $highestRow) {
                    $projectTitle = $sheet->getCell("B{$currentRow}")->getValue();
                    if (empty($projectTitle)) {
                        $currentRow++;
                        continue;
                    }
                    $startRow = $currentRow;
                    $endRow = min($startRow + 4, $highestRow);
                    for ($r = $startRow; $r <= $endRow; $r++) {
                        $sheet->getRowDimension($r)->setRowHeight(35);
                    }
                    $currentRow = $endRow + 1;
                }

                // 9. Freeze pane below the header
                $sheet->freezePane('A5');
            },
        ];
    }
}
