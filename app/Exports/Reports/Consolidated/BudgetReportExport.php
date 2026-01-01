<?php

declare(strict_types=1);

namespace App\Exports\Reports\Consolidated;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell; // Added
use Maatwebsite\Excel\Concerns\WithHeadings;       // Added (optional but recommended)
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class BudgetReportExport implements
    FromCollection,
    WithHeadings,          // Prevents any automatic heading row
    WithCustomStartCell,   // Allows data to start at A7
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

        return $this->projects->map(function ($project, $index) {
            return [
                $index + 1,                                            // A: क्र.सं. (S.N.) - starts from 1
                $project['title'] ?? '',                               // B: आयोजनाको नाम
                '',                                                    // C: बजेट संकेत नं.
                $project['gov_share'] ?? 0,                             // D: नेपाल सरकार - शेयर
                $project['gov_loan'] ?? 0,                              // E: नेपाल सरकार - ऋण
                $project['foreign_loan'] ?? 0,                         // F: वैदेशिक ऋण
                $project['foreign_loan_source'] ?? '',                 // G: source
                $project['grant'] ?? 0,                                // H: अनुदान
                $project['grant_source'] ?? '',                        // I: source
                $project['total_lmbis'] ?? 0,                          // J: जम्मा (LMBIS total)
                $project['nea_budget'] ?? 0,                           // K: ने.वि.प्रा.
                $project['grand_total'] ?? 0,                          // L: कुल जम्मा
            ];
        });
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
                if ($projects->count() > 0) {
                    $lastRow = 6 + $projects->count(); // Data ends at this row

                    // Borders for all data cells
                    $sheet->getStyle("A7:L{$lastRow}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);

                    // Right-align numeric columns
                    $sheet->getStyle("D7:L{$lastRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Left-align text columns: Project title and source columns
                    $sheet->getStyle("B7:B{$lastRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("G7:G{$lastRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("I7:I{$lastRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                    // === FIX FOR LONG PROJECT TITLES ===
                    // Enable text wrapping in column B
                    $sheet->getStyle("B7:B{$lastRow}")->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    // Set a generous fixed width for column B (Nepali text needs more space)
                    $sheet->getColumnDimension('B')->setWidth(70);

                    // Auto-fit row height so wrapped text is fully visible
                    for ($row = 7; $row <= $lastRow; $row++) {
                        $sheet->getRowDimension($row)->setRowHeight(-1); // -1 means auto
                    }
                }
            },
        ];
    }
}
