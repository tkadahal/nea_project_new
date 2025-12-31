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

        // These will be filled by WithEvents (better for merging)
        $rows[] = ['Quarterly Budget Allocation Template (Admin Only)'];
        $rows[] = ['Directorate:', $this->directorateTitle];
        $rows[] = ['Fiscal Year:', $this->fiscalYearTitle];
        $rows[] = []; // empty
        $rows[] = ['SN', 'Project Title', 'Budget Type', 'Total Approved', 'Q1', 'Q2', 'Q3', 'Q4', 'Source (if foreign)'];

        $sn = 1;
        $budgetTypes = [
            'Government Share',
            'Government Loan',
            'Foreign Loan',
            'Foreign Subsidy',
            'Internal Resources', // ← 5th source added
        ];

        if ($this->projects->isEmpty()) {
            $rows[] = ['No projects found for the selected filters. Add rows manually below if needed.', '', '', '', '', '', '', '', ''];
        } else {
            foreach ($this->projects as $project) {
                $budget = $project->budgets->first(); // may be null

                foreach ($budgetTypes as $type) {
                    $total = 0;
                    $source = '';

                    if ($budget) {
                        $total = match ($type) {
                            'Government Share'     => $budget->government_share ?? 0,
                            'Government Loan'       => $budget->government_loan ?? 0,
                            'Foreign Loan'         => $budget->foreign_loan_budget ?? 0,
                            'Foreign Subsidy'      => $budget->foreign_subsidy_budget ?? 0,
                            'Internal Resources'   => $budget->internal_budget ?? 0, // assuming column name
                            default                => 0,
                        };

                        if (in_array($type, ['Foreign Loan', 'Foreign Subsidy'])) {
                            $source = $type === 'Foreign Loan'
                                ? ($budget->foreign_loan_source ?? '')
                                : ($budget->foreign_subsidy_source ?? '');
                        }
                    }

                    $rows[] = [
                        $sn++,
                        $project->title,
                        $type,
                        $total,
                        '',
                        '',
                        '',
                        '',
                        $source
                    ];
                }
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
            5 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = 'I'; // 9 columns

                // 1. Merge & center main title
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 2. Merge Directorate label + value across center
                $sheet->mergeCells('A2:B2');
                $sheet->mergeCells("C2:{$highestColumn}2");
                $sheet->getStyle("C2:{$highestColumn}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 3. Merge Fiscal Year label + value
                $sheet->mergeCells('A3:B3');
                $sheet->mergeCells("C3:{$highestColumn}3");
                $sheet->getStyle("C3:{$highestColumn}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 4. Table borders (from row 5 to end)
                $borderStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ];

                $sheet->getStyle("A5:{$highestColumn}{$highestRow}")->applyFromArray($borderStyle);

                // Optional: Auto-fit columns
                foreach (range('A', $highestColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
