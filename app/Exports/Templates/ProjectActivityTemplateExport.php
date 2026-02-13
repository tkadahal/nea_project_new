<?php

declare(strict_types=1);

namespace App\Exports\Templates;

use App\Models\Project;
use App\Models\FiscalYear;
use App\Models\ProjectActivityDefinition;
use App\Models\ProjectActivityPlan;
use App\Models\ProjectExpense;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ProjectActivityTemplateExport implements WithMultipleSheets
{
    public function __construct(
        private Project $project,
        private FiscalYear $fiscalYear,
        private bool $isNewVersion = false
    ) {}

    public static function getCurrentDefinitionCounts(Project $project): array
    {
        return [
            'capital'   => ProjectActivityDefinition::forProject($project->id)
                ->current()
                ->where('expenditure_id', 1)
                ->count(),

            'recurrent' => ProjectActivityDefinition::forProject($project->id)
                ->current()
                ->where('expenditure_id', 2)
                ->count(),
        ];
    }

    public function sheets(): array
    {
        return [
            'Instructions' => new InstructionsSheet($this->project, $this->fiscalYear, $this->isNewVersion),
            'पूँजीगत खर्च' => new ExpenditureSheet('पूँजीगत', $this->project, $this->fiscalYear, $this->isNewVersion),
            'चालू खर्च'     => new ExpenditureSheet('चालू', $this->project, $this->fiscalYear, $this->isNewVersion),
        ];
    }
}

class InstructionsSheet implements FromCollection, WithColumnWidths, WithEvents
{
    public function __construct(
        private Project $project,
        private FiscalYear $fiscalYear,
        private bool $isNewVersion = false
    ) {}

    public function collection()
    {
        $baseInstructions = [
            [$this->project->title, $this->project->id], // Row 1: Project Title
            [$this->fiscalYear->title, $this->fiscalYear->id],  // Row 2: Fiscal Year
            [], // Row 3: Spacer
            ['निर्देशन:'],
            ['• पहेँलो रंग भएका कोषहरू (G–P) मा मात्र डाटा भर्नुहोस्।'],
            ['• कार्यक्रम नाम, कुल बजेट तथा परिमाण परिवर्तन नगर्नुहोस्।'],
            ['• नयाँ पङ्क्ति थप्न वा हटाउन मिल्दैन (डाटा भएको अवस्थामा)।'],
            [],
            ['महत्वपूर्ण:'],
            ['• वार्षिक बजेट (H) = Q1+Q2+Q3+Q4 (J+L+N+P) — मैनुअल'],
            ['• अभिभावक पङ्क्ति = सन्तानहरूको योग — मैनुअल'],
            [],
            ['अपलोड अघि .xlsx ढाँचामा सुरक्षित गर्नुहोस्।'],
        ];

        if ($this->isNewVersion) {
            array_splice($baseInstructions, 6, 0, [
                [],
                ['यो नयाँ संस्करण (New Version) टेम्प्लेट हो।'],
                ['• तपाईंले कार्यक्रमहरू थप्न/हटाउन/सम्पादन गर्न सक्नुहुन्छ।'],
                ['• कुल बजेट र परिमाण पनि परिवर्तन गर्न सकिन्छ।'],
                ['• सबै कोषहरू खुला छन् — कुनै लक छैन।'],
            ]);
        }

        return collect($baseInstructions);
    }

    public function columnWidths(): array
    {
        return ['A' => 70, 'B' => 10];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Style Headers (Project & FY)
                // FIXED: getColor()->setARGB() instead of setColor(string)
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF000000');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF4F81BD');

                $sheet->getStyle('A4')->getFont()->setBold(true);
                $sheet->getStyle('A8')->getFont()->setBold(true);

                // Hide Column B so users don't see/edit IDs
                $sheet->getColumnDimension('B')->setVisible(false);

                // Protection: Lock entire sheet
                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setPassword('nea-template');
                $sheet->getProtection()->setSelectUnlockedCells(false);
            },
        ];
    }
}

class ExpenditureSheet implements FromCollection, WithTitle, WithColumnWidths, WithEvents
{
    private bool $hasRealData = false;
    private bool $isDraft = true;
    private bool $isNewVersion = false;

    public function __construct(
        private string $type,
        private Project $project,
        private FiscalYear $fiscalYear,
        bool $isNewVersion = false
    ) {}

    public function title(): string
    {
        return $this->type . ' खर्च';
    }

    public function collection()
    {
        // Row 1: Sheet Name Heading (Replaces Project/FY)
        $titleRow = array_fill(0, 16, '');
        $titleRow[0] = $this->title();

        // UPDATED HEADERS: Added Q1, Q2, Q3, Q4 after Annual Budget
        $headers = [
            'क्र.सं.',                // A (0)
            'कार्यक्रम/क्रियाकलाप', // B (1)
            'कुल क्रियाकलाप',             // C (2)
            0,                       // D (3) - Merged with C
            'कुल खर्च (गत आर्थिक वर्षसम्मको)',             // E (4)
            0,                       // F (5) - Merged with E
            'वार्षिक लक्ष्य',        // G (6)
            0,                       // H (7) - Merged with G
            'पहिलो त्रैमासिक',                    // I (8) - NEW
            0,                       // J (9) - Merged with I
            'दोस्रो त्रैमासिक',                    // K (10) - NEW
            0,                       // L (11) - Merged with K
            'तेस्रो त्रैमासिक',                    // M (12) - NEW
            0,                       // N (13) - Merged with M
            'चौथो त्रैमासिक',                    // O (14) - NEW
            0,                       // P (15) - Merged with O
        ];

        return collect([
            $titleRow,          // 1. Sheet Name
            array_fill(0, 16, 0), // 2. Spacer
            $headers,             // 3. Headers (Updated)
            array_fill(0, 16, 0), // 4. Spacer
            ...$this->getDataRows(),
            ['कुल जम्मा']
        ]);
    }

    private function getDataRows(): array
    {
        $rows = [];
        $index = 1;

        $definitions = ProjectActivityDefinition::forProject($this->project->id)
            ->current()
            ->where('expenditure_id', $this->type === 'पूँजीगत' ? 1 : 2)
            ->topLevel()
            ->with('children.children')
            ->ordered()
            ->get();

        if ($definitions->isNotEmpty()) {
            $this->hasRealData = true;

            $existingPlan = ProjectActivityPlan::forProject($this->project->id)
                ->where('fiscal_year_id', $this->fiscalYear->id)
                ->active()
                ->first();

            if ($existingPlan) {
                $this->isDraft = ($existingPlan->status === 'draft');
            }
        }

        // -----------------------------------------------------------------
        // FETCH PREVIOUS YEAR CUMULATIVE DATA
        // -----------------------------------------------------------------
        $previousFiscalYearId = $this->fiscalYear->id - 1;
        $previousDataMap = collect();

        if ($definitions->isNotEmpty() && $previousFiscalYearId > 0) {
            $definitionIds = $definitions->pluck('id');

            $previousPlans = ProjectActivityPlan::where('fiscal_year_id', $previousFiscalYearId)
                ->whereIn('activity_definition_version_id', $definitionIds)
                ->get()
                ->keyBy('activity_definition_version_id');

            $planIds = $previousPlans->pluck('id');
            $quarterSums = ProjectExpense::whereIn('project_activity_plan_id', $planIds)
                ->whereHas('quarters', fn($q) => $q->where('status', 'finalized'))
                ->withSum('quarters as total_amount', 'amount')
                ->withSum('quarters as total_quantity', 'quantity')
                ->get()
                ->keyBy('project_activity_plan_id');

            foreach ($previousPlans as $defId => $plan) {
                $baseExpense = (float) ($plan->total_expense ?? 0.0);
                $baseQuantity = (float) ($plan->completed_quantity ?? 0.0);

                $quarterExpense = (float) ($quarterSums->get($plan->id)?->total_amount ?? 0.0);
                $quarterQuantity = (float) ($quarterSums->get($plan->id)?->total_quantity ?? 0.0);

                $previousDataMap[$defId] = [
                    'expense' => $baseExpense + $quarterExpense,
                    'qty' => $baseQuantity + $quarterQuantity
                ];
            }
        }
        // -----------------------------------------------------------------

        if ($definitions->isEmpty()) {
            return [
                [1, 'मुख्य कार्यक्रम उदाहरण', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                ['1.1', 'उप कार्यक्रम उदाहरण', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            ];
        }

        foreach ($definitions as $def) {
            $prevData = $previousDataMap->get($def->id);
            $prevExpense = $prevData['expense'] ?? 0.0;
            $prevQty = $prevData['qty'] ?? 0.0;

            // Columns: A(0), B(1), C(2), D(3), E(4), F(5), G(6), H(7), I(8), J(9), K(10), L(11), M(12), N(13), O(14), P(15)
            // G:H = Annual Budget, I:J = Q1, K:L = Q2, M:N = Q3, O:P = Q4
            $rows[] = [
                $index,
                $def->program,
                $def->total_quantity ?? 0.0, // C (Total Qty)
                $def->total_budget ?? 0.0,   // D (Total Budget)
                $prevQty,                      // E (Prev Qty)
                $prevExpense,                   // F (Prev Exp)
                0, // G (Annual Qty)
                0, // H (Annual Amt)
                0, // I (Q1 Qty)
                0, // J (Q1 Amt)
                0, // K (Q2 Qty)
                0, // L (Q2 Amt)
                0, // M (Q3 Qty)
                0, // N (Q3 Amt)
                0, // O (Q4 Qty)
                0  // P (Q4 Amt)
            ];

            $rows = array_merge($rows, $this->buildChildren($def->children, (string) $index, $previousDataMap));
            $index++;
        }

        return $rows;
    }

    private function buildChildren($children, string $parentCode, $previousDataMap): array
    {
        $rows = [];
        $i = 1;

        foreach ($children as $child) {
            $code = "{$parentCode}.{$i}";

            $prevData = $previousDataMap->get($child->id);
            $prevExpense = $prevData['expense'] ?? 0.0;
            $prevQty = $prevData['qty'] ?? 0.0;

            $rows[] = [
                $code,
                $child->program,
                $child->total_quantity ?? 0.0,
                $child->total_budget ?? 0.0,
                $prevQty,
                $prevExpense,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0 // 10 zeros for Annual + Q1-Q4 inputs
            ];

            $rows = array_merge($rows, $this->buildChildren($child->children, $code, $previousDataMap));
            $i++;
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 40,
            'C' => 10,
            'D' => 12,
            'E' => 10,
            'F' => 12,
            'G' => 10,
            'H' => 12,
            'I' => 10,
            'J' => 12,
            'K' => 10,
            'L' => 12,
            'M' => 10,
            'N' => 12,
            'O' => 10,
            'P' => 12,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $totalRow = $highestRow;

                $sheet->getStyle('A5:A1000')
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

                // Style the New Title Row (Row 1)
                $sheet->mergeCells('A1:P1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => '1F4E79']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Total row formulas
                $columnsToSum = ['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
                foreach ($columnsToSum as $col) {
                    $colIndex = Coordinate::columnIndexFromString($col);
                    $formula = '=SUMPRODUCT(
                        (ISNUMBER(A5:INDEX(A:A,ROW()-1))) *
                        (ISERROR(SEARCH(".",A5:INDEX(A:A,ROW()-1)))),
                        ' . $col . '5:INDEX(' . $col . ':' . $col . ',ROW()-1)
                    )';
                    $sheet->setCellValueByColumnAndRow($colIndex, $totalRow, $formula);
                }

                // Page setup, merges
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1);

                $sheet->mergeCells('A3:A4');
                $sheet->mergeCells('B3:B4');

                // Merge headers for Total, Total Exp, Annual, Q1, Q2, Q3, Q4
                foreach (['C3:D3', 'E3:F3', 'G3:H3', 'I3:J3', 'K3:L3', 'M3:N3', 'O3:P3'] as $r) {
                    $sheet->mergeCells($r);
                }

                // Set subheaders (Qty/Amount) for all 7 pairs
                $col = 3;
                for ($i = 0; $i < 7; $i++) {
                    $sheet->setCellValueByColumnAndRow($col, 4, 'परिमाण');
                    $sheet->setCellValueByColumnAndRow($col + 1, 4, 'रकम');
                    $col += 2;
                }

                $sheet->freezePane('C5');

                // Header Styles (Now Row 3)
                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF'], 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '1F4E79']],
                ];
                $sheet->getStyle('A3:P4')->applyFromArray($headerStyle);

                // Row Styles
                for ($row = 5; $row <= $totalRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(22);
                }

                // Highlight inputs (Annual + Q1-Q4)
                $sheet->getStyle('E5:P1000')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFDE7');

                $totalStyle = [
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'A7C4E5']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ];
                $sheet->getStyle("A{$totalRow}:P{$totalRow}")->applyFromArray($totalStyle);

                for ($r = 5; $r < $totalRow; $r++) {
                    if ($r % 2 === 0) {
                        $sheet->getStyle("A{$r}:P{$r}")
                            ->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('F9FAFB');
                    }
                }

                $sheet->getStyle("A3:P{$totalRow}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Validation for input columns G-P
                $validation = $sheet->getDataValidation('G5:P1000');
                $validation->setType(DataValidation::TYPE_DECIMAL);
                $validation->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
                $validation->setFormula1(0);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(false);
                $validation->setShowInputMessage(false);
                $validation->setShowErrorMessage(true);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setErrorTitle('गलत इनपुट');
                $validation->setError('केवल अंक (numeric) मान मात्र अनुमति छ।');

                // === PROTECTION LOGIC ===
                $applyProtection = $this->hasRealData && !$this->isDraft;

                if ($applyProtection) {
                    $sheet->getProtection()->setSheet(true);
                    $sheet->getProtection()->setPassword('nea-template');

                    // Lock A-F (Structural + Historical), Unlock G-P (Inputs)
                    $sheet->getStyle('A1:F1000')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
                    $sheet->getStyle('G5:P1000')->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

                    // Lock Total Row
                    $sheet->getStyle("A{$totalRow}:P{$totalRow}")->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

                    $sheet->getProtection()->setInsertRows(false);
                    $sheet->getProtection()->setDeleteRows(false);
                    $sheet->getProtection()->setInsertColumns(false);
                    $sheet->getProtection()->setDeleteColumns(false);
                }
            },
        ];
    }
}
