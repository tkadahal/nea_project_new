<?php

declare(strict_types=1);

namespace App\Exports\Reports\Consolidated\BudgetSheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BudgetSummaryReportExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithEvents, WithHeadings, WithStyles, WithTitle
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

    public function collection(): Collection
    {
        if ($this->projects->isEmpty()) {
            return collect([]);
        }

        // Group by Budget Heading
        $grouped = $this->projects->groupBy('budget_heading');
        $data = collect();
        $index = 1;

        foreach ($grouped as $headingTitle => $groupProjects) {
            // Get details from the first item
            $firstItem = $groupProjects->first();
            $headingCode = $firstItem['budget_heading_code'] ?? '';
            $headingDesc = $firstItem['budget_heading_description'] ?? '';

            // Calculate sums
            $sum_gov_share = $groupProjects->sum('gov_share');
            $sum_gov_loan = $groupProjects->sum('gov_loan');
            $sum_foreign_loan = $groupProjects->sum('foreign_loan');
            $sum_grant = $groupProjects->sum('grant');
            $sum_total_lmbis = $groupProjects->sum('total_lmbis');
            $sum_nea_budget = $groupProjects->sum('nea_budget');
            $sum_grand_total = $groupProjects->sum('grand_total');

            // Add ONLY the Summary Row
            $data->push([
                $this->toNepaliNumber($index++),                 // A: Serial
                trim("{$headingCode} {$headingDesc}"), // B: Code + Title + Desc
                trim("{$headingCode} {$headingTitle}"),                                               // C: Code (Optional, keeping empty for now)
                $this->toNepaliNumber($sum_gov_share),            // D
                $this->toNepaliNumber($sum_gov_loan),             // E
                $this->toNepaliNumber($sum_foreign_loan),        // F
                '',                                               // G
                $this->toNepaliNumber($sum_grant),                // H
                '',                                               // I
                $this->toNepaliNumber($sum_total_lmbis),          // J
                $this->toNepaliNumber($sum_nea_budget),           // K
                $this->toNepaliNumber($sum_grand_total),          // L
            ]);

            // INDIVIDUAL PROJECT LOOP REMOVED
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            4 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            5 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 60,   // Wide for Description
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

                // Calculate counts
                $numHeadings = $this->projects->groupBy('budget_heading')->count();
                $lastRow = 6 + $numHeadings;

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
                $sheet->setCellValue('B4', 'बजेट शीर्षक');
                $sheet->setCellValue('C4', 'बजेट संकेत नं.');
                $sheet->setCellValue('D4', 'वार्षिक बजेट (LMBIS)');
                $sheet->setCellValue('J4', 'जम्मा');
                $sheet->setCellValue('K4', 'ने.वि.प्रा.');
                $sheet->setCellValue('L4', 'कुल जम्मा');

                // Sub headers
                $sheet->mergeCells('D5:E5');
                $sheet->mergeCells('F5:G5');
                $sheet->mergeCells('H5:I5');
                $sheet->setCellValue('D5', 'नेपाल सरकार');
                $sheet->setCellValue('F5', 'वैदेशिक');
                $sheet->setCellValue('H5', 'अनुदान');

                // Third level headers
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

                $sheet->getStyle('A4:L6')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF4F81BD');

                $sheet->getStyle('A4:L6')->getFont()
                    ->setBold(true)
                    ->setSize(11)
                    ->getColor()->setARGB(Color::COLOR_WHITE);

                /* ================= DATA ROWS STYLING ================= */
                if ($numHeadings > 0) {
                    // Base border
                    $sheet->getStyle("A7:L{$lastRow}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);

                    for ($row = 7; $row <= $lastRow; $row++) {
                        $sheet->getRowDimension($row)->setRowHeight(-1);
                        $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true);

                        // Since we only have Summary Rows now, we apply the Summary Style to ALL rows
                        $sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'size' => 12,
                                'color' => ['argb' => Color::COLOR_BLACK],
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFE2EFDA'], // Light Green
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

                        // Right align numeric columns
                        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("D{$row}:L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                        // Indent the title
                        $sheet->getStyle("B{$row}")->getAlignment()->setIndent(1);
                    }
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Summary_report';
    }

    /* ── Helper: Convert to Nepali Digits ─────────────────────────────────── */
    private function toNepaliNumber(int|float|string $number): string
    {
        if (! is_numeric($number)) {
            $number = 0;
        }
        $digits = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];

        return preg_replace_callback('/\d/', fn ($m) => $digits[$m[0]], (string) $number);
    }
}
