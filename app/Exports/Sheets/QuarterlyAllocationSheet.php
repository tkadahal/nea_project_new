<?php

namespace App\Exports\Sheets;

use App\Models\PreBudget;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuarterlyAllocationSheet implements FromCollection, ShouldAutoSize, WithEvents, WithMapping, WithTitle
{
    public $fiscalYearTitle = 'N/A';

    public function collection()
    {
        $data = PreBudget::with(['project', 'quarterAllocations', 'fiscalYear'])
            ->get();

        if ($data->isNotEmpty()) {
            $this->fiscalYearTitle = $data->first()->fiscalYear?->title ?? 'N/A';
        }

        return $data
            ->sortBy(fn ($pb) => $pb->project->title ?? 'ZZZ')
            ->flatMap(function ($pb) {
                return $pb->quarterAllocations->sortBy('quarter')->map(function ($q) use ($pb) {
                    return [
                        'project' => $pb->project->title ?? 'Unknown Project',
                        'quarter' => 'Q'.$q->quarter,
                        'internal' => $q->internal_budget,
                        'gov_share' => $q->government_share,
                        'gov_loan' => $q->government_loan,
                        'foreign_loan' => $q->foreign_loan_budget,
                        'foreign_subsidy' => $q->foreign_subsidy_budget,
                        'company' => $q->company_budget,
                        'total' => $q->internal_budget +
                            $q->government_share +
                            $q->government_loan +
                            $q->foreign_loan_budget +
                            $q->foreign_subsidy_budget +
                            $q->company_budget,
                    ];
                });
            });
    }

    public function map($row): array
    {
        return array_values($row);
    }

    public function title(): string
    {
        return 'Quarterly Allocation';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // --- HEIGHT ADJUSTMENTS ---

                // Set a taller default height for all data rows (approx 20px)
                $sheet->getDefaultRowDimension()->setRowHeight(20);

                // Set specific heights for headers (approx 25-30px)
                $sheet->getRowDimension(1)->setRowHeight(30); // Title
                $sheet->getRowDimension(2)->setRowHeight(25); // Subtitle
                $sheet->getRowDimension(3)->setRowHeight(25); // Header Categories
                $sheet->getRowDimension(4)->setRowHeight(20); // Header Details

                // 1. Insert 4 rows at the top
                $sheet->insertNewRowBefore(1, 4);

                // 2. Set Main Title
                $sheet->setCellValue('A1', 'Pre-Budget Quarterly Allocation');
                $sheet->mergeCells('A1:I1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // 3. Set Subtitle
                $sheet->setCellValue('A2', 'Fiscal Year: '.$this->fiscalYearTitle);
                $sheet->mergeCells('A2:I2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // 4. Build Table Header
                $sheet->setCellValue('A3', 'Project Details');
                $sheet->setCellValue('C3', 'Internal');
                $sheet->setCellValue('D3', 'Govt. Sources');
                $sheet->setCellValue('F3', 'Foreign Sources');
                $sheet->setCellValue('H3', 'Company');
                $sheet->setCellValue('I3', 'Total');

                $sheet->setCellValue('A4', 'Project');
                $sheet->setCellValue('B4', 'Quarter');
                $sheet->setCellValue('C4', 'Budget');
                $sheet->setCellValue('D4', 'Share');
                $sheet->setCellValue('E4', 'Loan');
                $sheet->setCellValue('F4', 'Loan');
                $sheet->setCellValue('G4', 'Subsidy');
                $sheet->setCellValue('H4', 'Budget');
                $sheet->setCellValue('I4', 'Amount');

                // 5. Merge Header Cells
                $sheet->mergeCells('A3:B3');
                $sheet->mergeCells('D3:E3');
                $sheet->mergeCells('F3:G3');

                // 6. Style the Header Rows
                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        'inside' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ],
                ];

                $catStyle = array_merge($headerStyle, [
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => '4472C4'],
                    ],
                ]);
                $sheet->getStyle('A3:I3')->applyFromArray($catStyle);

                $subStyle = array_merge($headerStyle, [
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => '5B9BD5'],
                    ],
                ]);
                $sheet->getStyle('A4:I4')->applyFromArray($subStyle);

                // 7. Format Data Columns
                $highestRow = $sheet->getHighestRow();
                if ($highestRow > 4) {
                    $sheet->getStyle('C5:I'.$highestRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                }

                // 8. Perform Project Merging
                $this->mergeProjectGroup($sheet, 5, $highestRow);
            },
        ];
    }

    protected function mergeProjectGroup(Worksheet $sheet, $startRow, $endRow)
    {
        if ($endRow < $startRow) {
            return;
        }

        $currentProject = null;
        $groupStart = $startRow;

        for ($row = $startRow; $row <= $endRow + 1; $row++) {
            $projectName = ($row <= $endRow) ? $sheet->getCell("A{$row}")->getValue() : '';

            if ($projectName !== $currentProject) {
                if ($currentProject !== null) {
                    $groupEnd = $row - 1;
                    $this->applyMerges($sheet, $groupStart, $groupEnd);
                }
                $currentProject = $projectName;
                $groupStart = $row;
            }
        }
    }

    protected function applyMerges(Worksheet $sheet, $startRow, $endRow)
    {
        if ($startRow === $endRow) {
            $sheet->getStyle("I{$startRow}")->getFont()->setBold(true);

            return;
        }

        // Merge Project Name (A)
        $sheet->mergeCells("A{$startRow}:A{$endRow}");
        $sheet->getStyle("A{$startRow}:A{$endRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Calculate Whole Total
        $wholeTotal = 0;
        for ($i = $startRow; $i <= $endRow; $i++) {
            $val = $sheet->getCell("I{$i}")->getValue();
            $wholeTotal += (float) str_replace([',', ' '], '', $val);
        }

        // Merge Total (I)
        $sheet->mergeCells("I{$startRow}:I{$endRow}");
        $sheet->setCellValue("I{$startRow}", $wholeTotal);

        // Style Total
        $sheet->getStyle("I{$startRow}")->getFont()->setBold(true);
        $sheet->getStyle("I{$startRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Add border around the merged block
        $sheet->getStyle("A{$startRow}:I{$endRow}")->applyFromArray([
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);
    }
}
