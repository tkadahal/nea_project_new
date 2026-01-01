<?php

declare(strict_types=1);

namespace App\Exports\Reports\Consolidated;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Collection;

class AnnualProgramReportExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithTitle,
    WithEvents,
    WithColumnFormatting
{
    protected $fiscalYear;
    protected $quarter;
    protected $projects;
    protected $includeData;
    protected $rowCount;
    protected $directorateRows = [];
    protected $totalRows = [];

    public function __construct(array $parameters = [])
    {
        $this->fiscalYear = $parameters['fiscal_year'] ?? '०८२/८३';
        $this->quarter = $parameters['quarter'] ?? 'प्रथम';
        $this->projects = $parameters['projects'] ?? [];
        $this->includeData = $parameters['include_data'] ?? false;
        $this->rowCount = $parameters['row_count'] ?? 10;
    }

    public function collection(): Collection
    {
        $data = collect();

        if ($this->includeData && !empty($this->projects)) {
            $groupedProjects = collect($this->projects)->groupBy('directorate_id');
            $currentRow = 8;

            foreach ($groupedProjects as $directorateId => $directorateProjects) {
                $directorateName = $directorateProjects->first()['directorate_title'] ?? 'Unknown Directorate';
                $this->directorateRows[] = $currentRow;

                // Directorate header row (will be merged later)
                $data->push(array_fill(0, 27, ''));
                $data->last()[0] = $directorateName;
                $currentRow++;

                // Initialize totals
                $totals = [
                    'budget_nepal_gov_contribution' => 0,
                    'budget_nepal_gov_loan' => 0,
                    'budget_foreign_loan' => 0,
                    'budget_foreign_grant' => 0,
                    'budget_total_nepal_gov' => 0,
                    'budget_total_foreign' => 0,
                    'budget_nea' => 0,
                    'budget_total' => 0,

                    'expense_nepal_gov' => 0,
                    'expense_foreign_loan' => 0,
                    'expense_foreign_grant' => 0,
                    'expense_total_nepal_gov' => 0,
                    'expense_total_foreign' => 0,
                    'expense_nea' => 0,
                    'expense_total' => 0,
                ];

                $directorateSerialNumber = 1;

                foreach ($directorateProjects as $project) {
                    // Calculate foreign totals
                    $budget_foreign = (floatval($project['budget_foreign_loan'] ?? 0) + floatval($project['budget_foreign_grant'] ?? 0));
                    $expense_foreign = (floatval($project['expense_foreign_loan'] ?? 0) + floatval($project['expense_foreign_grant'] ?? 0));

                    $data->push([
                        $directorateSerialNumber++, // A
                        $project['title'] ?? '', // B
                        $project['budget_heading'] ?? '', // C
                        $project['progress_percent'] ?? '', // D

                        // Budget (E to L: 8 columns)
                        $project['budget_nepal_gov_contribution'] ?? '',
                        $project['budget_nepal_gov_loan'] ?? '',
                        $project['budget_foreign_loan'] ?? '',
                        $project['budget_foreign_grant'] ?? '',
                        $budget_foreign, // I: कुल वैदेशिक
                        $project['budget_total_nepal_gov'] ?? '',
                        $project['budget_nea'] ?? '',
                        $project['budget_total'] ?? '',

                        // Expense (M to S: 7 columns)
                        $project['expense_nepal_gov'] ?? '',
                        $project['expense_foreign_loan'] ?? '',
                        $project['expense_foreign_grant'] ?? '',
                        $expense_foreign, // P: कुल वैदेशिक खर्च
                        $project['expense_total_nepal_gov'] ?? '',
                        $project['expense_nea'] ?? '',
                        $project['expense_total'] ?? '',

                        // Percentages (T to V)
                        ($project['target_nepal_gov_percent'] ?? 0) / 100,
                        ($project['target_nea_percent'] ?? 0) / 100,
                        ($project['target_total_percent'] ?? 0) / 100,

                        // Additional (W to AA)
                        $project['semi_annual_total_expense'] ?? '',
                        $project['semi_annual_progress_percent'] ?? '',
                        $project['dhar'] ?? '',
                        $project['weighted_progress_1'] ?? '',
                        $project['weighted_progress_2'] ?? '',
                        $project['remarks'] ?? '', // AA: कैफियत (only remaining one)
                    ]);
                    $currentRow++;

                    // Accumulate totals
                    $keys = array_keys($totals);
                    foreach ($keys as $key) {
                        if (isset($project[$key])) {
                            $totals[$key] += floatval($project[$key]);
                        }
                    }
                    $totals['budget_total_foreign'] += $budget_foreign;
                    $totals['expense_total_foreign'] += $expense_foreign;
                }

                // Directorate total row
                $this->totalRows[] = $currentRow;
                $data->push([
                    '',
                    'जम्मा:',
                    '',
                    '',
                    $totals['budget_nepal_gov_contribution'],
                    $totals['budget_nepal_gov_loan'],
                    $totals['budget_foreign_loan'],
                    $totals['budget_foreign_grant'],
                    $totals['budget_total_foreign'],
                    $totals['budget_total_nepal_gov'],
                    $totals['budget_nea'],
                    $totals['budget_total'],

                    $totals['expense_nepal_gov'],
                    $totals['expense_foreign_loan'],
                    $totals['expense_foreign_grant'],
                    $totals['expense_total_foreign'],
                    $totals['expense_total_nepal_gov'],
                    $totals['expense_nea'],
                    $totals['expense_total'],

                    '',
                    '',
                    '', // T-V
                    '',
                    '',
                    '',
                    '',
                    '', // W-AA
                ]);
                $currentRow++;
            }

            $this->rowCount = $currentRow - 8;
        } else {
            for ($i = 1; $i <= $this->rowCount; $i++) {
                $data->push(array_fill(0, 27, ''));
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function columnFormats(): array
    {
        return [
            'T' => NumberFormat::FORMAT_PERCENTAGE_00,
            'U' => NumberFormat::FORMAT_PERCENTAGE_00,
            'V' => NumberFormat::FORMAT_PERCENTAGE_00,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 28,
            'C' => 12,
            'D' => 10,
            'E' => 12,
            'F' => 12,
            'G' => 10,
            'H' => 12,
            'I' => 12,  // कुल वैदेशिक (budget)
            'J' => 12,
            'K' => 10,
            'L' => 12,
            'M' => 12,
            'N' => 10,
            'O' => 12,
            'P' => 12,  // कुल वैदेशिक (expense)
            'Q' => 12,
            'R' => 10,
            'S' => 12,
            'T' => 12,
            'U' => 10,
            'V' => 10,
            'W' => 13,
            'X' => 13,
            'Y' => 8,
            'Z' => 12,
            'AA' => 12, // कैफियत (remaining one)
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true]],
            4 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']]],
            5 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']]],
            6 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']]],
            7 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']]],
        ];
    }

    public function title(): string
    {
        return 'त्रैमासिक प्रगति';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Set default font for the entire workbook
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Kalimati')->setSize(9);

                // Insert 7 rows at the top for title and headers
                $sheet->insertNewRowBefore(1, 7);

                // ==================== ROW 1: MAIN TITLE ====================
                $sheet->mergeCells('A1:AA1');
                $sheet->setCellValue('A1', 'नेपाल विद्युत प्राधिकरण');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension(1)->setRowHeight(18);

                // ==================== ROW 2: SUBTITLE ====================
                $quarterText = $this->getQuarterText($this->quarter);
                $sheet->mergeCells('A2:AA2');
                $sheet->setCellValue('A2', "नेपाल सरकार तथा ने.वि.प्रा द्वारा संचालित आयोजनाहरुको आ.व.{$this->fiscalYear} को {$quarterText} त्रैमासिक प्रगति विवरण");
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('A2')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getRowDimension(2)->setRowHeight(18);

                // ==================== ROW 3: EMPTY ====================
                $sheet->mergeCells('A3:AA3');
                $sheet->getRowDimension(3)->setRowHeight(15);

                // ==================== MAIN HEADERS (ROWS 4-7) ====================
                $sheet->mergeCells('A4:A7');
                $sheet->setCellValue('A4', 'क्र.सं.');

                $sheet->mergeCells('B4:B7');
                $sheet->setCellValue('B4', 'आयोजनाको नाम');

                $sheet->mergeCells('C4:C7');
                $sheet->setCellValue('C4', 'बजेट शीर्षक नं.');

                $sheet->mergeCells('D4:AA4');
                $sheet->setCellValue('D4', "{$quarterText} त्रैमासिक प्रगति विवरण");

                $sheet->mergeCells('D5:D7');
                $sheet->setCellValue('D5', 'भारित प्रगति प्रतिशत');

                $sheet->mergeCells('E5:L5');
                $sheet->setCellValue('E5', 'बजेट रु. हजार');

                $sheet->mergeCells('M5:S5');
                $sheet->setCellValue('M5', 'अवधिको खर्च रु. हजारमा');

                $sheet->mergeCells('T5:V5');
                $sheet->setCellValue('T5', 'अवधि लक्ष्यको तुलनामा खर्च प्रतिशत');

                // ==================== SUB-HEADERS (ROW 6-7) ====================
                $subHeaders = [
                    'E' => 'नेपाल सरकार योगदान',
                    'F' => 'नेपाल सरकार ऋण',
                    'G' => 'वैदेशिक ऋण',
                    'H' => 'वैदेशिक अनुदान',
                    'I' => 'कुल वैदेशिक',
                    'J' => 'कुल नेपाल सरकार',
                    'K' => 'ने.वि.प्रा.',
                    'L' => 'जम्मा बजेट',
                    'M' => 'नेपाल सरकार',
                    'N' => 'वैदेशिक ऋण',
                    'O' => 'वैदेशिक अनुदान',
                    'P' => 'कुल वैदेशिक',
                    'Q' => 'कुल नेपाल सरकार',
                    'R' => 'ने.वि.प्रा.',
                    'S' => 'जम्मा खर्च',
                    'T' => 'नेपाल सरकार',
                    'U' => 'ने.वि.प्रा.',
                    'V' => 'जम्मा',
                ];

                foreach ($subHeaders as $col => $header) {
                    $sheet->setCellValue("{$col}6", $header);
                    $sheet->mergeCells("{$col}6:{$col}7");
                }

                // Additional columns (removed Y)
                $sheet->setCellValue('W6', 'अर्धवार्षिक तथा अवधिसम्मको कुल खर्च (कुल चालानको प्रतिशत)');
                $sheet->mergeCells('W6:W7');

                $sheet->setCellValue('X6', 'अर्धवार्षिक तथा अवधि सम्मको प्रगति प्रतिशत');
                $sheet->mergeCells('X6:X7');

                $sheet->setCellValue('Y6', 'भार');
                $sheet->mergeCells('Y6:Y7');

                $sheet->setCellValue('Z6', 'भारित प्रगति प्रतिशत');
                $sheet->mergeCells('Z6:Z7');

                $sheet->setCellValue('AA6', 'कैफियत');
                $sheet->mergeCells('AA6:AA7');

                // Row heights for header section
                $sheet->getRowDimension(4)->setRowHeight(18);
                $sheet->getRowDimension(5)->setRowHeight(22);
                $sheet->getRowDimension(6)->setRowHeight(22);
                $sheet->getRowDimension(7)->setRowHeight(22);

                // ==================== STYLE DIRECTORATE HEADERS ====================
                foreach ($this->directorateRows as $row) {
                    $sheet->mergeCells("A{$row}:AA{$row}");
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'D9E1F2']
                        ],
                    ]);
                }

                // ==================== STYLE TOTAL ROWS ====================
                foreach ($this->totalRows as $row) {
                    $sheet->getStyle("B{$row}:AA{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFF2CC']
                        ],
                    ]);
                }

                // ==================== BORDERS & ALIGNMENT ====================
                $lastRow = 7 + $this->rowCount;

                $sheet->getStyle("A4:AA{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getStyle("A4:AA{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                // Left align project names in column B
                $sheet->getStyle("B8:B{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);
            },
        ];
    }

    protected function getQuarterText($quarter): string
    {
        $quarters = [
            'first' => 'प्रथम',
            'second' => 'दोस्रो',
            'third' => 'तेस्रो',
            'fourth' => 'चौथो',
            'प्रथम' => 'प्रथम',
            'दोस्रो' => 'दोस्रो',
            'तेस्रो' => 'तेस्रो',
            'चौथो' => 'चौथो',
        ];
        return $quarters[$quarter] ?? 'प्रथम';
    }
}
