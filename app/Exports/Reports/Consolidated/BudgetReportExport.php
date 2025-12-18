<?php

declare(strict_types=1);

namespace App\Exports\Reports\Consolidated;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class BudgetReportExport implements FromCollection, WithStyles, WithColumnWidths, WithEvents
{
    public function collection()
    {
        // Empty rows for template
        return collect([
            array_fill(0, 12, null),
        ]);
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
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
            5 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // क्र.सं.
            'B' => 35,  // आयोजनाको नाम
            'C' => 14,  // बजेट संकेत नं.
            'D' => 12,  // नेपाल सरकार - शेयर
            'E' => 12,  // नेपाल सरकार - ऋण
            'F' => 12,  // वैदेशिक ऋण
            'G' => 14,  // source
            'H' => 12,  // अनुदान
            'I' => 14,  // source
            'J' => 14,  // जम्मा
            'K' => 12,  // ने.वि.प्रा.
            'L' => 14,  // कुल जम्मा
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                /* ================= PAGE SETUP ================= */
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4);

                /* ================= TITLE ================= */
                $sheet->mergeCells('A1:L1');
                $sheet->setCellValue('A1', 'नेपाल विद्युत प्राधिकरण');

                $sheet->mergeCells('A2:L2');
                $sheet->setCellValue(
                    'A2',
                    'नेपाल सरकार तथा ने.वि.प्रा द्वारा संचालित आयोजनाहरूको आ.व. २०८१/८२ को बजेट बोर्डफाईल'
                );

                /* ================= HEADER STRUCTURE ================= */

                // Main headers
                $sheet->mergeCells('A4:A5');
                $sheet->mergeCells('B4:B5');
                $sheet->mergeCells('C4:C5');
                $sheet->mergeCells('D4:I4');
                $sheet->mergeCells('K4:K5');
                $sheet->mergeCells('L4:L5');

                $sheet->setCellValue('A4', 'क्र.सं.');
                $sheet->setCellValue('B4', 'आयोजनाको नाम');
                $sheet->setCellValue('C4', 'बजेट संकेत नं.');
                $sheet->setCellValue('D4', 'वार्षिक बजेट (LMBIS)');
                $sheet->setCellValue('K4', 'ने.वि.प्रा.');
                $sheet->setCellValue('L4', 'कुल जम्मा');

                // Sub headers under LMBIS
                $sheet->mergeCells('D5:E5');
                $sheet->mergeCells('F5:G5');
                $sheet->mergeCells('H5:I5');

                $sheet->setCellValue('D5', 'नेपाल सरकार');
                $sheet->setCellValue('F5', 'वैदेशिक');
                $sheet->setCellValue('H5', 'अनुदान');

                // Third row
                $sheet->setCellValue('D6', 'शेयर');
                $sheet->setCellValue('E6', 'ऋण');
                $sheet->setCellValue('F6', 'ऋण');
                $sheet->setCellValue('G6', 'source');
                $sheet->setCellValue('H6', 'अनुदान');
                $sheet->setCellValue('I6', 'source');
                $sheet->setCellValue('J6', 'जम्मा');

                $sheet->mergeCells('A6:A6');
                $sheet->mergeCells('B6:B6');
                $sheet->mergeCells('C6:C6');
                $sheet->mergeCells('K6:K6');
                $sheet->mergeCells('L6:L6');

                /* ================= ALIGNMENT ================= */
                $sheet->getStyle('A4:L6')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                /* ================= BORDERS ================= */
                $sheet->getStyle('A4:L6')->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }
        ];
    }
}
