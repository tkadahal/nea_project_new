<?php

namespace App\Exports\Reports\Consolidated;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class AnnualProgramReportExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithTitle,
    WithEvents
{
    protected $fiscalYear;
    protected $quarter;
    protected $projects;
    protected $includeData;
    protected $rowCount;
    protected $directorateRows = []; // Track directorate header rows
    protected $totalRows = []; // Track total rows

    public function __construct(array $parameters = [])
    {
        $this->fiscalYear = $parameters['fiscal_year'] ?? '०८२/८३';
        $this->quarter = $parameters['quarter'] ?? 'प्रथम';
        $this->projects = $parameters['projects'] ?? [];
        $this->includeData = $parameters['include_data'] ?? false;
        $this->rowCount = $parameters['row_count'] ?? 10;
    }

    /**
     * Return collection of data for the Excel sheet
     */
    public function collection(): Collection
    {
        $data = collect();

        if ($this->includeData && !empty($this->projects)) {
            // Group projects by directorate
            $groupedProjects = collect($this->projects)->groupBy('directorate_id');

            $currentRow = 8; // Starting row after headers (rows 1-7)

            foreach ($groupedProjects as $directorateId => $directorateProjects) {
                // Add directorate header row
                $directorateName = $directorateProjects->first()['directorate_title'] ?? 'Unknown Directorate';
                $this->directorateRows[] = $currentRow;

                $data->push([
                    $directorateName, // This will span across columns
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    ''
                ]);
                $currentRow++;

                // Initialize totals for this directorate
                $totals = [
                    'budget_nepal_gov_contribution' => 0,
                    'budget_nepal_gov_loan' => 0,
                    'budget_foreign_loan' => 0,
                    'budget_foreign_grant' => 0,
                    'budget_total_nepal_gov' => 0,
                    'budget_nea' => 0,
                    'budget_total' => 0,
                    'expense_nepal_gov' => 0,
                    'expense_foreign_loan' => 0,
                    'expense_foreign_grant' => 0,
                    'expense_total_nepal_gov' => 0,
                    'expense_nea' => 0,
                    'expense_total' => 0,
                ];

                // Reset serial number for each directorate
                $directorateSerialNumber = 1;

                // Add project rows
                foreach ($directorateProjects as $project) {
                    $data->push([
                        $directorateSerialNumber++, // क्र.सं. - starts from 1 for each directorate
                        $project['title'] ?? '', // आयोजनाको नाम
                        $project['budget_heading'] ?? '', // बजेट शीर्षक नं.
                        $project['progress_percent'] ?? '',
                        // बजेट रु. हजार (7 columns)
                        $project['budget_nepal_gov_contribution'] ?? '',
                        $project['budget_nepal_gov_loan'] ?? '',
                        $project['budget_foreign_loan'] ?? '',
                        $project['budget_foreign_grant'] ?? '',
                        $project['budget_total_nepal_gov'] ?? '',
                        $project['budget_nea'] ?? '',
                        $project['budget_total'] ?? '',
                        // अवधिको खर्च रु. हजारमा (6 columns)
                        $project['expense_nepal_gov'] ?? '',
                        $project['expense_foreign_loan'] ?? '',
                        $project['expense_foreign_grant'] ?? '',
                        $project['expense_total_nepal_gov'] ?? '',
                        $project['expense_nea'] ?? '',
                        $project['expense_total'] ?? '',
                        // अवधि लक्ष्यको तुलनामा खर्च प्रतिशत (3 columns)
                        $project['target_nepal_gov_percent'] ?? '',
                        $project['target_nea_percent'] ?? '',
                        $project['target_total_percent'] ?? '',
                        // Additional columns (6 columns)
                        $project['semi_annual_total_expense'] ?? '',
                        $project['semi_annual_progress_percent'] ?? '',
                        $project['remarks'] ?? '',
                        $project['dhar'] ?? '',
                        $project['weighted_progress_1'] ?? '',
                        $project['weighted_progress_2'] ?? '',
                    ]);
                    $currentRow++;

                    // Accumulate totals
                    foreach ($totals as $key => $value) {
                        $totals[$key] += floatval($project[$key] ?? 0);
                    }
                }

                // Add total row for this directorate
                $this->totalRows[] = $currentRow;
                $data->push([
                    '',
                    'जम्मा:', // Total
                    '',
                    '',
                    // Budget totals
                    $totals['budget_nepal_gov_contribution'],
                    $totals['budget_nepal_gov_loan'],
                    $totals['budget_foreign_loan'],
                    $totals['budget_foreign_grant'],
                    $totals['budget_total_nepal_gov'],
                    $totals['budget_nea'],
                    $totals['budget_total'],
                    // Expense totals
                    $totals['expense_nepal_gov'],
                    $totals['expense_foreign_loan'],
                    $totals['expense_foreign_grant'],
                    $totals['expense_total_nepal_gov'],
                    $totals['expense_nea'],
                    $totals['expense_total'],
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ]);
                $currentRow++;
            }

            $this->rowCount = $currentRow - 8; // Update row count
        } else {
            // Add empty rows (26 columns: A-Z)
            for ($i = 1; $i <= $this->rowCount; $i++) {
                $data->push(array_fill(0, 26, ''));
            }
        }

        return $data;
    }

    /**
     * Define the headings (will be overridden by events)
     */
    public function headings(): array
    {
        return [];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 6,   // क्र.सं.
            'B' => 28,  // आयोजनाको नाम
            'C' => 12,  // बजेट शीर्षक नं.
            'D' => 10,  // भारित प्रगति प्रतिशत
            'E' => 12,  // नेपाल सरकार योगदान
            'F' => 12,  // नेपाल सरकार ऋण
            'G' => 10,  // वैदेशिक ऋण
            'H' => 12,  // वैदेशिक अनुदान
            'I' => 12,  // कुल नेपाल सरकार
            'J' => 10,  // ने.वि.प्रा.
            'K' => 10,  // जम्मा बजेट
            'L' => 12,  // नेपाल सरकार (खर्च)
            'M' => 10,  // वैदेशिक ऋण (खर्च)
            'N' => 12,  // वैदेशिक अनुदान (खर्च)
            'O' => 12,  // कुल नेपाल सरकार (खर्च)
            'P' => 10,  // ने.वि.प्रा. (खर्च)
            'Q' => 10,  // जम्मा खर्च
            'R' => 12,  // नेपाल सरकार प्रतिशत
            'S' => 10,  // ने.वि.प्रा. प्रतिशत
            'T' => 10,  // जम्मा प्रतिशत
            'U' => 13,  // अर्धवार्षिक तथा अवधिसम्मको कुल खर्च
            'V' => 13,  // अर्धवार्षिक तथा अवधि सम्मको प्रगति प्रतिशत
            'W' => 10,  // कैफियत
            'X' => 8,   // धार
            'Y' => 12,  // भारित प्रगति प्रतिशत
            'Z' => 12,  // भारित प्रगति प्रतिशत
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ],
            4 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
            ],
            5 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
            ],
            6 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
            ],
            7 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
            ],
        ];
    }

    /**
     * Set sheet title
     */
    public function title(): string
    {
        return 'त्रैमासिक प्रगति';
    }

    /**
     * Register events to customize the sheet
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Set default font
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Kalimati');
                $sheet->getParent()->getDefaultStyle()->getFont()->setSize(9);

                // Insert rows at the top for title and headers
                $sheet->insertNewRowBefore(1, 7);

                // ========== ROW 1: TITLE ==========
                $sheet->mergeCells('A1:Z1');
                $sheet->setCellValue('A1', 'नेपाल विद्युत प्राधिकरण');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension(1)->setRowHeight(18);

                // ========== ROW 2: SUBTITLE ==========
                $sheet->mergeCells('A2:Z2');
                $quarterText = $this->getQuarterText($this->quarter);
                $sheet->setCellValue('A2', "नेपाल सरकार तथा ने.वि.प्रा द्वारा संचालित आयोजनाहरुको आ.व.{$this->fiscalYear} को {$quarterText} त्रैमासिक प्रगति विवरण");
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('A2')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getRowDimension(2)->setRowHeight(18);

                // ========== ROW 3: EMPTY ==========
                $sheet->mergeCells('A3:Z3');

                // ========== ROW 4-7: HEADERS (same as before) ==========
                $sheet->mergeCells('A4:A7');
                $sheet->setCellValue('A4', 'क्र.सं.');

                $sheet->mergeCells('B4:B7');
                $sheet->setCellValue('B4', 'आयोजनाको नाम');

                $sheet->mergeCells('C4:C7');
                $sheet->setCellValue('C4', 'बजेट शीर्षक नं.');

                $sheet->mergeCells('D4:Z4');
                $sheet->setCellValue('D4', "{$quarterText} त्रैमासिक प्रगति विवरण");
                $sheet->getRowDimension(4)->setRowHeight(18);

                $sheet->mergeCells('D5:D7');
                $sheet->setCellValue('D5', 'भारित प्रगति प्रतिशत');

                $sheet->mergeCells('E5:K5');
                $sheet->setCellValue('E5', 'बजेट रु. हजार');

                $sheet->mergeCells('L5:Q5');
                $sheet->setCellValue('L5', 'अवधिको खर्च रु. हजारमा');

                $sheet->mergeCells('R5:T5');
                $sheet->setCellValue('R5', 'अवधि लक्ष्यको तुलनामा खर्च प्रतिशत');
                $sheet->getRowDimension(5)->setRowHeight(22);

                // Row 6-7 sub-headers
                $subHeaders = [
                    'E' => 'नेपाल सरकार योगदान',
                    'F' => 'नेपाल सरकार ऋण',
                    'G' => 'वैदेशिक ऋण',
                    'H' => 'वैदेशिक अनुदान',
                    'I' => 'कुल नेपाल सरकार',
                    'J' => 'ने.वि.प्रा.',
                    'K' => 'जम्मा बजेट',
                    'L' => 'नेपाल सरकार',
                    'M' => 'वैदेशिक ऋण',
                    'N' => 'वैदेशिक अनुदान',
                    'O' => 'कुल नेपाल सरकार',
                    'P' => 'ने.वि.प्रा.',
                    'Q' => 'जम्मा खर्च',
                    'R' => 'नेपाल सरकार',
                    'S' => 'ने.वि.प्रा.',
                    'T' => 'जम्मा',
                ];

                foreach ($subHeaders as $col => $header) {
                    $sheet->setCellValue("{$col}6", $header);
                    $sheet->mergeCells("{$col}6:{$col}7");
                }

                $sheet->setCellValue('U6', 'अर्धवार्षिक तथा अवधिसम्मको कुल खर्च (कुल चालानको प्रतिशत)');
                $sheet->mergeCells('U6:U7');

                $sheet->setCellValue('V6', 'अर्धवार्षिक तथा अवधि सम्मको प्रगति प्रतिशत');
                $sheet->mergeCells('V6:V7');

                $sheet->setCellValue('W6', 'कैफियत');
                $sheet->mergeCells('W6:W7');

                $sheet->setCellValue('X6', 'धार');
                $sheet->mergeCells('X6:X7');

                $sheet->setCellValue('Y6', 'भारित प्रगति प्रतिशत');
                $sheet->mergeCells('Y6:Y7');

                $sheet->setCellValue('Z6', 'भारित प्रगति प्रतिशत');
                $sheet->mergeCells('Z6:Z7');

                $sheet->getRowDimension(6)->setRowHeight(22);
                $sheet->getRowDimension(7)->setRowHeight(22);

                // ========== STYLE DIRECTORATE HEADERS ==========
                foreach ($this->directorateRows as $row) {
                    $sheet->mergeCells("A{$row}:Z{$row}");
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

                // ========== STYLE TOTAL ROWS ==========
                foreach ($this->totalRows as $row) {
                    $sheet->getStyle("B{$row}:Z{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFF2CC']
                        ],
                    ]);
                }

                // ========== APPLY BORDERS ==========
                $lastRow = 7 + $this->rowCount;
                $sheet->getStyle("A4:Z$lastRow")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // ========== CENTER ALIGN ALL CELLS ==========
                $sheet->getStyle("A4:Z$lastRow")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                // Left align project names (column B)
                $sheet->getStyle("B8:B$lastRow")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);
            },
        ];
    }

    /**
     * Get quarter text in Nepali
     */
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
