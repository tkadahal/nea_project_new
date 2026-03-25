<?php

namespace App\Exports\Sheets;

use App\Models\PreBudget;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BudgetHeadingSummarySheet implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithMapping, WithTitle
{
    public $fiscalYearTitle = 'N/A';

    public function collection()
    {
        $data = PreBudget::with(['project.budgetHeading', 'fiscalYear'])->get();

        if ($data->isNotEmpty()) {
            $this->fiscalYearTitle = $data->first()->fiscalYear?->title ?? 'N/A';
        }

        return $data
            ->groupBy(fn ($pb) => $pb->project->budgetHeading->title ?? 'Unknown Heading')
            ->map(function ($items, $heading) {
                return [
                    'heading' => $heading,
                    'internal' => $items->sum('internal_budget'),
                    'gov_share' => $items->sum('government_share'),
                    'gov_loan' => $items->sum('government_loan'),
                    'foreign_loan' => $items->sum('foreign_loan_budget'),
                    'foreign_subsidy' => $items->sum('foreign_subsidy_budget'),
                    'company' => $items->sum('company_budget'),
                    'total' => $items->sum('total_budget'),
                ];
            })
            ->sortBy('heading')
            ->values();
    }

    public function map($row): array
    {
        return array_values($row);
    }

    public function title(): string
    {
        return 'Budget Heading Summary';
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_00,
            'C' => NumberFormat::FORMAT_NUMBER_00,
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // --- HEIGHT ADJUSTMENTS ---
                $sheet->getDefaultRowDimension()->setRowHeight(20);
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(25);
                $sheet->getRowDimension(3)->setRowHeight(25);
                $sheet->getRowDimension(4)->setRowHeight(20);

                // 1. Insert 4 rows at the top
                $sheet->insertNewRowBefore(1, 4);

                // 2. Set Main Title
                $sheet->setCellValue('A1', 'Budget Heading Summary');
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // 3. Set Subtitle
                $sheet->setCellValue('A2', 'Fiscal Year: '.$this->fiscalYearTitle);
                $sheet->mergeCells('A2:H2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // 4. Build Table Header
                // Row 3: Categories
                $sheet->setCellValue('A3', 'Budget Heading');  // A
                $sheet->setCellValue('B3', 'Internal');         // B
                $sheet->setCellValue('C3', 'Govt. Sources');    // Spans C-D
                $sheet->setCellValue('E3', 'Foreign Sources');   // Spans E-F
                $sheet->setCellValue('G3', 'Company');           // G
                $sheet->setCellValue('H3', 'Total');             // H

                // Row 4: Specifics
                $sheet->setCellValue('A4', 'Heading Name');
                $sheet->setCellValue('B4', 'Budget');
                $sheet->setCellValue('C4', 'Share');
                $sheet->setCellValue('D4', 'Loan');
                $sheet->setCellValue('E4', 'Loan');
                $sheet->setCellValue('F4', 'Subsidy');
                $sheet->setCellValue('G4', 'Budget');
                $sheet->setCellValue('H4', 'Amount');

                // 5. Merge Header Cells
                $sheet->mergeCells('C3:D3'); // Govt Sources
                $sheet->mergeCells('E3:F3'); // Foreign Sources

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
                $sheet->getStyle('A3:H3')->applyFromArray($catStyle);

                $subStyle = array_merge($headerStyle, [
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => '5B9BD5'],
                    ],
                ]);
                $sheet->getStyle('A4:H4')->applyFromArray($subStyle);

                // 7. Bold the Total Column
                $highestRow = $sheet->getHighestRow();
                if ($highestRow > 4) {
                    $sheet->getStyle("H5:H{$highestRow}")->getFont()->setBold(true);
                }
            },
        ];
    }
}
