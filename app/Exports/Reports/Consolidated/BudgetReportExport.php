<?php

declare(strict_types=1);

namespace App\Exports\Reports\Consolidated;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class BudgetReportExport implements
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

    /**
     * No automatic headings - we build everything manually in AfterSheet
     */
    public function headings(): array
    {
        return [];
    }

    /**
     * Force the collection data to start exactly at cell A7
     */
    public function startCell(): string
    {
        return 'A7';
    }

    /**
     * Provide ONLY the data rows (no headings) - data will now start at row 7
     */
    public function collection(): Collection
    {
        if ($this->projects->isEmpty()) {
            // Return empty rows to keep structure (headers visible)
            return collect([]);
        }

        $grouped = $this->projects->groupBy('directorate');
        $data = collect();
        $index = 1;

        foreach ($grouped as $directorate => $groupProjects) {
            // Calculate sums for the directorate
            $sum_gov_share = $groupProjects->sum('gov_share');
            $sum_gov_loan = $groupProjects->sum('gov_loan');
            $sum_foreign_loan = $groupProjects->sum('foreign_loan');
            $sum_grant = $groupProjects->sum('grant');
            $sum_total_lmbis = $groupProjects->sum('total_lmbis');
            $sum_nea_budget = $groupProjects->sum('nea_budget');
            $sum_grand_total = $groupProjects->sum('grand_total');

            // Add directorate row with title and sums
            $data->push([
                '',                                               // A: empty
                $directorate,                                     // B: Directorate title
                '',                                               // C: empty
                $sum_gov_share,                                   // D: sum नेपाल सरकार - शेयर
                $sum_gov_loan,                                    // E: sum नेपाल सरकार - ऋण
                $sum_foreign_loan,                                // F: sum वैदेशिक ऋण
                '',                                               // G: empty (source)
                $sum_grant,                                       // H: sum अनुदान
                '',                                               // I: empty (source)
                $sum_total_lmbis,                                 // J: sum जम्मा (LMBIS total)
                $sum_nea_budget,                                  // K: sum ने.वि.प्रा.
                $sum_grand_total,                                 // L: sum कुल जम्मा
            ]);

            // Add project rows for this directorate
            foreach ($groupProjects as $project) {
                $data->push([
                    $index,                                           // A: क्र.सं. (S.N.) - starts from 1
                    $project['title'] ?? '',                          // B: आयोजनाको नाम
                    '',                                               // C: बजेट संकेत नं.
                    $project['gov_share'] ?? 0,                       // D: नेपाल सरकार - शेयर
                    $project['gov_loan'] ?? 0,                        // E: नेपाल सरकार - ऋण
                    $project['foreign_loan'] ?? 0,                    // F: वैदेशिक ऋण
                    $project['foreign_loan_source'] ?? '',            // G: source
                    $project['grant'] ?? 0,                           // H: अनुदान
                    $project['grant_source'] ?? '',                   // I: source
                    $project['total_lmbis'] ?? 0,                     // J: जम्मा (LMBIS total)
                    $project['nea_budget'] ?? 0,                      // K: ने.वि.प्रा.
                    $project['grand_total'] ?? 0,                     // L: कुल जम्मा
                ]);
                $index++;
            }
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            4 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
            5 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 60,   // Wide base for long Nepali titles
            'C' => 16,
            'D' => 14,
            'E' => 14,
            'F' => 14,
            'G' => 20,
            'H' => 14,
            'I' => 20,
            'J' => 16,
            'K' => 14,
            'L' => 16,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $projects = $this->projects;
                $grouped = $projects->groupBy('directorate');
                $numGroups = $grouped->count();

                /* ================= PAGE SETUP ================= */
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4);

                /* ================= TITLE ================= */
                $sheet->mergeCells('A1:L1');
                $sheet->setCellValue('A1', 'नेपाल विद्युत प्राधिकरण');

                $sheet->mergeCells('A2:L2');
                $sheet->setCellValue('A2', "नेपाल सरकार तथा ने.वि.प्रा द्वारा संचालित आयोजनाहरूको आ.व. {$this->fiscalYear} को बजेट बोर्डफाईल");

                /* ================= HEADER STRUCTURE ================= */
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

                // Sub headers (row 5)
                $sheet->mergeCells('D5:E5');
                $sheet->mergeCells('F5:G5');
                $sheet->mergeCells('H5:I5');

                $sheet->setCellValue('D5', 'नेपाल सरकार');
                $sheet->setCellValue('F5', 'वैदेशिक');
                $sheet->setCellValue('H5', 'अनुदान');

                // Third level headers (row 6)
                $sheet->setCellValue('D6', 'शेयर');
                $sheet->setCellValue('E6', 'ऋण');
                $sheet->setCellValue('F6', 'ऋण');
                $sheet->setCellValue('G6', 'source');
                $sheet->setCellValue('H6', 'अनुदान');
                $sheet->setCellValue('I6', 'source');

                /* ================= HEADER STYLING ================= */
                $sheet->getStyle('A4:L6')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle('A4:L6')->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Blue background + white bold text for headers
                $sheet->getStyle('A4:L6')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF4F81BD');

                $sheet->getStyle('A4:L6')->getFont()
                    ->setBold(true)
                    ->setSize(11)
                    ->getColor()->setARGB(Color::COLOR_WHITE);

                /* ================= DATA ROWS STYLING ================= */
                /* ================= DATA ROWS STYLING ================= */
                if ($projects->count() > 0) {
                    $lastRow = 6 + $projects->count() + $numGroups;

                    // Base styles for the whole data range
                    $sheet->getStyle("A7:L{$lastRow}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);

                    for ($row = 7; $row <= $lastRow; $row++) {
                        $sheet->getRowDimension($row)->setRowHeight(-1);
                        $valueA = $sheet->getCell("A{$row}")->getValue();

                        // Check if this is a Directorate (Summary) Row
                        // In your collection, Directorate rows have an empty column A
                        if (empty($valueA)) {
                            $sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
                                'font' => [
                                    'bold' => true,
                                    'size' => 12,
                                    'color' => ['argb' => Color::COLOR_BLACK],
                                ],
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['argb' => 'FFE2EFDA'], // Soft Professional Green
                                ],
                                'alignment' => [
                                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                                    'vertical' => Alignment::VERTICAL_CENTER,
                                ],
                                'borders' => [
                                    'top' => ['borderStyle' => Border::BORDER_MEDIUM],
                                    'bottom' => ['borderStyle' => Border::BORDER_MEDIUM],
                                ],
                            ]);

                            // Right-align the sums in the directorate row
                            $sheet->getStyle("D{$row}:L{$row}")->getAlignment()
                                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                            // Indent the Directorate title slightly for better hierarchy
                            $sheet->getStyle("B{$row}")->getAlignment()->setIndent(1);
                        } else {
                            // Regular Project Row Styling
                            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                            $sheet->getStyle("D{$row}:L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        }
                    }
                }
            },
        ];
    }
}
