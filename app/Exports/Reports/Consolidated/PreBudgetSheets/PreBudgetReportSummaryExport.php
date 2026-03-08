<?php

namespace App\Exports\Reports\Consolidated\PreBudgetSheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithCustomStartCell, WithStyles, WithColumnWidths, WithEvents, ShouldAutoSize};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill, Color};
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Maatwebsite\Excel\Concerns\WithTitle;

class PreBudgetReportSummaryExport implements
    FromCollection,
    WithHeadings,
    WithCustomStartCell,
    WithStyles,
    WithColumnWidths,
    WithEvents,
    ShouldAutoSize,
    WithTitle
{
    protected Collection $projects;
    protected string $fiscalYear;

    public function __construct(Collection $projects, string $fiscalYear = '२०८१/८२')
    {
        $this->projects = $projects;
        $this->fiscalYear = $fiscalYear;
    }

    public function headings(): array
    {
        return [];
    }

    public function startCell(): string
    {
        return 'A7';
    }

    private function toNepaliNumber(int|float $number): string
    {
        $nepaliDigits = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
        return preg_replace_callback('/\d/', fn($m) => $nepaliDigits[$m[0]], (string) $number);
    }

    private function toNepaliAlphabet(int $index): string
    {
        $alphabets = [
            'क',
            'ख',
            'ग',
            'घ',
            'ङ',
            'च',
            'छ',
            'ज',
            'झ',
            'ञ',
            'ट',
            'ठ',
            'ड',
            'ढ',
            'ण',
            'त',
            'थ',
            'द',
            'ध',
            'न',
            'प',
            'फ',
            'ब',
            'भ',
            'म',
            'य',
            'र',
            'ल',
            'व',
            'श',
            'ष',
            'स',
            'ह',
        ];

        return $alphabets[$index - 1] ?? (string) $index;
    }

    public function collection(): Collection
    {
        if ($this->projects->isEmpty()) return collect([]);

        $rows = collect();
        $serial = 1;

        $this->projects
            ->groupBy('budget_heading')
            ->sortKeys()
            ->each(function ($byHeading, $headingTitle) use (&$rows, &$serial) {
                $firstItem = $byHeading->first();
                $headingCode = $firstItem['budget_heading_code'] ?? '';
                $headingDescription = $firstItem['budget_heading_description']
                    ?? $firstItem['description']
                    ?? $headingTitle;

                $groupTotal = $this->sumGroup($byHeading);

                $rows->push([
                    $this->toNepaliAlphabet($serial++),         // A: क, ख, ग ...
                    $headingDescription,
                    $headingCode . ' ' . $headingTitle,
                    $this->toNepaliNumber($groupTotal['gov_share']),
                    $this->toNepaliNumber($groupTotal['gov_loan']),
                    $this->toNepaliNumber($groupTotal['foreign_loan']),
                    '',
                    $this->toNepaliNumber($groupTotal['grant']),
                    '',
                    $this->toNepaliNumber($groupTotal['total_lmbis']),
                    $this->toNepaliNumber($groupTotal['nea_budget']),
                    $this->toNepaliNumber($groupTotal['grand_total']),
                ]);

                $byHeading->groupBy('directorate')->sortKeys()
                    ->each(function ($group, $directorateTitle) use (&$rows) {
                        $totals = $this->sumGroup($group);
                        $rows->push([
                            '',
                            $directorateTitle,
                            '',
                            $this->toNepaliNumber($totals['gov_share']),
                            $this->toNepaliNumber($totals['gov_loan']),
                            $this->toNepaliNumber($totals['foreign_loan']),
                            '',
                            $this->toNepaliNumber($totals['grant']),
                            '',
                            $this->toNepaliNumber($totals['total_lmbis']),
                            $this->toNepaliNumber($totals['nea_budget']),
                            $this->toNepaliNumber($totals['grand_total']),
                        ]);
                    });
            });

        return $rows;
    }

    private function sumGroup($group)
    {
        return $group->reduce(function ($carry, $item) {
            return [
                'gov_share'    => $carry['gov_share']    + ($item['gov_share'] ?? 0),
                'gov_loan'     => $carry['gov_loan']     + ($item['gov_loan'] ?? 0),
                'foreign_loan' => $carry['foreign_loan'] + ($item['foreign_loan'] ?? 0),
                'grant'        => $carry['grant']        + ($item['grant'] ?? 0),
                'total_lmbis'  => $carry['total_lmbis']  + ($item['total_lmbis'] ?? 0),
                'nea_budget'   => $carry['nea_budget']   + ($item['nea_budget'] ?? 0),
                'grand_total'  => $carry['grand_total']  + ($item['grand_total'] ?? 0),
            ];
        }, array_fill_keys(['gov_share', 'gov_loan', 'foreign_loan', 'grant', 'total_lmbis', 'nea_budget', 'grand_total'], 0));
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 50,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 12,
            'H' => 15,
            'I' => 12,
            'J' => 16,
            'K' => 15,
            'L' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rowCount = $this->collection()->count();
                $lastRow = 6 + $rowCount;

                /* TITLE & HEADERS */
                $sheet->mergeCells('A1:L1');
                $sheet->setCellValue('A1', 'नेपाल विद्युत प्राधिकरण');
                $sheet->mergeCells('A2:L2');
                $sheet->setCellValue('A2', "आयोजनाहरूको आ.व. {$this->fiscalYear} को पूर्वबजेट सारांश (Summary)");

                // Realignment: C is now the Directorate column
                $sheet->mergeCells('A4:A6'); // SN
                $sheet->mergeCells('B4:B6'); // Budget Heading
                $sheet->mergeCells('C4:C6'); // Directorate
                $sheet->mergeCells('D4:I4'); // Annual Budget Group
                $sheet->mergeCells('J4:J6'); // Total
                $sheet->mergeCells('K4:K6'); // NEA
                $sheet->mergeCells('L4:L6'); // Grand Total

                $sheet->setCellValue('A4', 'क्र.सं.');
                $sheet->setCellValue('B4', 'निर्देशनालय');
                $sheet->setCellValue('C4', 'बजेट शीर्षक');
                $sheet->setCellValue('D4', 'वार्षिक बजेट (LMBIS)');
                $sheet->setCellValue('J4', 'जम्मा');
                $sheet->setCellValue('K4', 'ने.वि.प्रा.');
                $sheet->setCellValue('L4', 'कुल जम्मा');

                $sheet->mergeCells('D5:E5');
                $sheet->mergeCells('F5:G5');
                $sheet->mergeCells('H5:I5');
                $sheet->setCellValue('D5', 'नेपाल सरकार');
                $sheet->setCellValue('F5', 'वैदेशिक');
                $sheet->setCellValue('H5', 'अनुदान');
                $sheet->setCellValue('D6', 'शेयर');
                $sheet->setCellValue('E6', 'ऋण');
                $sheet->setCellValue('F6', 'ऋण');
                $sheet->setCellValue('G6', 'Source');
                $sheet->setCellValue('H6', 'अनुदान');
                $sheet->setCellValue('I6', 'Source');

                /* STYLING HEADERS */
                $sheet->getStyle('A4:L6')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4F81BD']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);

                /* DATA STYLING & HIGHLIGHTING */
                for ($row = 7; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(-1);
                    $snValue = $sheet->getCell("A{$row}")->getValue();

                    // If SN is not empty, it's a "Budget Heading Total" row
                    if (!empty($snValue)) {
                        $sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']], // Light Green
                            'borders' => [
                                'top' => ['borderStyle' => Border::BORDER_MEDIUM],
                                'bottom' => ['borderStyle' => Border::BORDER_THIN]
                            ],
                        ]);

                        $sheet->getStyle("B{$row}")->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                            ->setIndent(0);
                    }

                    // Numeric alignment
                    $sheet->getStyle("D{$row}:L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    // General borders
                    $sheet->getStyle("A{$row}:L{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            2 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function title(): string
    {
        return 'Summary_budget_head_directorate';
    }
}
