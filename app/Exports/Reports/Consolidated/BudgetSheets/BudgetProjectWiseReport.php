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

class BudgetProjectWiseReport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected Collection $projects;

    protected string $fiscalYear;

    public function __construct(Collection $projects, string $fiscalYear = '२०८१/८२')
    {
        $this->projects = $projects;
        $this->fiscalYear = $fiscalYear;
    }

    public function title(): string
    {
        return 'Summary_directorate_project_detail';
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

        $rows = collect();
        $serial = 1;       // क, ख, ग... for budget heading
        $overall = 1;       // 1, 2, 3... overall project serial (column A)

        $this->projects
            ->groupBy('budget_heading')
            ->sortKeys()
            ->each(function ($byHeading, $headingTitle) use (&$rows, &$serial, &$overall) {
                $firstItem = $byHeading->first();
                $headingCode = $firstItem['budget_heading_code'] ?? '';
                $headingDescription = $firstItem['budget_heading_description']
                    ?? $firstItem['description']
                    ?? $headingTitle;
                $totals = $this->sumGroup($byHeading);

                // ── Budget Heading Row ──────────────────────────────────────────
                $rows->push([
                    '',                                         // A: empty
                    '',                                         // B: empty
                    $this->toNepaliAlphabet($serial++),         // C: क, ख, ग...
                    $headingDescription,        // D: heading title
                    $headingCode.' '.$headingTitle,         // E: heading code
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

                // ── Directorate Rows ────────────────────────────────────────────
                $byHeading
                    ->groupBy('directorate')
                    ->sortKeys()
                    ->each(function ($group, $directorateTitle) use (&$rows, &$overall) {
                        $dTotals = $this->sumGroup($group);
                        $innerSerial = 1; // 1, 2, 3... within directorate (col C)

                        // Directorate summary row
                        $rows->push([
                            '',                 // A
                            '',                 // B
                            '',                 // C
                            $directorateTitle,  // D: directorate name
                            '',                 // E
                            $this->toNepaliNumber($dTotals['gov_share']),
                            $this->toNepaliNumber($dTotals['gov_loan']),
                            $this->toNepaliNumber($dTotals['foreign_loan']),
                            '',
                            $this->toNepaliNumber($dTotals['grant']),
                            '',
                            $this->toNepaliNumber($dTotals['total_lmbis']),
                            $this->toNepaliNumber($dTotals['nea_budget']),
                            $this->toNepaliNumber($dTotals['grand_total']),
                        ]);

                        // Individual project rows
                        foreach ($group as $project) {
                            $rows->push([
                                $this->toNepaliNumber($overall++),              // A: overall serial
                                $this->toNepaliNumber($project['lmbis_serial'] ?? 0), // B: LMBIS serial
                                $this->toNepaliNumber($innerSerial++),          // C: within-directorate serial
                                $project['title'] ?? '',                         // D: project name
                                $project['budget_code'] ?? '',                   // E: per-project budget code
                                $this->toNepaliNumber($project['gov_share'] ?? 0),
                                $this->toNepaliNumber($project['gov_loan'] ?? 0),
                                $this->toNepaliNumber($project['foreign_loan'] ?? 0),
                                $project['foreign_loan_source'] ?? '',
                                $this->toNepaliNumber($project['grant'] ?? 0),
                                $project['grant_source'] ?? '',
                                $this->toNepaliNumber($project['total_lmbis'] ?? 0),
                                $this->toNepaliNumber($project['nea_budget'] ?? 0),
                                $this->toNepaliNumber($project['grand_total'] ?? 0),
                            ]);
                        }
                    });
            });

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 8,
            'C' => 6,
            'D' => 50,
            'E' => 12,
            'F' => 14,
            'G' => 14,
            'H' => 14,
            'I' => 18,
            'J' => 14,
            'K' => 18,
            'L' => 16,
            'M' => 14,
            'N' => 16,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            2 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rowCount = $this->collection()->count();
                $lastRow = 6 + $rowCount;

                /* ── PAGE SETUP ─────────────────────────────────────────────── */
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4);

                /* ── TITLES ─────────────────────────────────────────────────── */
                $sheet->mergeCells('A1:N1');
                $sheet->setCellValue('A1', 'नेपाल विद्युत प्राधिकरण');

                $sheet->mergeCells('A2:N2');
                $sheet->setCellValue('A2', "नेपाल सरकार तथा ने.वि.प्रा द्वारा संचालित आयोजनाहरूको आ.व. {$this->fiscalYear} को बजेट बाँडफाँड");

                // "(रु. हजारमा)" label — top right, row 3
                $sheet->mergeCells('K3:N3');
                $sheet->setCellValue('K3', '(रु. हजारमा)');
                $sheet->getStyle('K3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('K3')->getFont()->setBold(true);

                /* ── HEADER ROWS 4–6 ────────────────────────────────────────── */
                // Row 4: top-level merges
                $sheet->mergeCells('A4:A6');   // क्र.सं.
                $sheet->mergeCells('B4:B6');   // LM BIS क्र.सं.
                $sheet->mergeCells('C4:C6');   // क्र.सं. (within directorate)
                $sheet->mergeCells('D4:D6');   // आयोजनाको नाम
                $sheet->mergeCells('E4:E6');   // बजेट शीर्षक नं.
                $sheet->mergeCells('F4:L4');   // वार्षिक बजेट (LMBIS)
                $sheet->mergeCells('M4:M6');   // ने.वि.प्रा.
                $sheet->mergeCells('N4:N6');   // कुल जम्मा

                $sheet->setCellValue('A4', 'क्र.सं.');
                $sheet->setCellValue('B4', 'LM BIS क्र.सं.');
                $sheet->setCellValue('C4', 'क्र.सं.');
                $sheet->setCellValue('D4', 'आयोजनाको नाम');
                $sheet->setCellValue('E4', 'बजेट शीर्षक नं.');
                $sheet->setCellValue('F4', 'वार्षिक बजेट (LMBIS)');
                $sheet->setCellValue('M4', 'ने.वि.प्रा.');
                $sheet->setCellValue('N4', 'कुल जम्मा');

                // Row 5: sub-group merges
                $sheet->mergeCells('F5:G5');   // नेपाल सरकार
                $sheet->mergeCells('H5:I5');   // वैदेशिक
                $sheet->mergeCells('J5:K5');   // अनुदान
                $sheet->mergeCells('L5:L6');   // जम्मा

                $sheet->setCellValue('F5', 'नेपाल सरकार');
                $sheet->setCellValue('H5', 'वैदेशिक');
                $sheet->setCellValue('J5', 'अनुदान');
                $sheet->setCellValue('L5', 'जम्मा');

                // Row 6: leaf headers
                $sheet->setCellValue('F6', 'शेयर');
                $sheet->setCellValue('G6', 'ऋण');
                $sheet->setCellValue('H6', 'ऋण');
                $sheet->setCellValue('I6', 'source');
                $sheet->setCellValue('J6', 'अनुदान');
                $sheet->setCellValue('K6', 'source');

                /* ── HEADER STYLING ─────────────────────────────────────────── */
                $sheet->getStyle('A4:N6')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => Color::COLOR_WHITE]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4F81BD']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                /* ── DATA ROW STYLING ───────────────────────────────────────── */
                if ($rowCount > 0) {
                    $sheet->getStyle("A7:N{$lastRow}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);

                    for ($row = 7; $row <= $lastRow; $row++) {
                        $sheet->getRowDimension($row)->setRowHeight(-1);
                        $sheet->getStyle("D{$row}")->getAlignment()->setWrapText(true);

                        $valA = $sheet->getCell("A{$row}")->getValue();
                        $valC = $sheet->getCell("C{$row}")->getValue();

                        // Budget Heading Row: A and B empty, C has alphabet
                        if (empty($valA) && empty($valC) && ! empty($sheet->getCell("D{$row}")->getValue())) {
                            // Directorate Row
                            $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                                'font' => ['bold' => true, 'size' => 11],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']],
                                'borders' => [
                                    'top' => ['borderStyle' => Border::BORDER_MEDIUM],
                                    'bottom' => ['borderStyle' => Border::BORDER_MEDIUM],
                                ],
                                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                            ]);
                            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
                            $sheet->getStyle("F{$row}:N{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        } elseif (! empty($valC) && empty($valA)) {
                            // Budget Heading Row (has alphabet in C, no overall serial in A)
                            $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                                'font' => ['bold' => true],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFBDD7EE']],
                                'borders' => [
                                    'top' => ['borderStyle' => Border::BORDER_MEDIUM],
                                    'bottom' => ['borderStyle' => Border::BORDER_THIN],
                                ],
                                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                            ]);
                            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                            $sheet->getStyle("F{$row}:N{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        } else {
                            // Regular Project Row
                            $sheet->getStyle("A{$row}:C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                            $sheet->getStyle("F{$row}:N{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        }
                    }
                }
            },
        ];
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    private function toNepaliNumber(int|float|string $number): string
    {
        // Check if the value is numeric, otherwise default to 0
        if (! is_numeric($number)) {
            $number = 0;
        }

        $digits = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];

        // Cast to string so the regex works, and convert digits
        return preg_replace_callback('/\d/', fn ($m) => $digits[$m[0]], (string) $number);
    }

    private function toNepaliAlphabet(int $index): string
    {
        $alpha = [
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

        return $alpha[$index - 1] ?? (string) $index;
    }

    private function sumGroup(Collection $group): array
    {
        return $group->reduce(fn ($carry, $item) => [
            'gov_share' => $carry['gov_share'] + ($item['gov_share'] ?? 0),
            'gov_loan' => $carry['gov_loan'] + ($item['gov_loan'] ?? 0),
            'foreign_loan' => $carry['foreign_loan'] + ($item['foreign_loan'] ?? 0),
            'grant' => $carry['grant'] + ($item['grant'] ?? 0),
            'total_lmbis' => $carry['total_lmbis'] + ($item['total_lmbis'] ?? 0),
            'nea_budget' => $carry['nea_budget'] + ($item['nea_budget'] ?? 0),
            'grand_total' => $carry['grand_total'] + ($item['grand_total'] ?? 0),
        ], array_fill_keys(['gov_share', 'gov_loan', 'foreign_loan', 'grant', 'total_lmbis', 'nea_budget', 'grand_total'], 0));
    }
}
