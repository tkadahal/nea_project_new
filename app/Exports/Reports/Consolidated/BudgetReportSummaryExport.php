<?php

namespace App\Exports\Reports\Consolidated;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class BudgetReportSummaryExport implements
    FromCollection,
    WithHeadings,
    WithCustomStartCell,
    WithStyles,
    WithColumnWidths,
    WithEvents,
    ShouldAutoSize
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

    /**
     * Build rows with grouping: First row per group is the heading (merged later), subsequent are directorates
     */
    public function collection(): Collection
    {
        if ($this->projects->isEmpty()) {
            return collect([]);
        }

        $serial = 1;
        $rows = collect();

        $this->projects
            ->groupBy('budget_heading')
            ->sortKeys()
            ->each(function ($byHeading, $headingTitle) use (&$rows, &$serial) {
                // Assume all projects in a heading have the same code (budget_heading_code)
                $headingCode = $byHeading->first()['budget_heading_code'] ?? '';

                $directorates = $byHeading->groupBy('directorate')->sortKeys();

                $first = true;

                $directorates->each(function ($group, $directorateTitle) use (&$rows, &$serial, $headingTitle, $headingCode, &$first) {
                    $totals = $group->reduce(function ($carry, $item) {
                        return [
                            'gov_share'    => $carry['gov_share']    + $item['gov_share'],
                            'gov_loan'     => $carry['gov_loan']     + $item['gov_loan'],
                            'foreign_loan' => $carry['foreign_loan'] + $item['foreign_loan'],
                            'grant'        => $carry['grant']        + $item['grant'],
                            'total_lmbis'  => $carry['total_lmbis']  + $item['total_lmbis'],
                            'nea_budget'   => $carry['nea_budget']   + $item['nea_budget'],
                            'grand_total'  => $carry['grand_total']  + $item['grand_total'],
                        ];
                    }, array_fill_keys(['gov_share', 'gov_loan', 'foreign_loan', 'grant', 'total_lmbis', 'nea_budget', 'grand_total'], 0));

                    if ($first) {
                        // First row: Heading with code + full description in B, directorate in indented way if needed
                        $rows->push([
                            $serial++,
                            $headingCode . ' ' . $headingTitle,  // e.g., "50108101 १३२ के.भी तथा अन्य..."
                            $directorateTitle,
                            $totals['gov_share'],
                            $totals['gov_loan'],
                            $totals['foreign_loan'],
                            '',
                            $totals['grant'],
                            '',
                            $totals['total_lmbis'],
                            $totals['nea_budget'],
                            $totals['grand_total'],
                        ]);
                        $first = false;
                    } else {
                        // Subsequent rows: Blank in B, directorate in C
                        $rows->push([
                            $serial++,
                            '',  // Blank for heading
                            $directorateTitle,
                            $totals['gov_share'],
                            $totals['gov_loan'],
                            $totals['foreign_loan'],
                            '',
                            $totals['grant'],
                            '',
                            $totals['total_lmbis'],
                            $totals['nea_budget'],
                            $totals['grand_total'],
                        ]);
                    }
                });
            });

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            2 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            4 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]],
            5 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 70,
            'C' => 40,
            'D' => 16,
            'E' => 16,
            'F' => 16,
            'G' => 20,
            'H' => 16,
            'I' => 20,
            'J' => 18,
            'K' => 16,
            'L' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rowCount = $this->collection()->count();
                $lastRow = $rowCount > 0 ? 6 + $rowCount : 7;

                /* PAGE SETUP */
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4);

                /* TITLE - Same as detailed sheet */
                $sheet->mergeCells('A1:L1');
                $sheet->setCellValue('A1', 'नेपाल विद्युत प्राधिकरण');

                $sheet->mergeCells('A2:L2');
                $sheet->setCellValue('A2', "नेपाल सरकार तथा ने.वि.प्रा द्वारा संचालित आयोजनाहरूको आ.व. {$this->fiscalYear} को बजेट बोर्डफाईल");

                /* HEADER - EXACT SAME AS DETAILED SHEET */
                $sheet->mergeCells('A4:A5');
                $sheet->mergeCells('B4:B5');
                $sheet->mergeCells('C4:C5');
                $sheet->mergeCells('D4:I4');
                $sheet->mergeCells('J4:J5');
                $sheet->mergeCells('K4:K5');
                $sheet->mergeCells('L4:L5');

                $sheet->setCellValue('A4', 'क्र.सं.');
                $sheet->setCellValue('B4', 'आयोजनाको नाम');
                $sheet->setCellValue('C4', 'बजेट संकेत नं.');
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
                $sheet->setCellValue('G6', 'source');
                $sheet->setCellValue('H6', 'अनुदान');
                $sheet->setCellValue('I6', 'source');

                /* HEADER STYLING */
                $sheet->getStyle('A4:L6')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle('A4:L6')->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle('A4:L6')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF4F81BD');

                $sheet->getStyle('A4:L6')->getFont()
                    ->setBold(true)
                    ->setSize(11)
                    ->getColor()->setARGB(Color::COLOR_WHITE);

                /* DATA STYLING & GROUP MERGING */
                if ($rowCount > 0) {
                    $sheet->getStyle("A7:L{$lastRow}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);

                    $sheet->getStyle("D7:L{$lastRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $sheet->getStyle("B7:C{$lastRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                        ->setVertical(Alignment::VERTICAL_CENTER)
                        ->setWrapText(true);

                    // Merge column B for each budget heading group (visual grouping)
                    $currentRow = 7;
                    $groupStartRow = 7;
                    $previousHeading = $sheet->getCell('B7')->getValue();

                    for ($row = 8; $row <= $lastRow; $row++) {
                        $currentHeading = $sheet->getCell("B{$row}")->getValue();

                        if ($currentHeading !== '' || $row > $lastRow) {
                            // End of group
                            if ($groupStartRow < $row - 1) {
                                $sheet->mergeCells("B{$groupStartRow}:B" . ($row - 1));
                                $sheet->getStyle("B{$groupStartRow}:B" . ($row - 1))
                                    ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                            }
                            $groupStartRow = $row;
                            $previousHeading = $currentHeading;
                        }
                    }

                    // Last group
                    if ($groupStartRow <= $lastRow) {
                        $sheet->mergeCells("B{$groupStartRow}:B{$lastRow}");
                        $sheet->getStyle("B{$groupStartRow}:B{$lastRow}")
                            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    }

                    // Bold the heading text in merged cells
                    for ($row = 7; $row <= $lastRow; $row++) {
                        if ($sheet->getCell("B{$row}")->getValue() !== '') {
                            $sheet->getStyle("B{$row}")->getFont()->setBold(true);
                        }
                    }

                    // Auto row height
                    for ($row = 7; $row <= $lastRow; $row++) {
                        $sheet->getRowDimension($row)->setRowHeight(-1);
                    }
                }
            },
        ];
    }
}
