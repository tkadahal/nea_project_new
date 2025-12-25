<?php

declare(strict_types=1);

namespace App\Exports\Templates;

use App\Models\Project;
use App\Models\FiscalYear;
use App\Models\ProjectActivityDefinition;
use App\Models\ProjectActivityPlan;
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
            ['प्रोजेक्ट क्रियाकलाप एक्सेल टेम्प्लेट'],
            [],
            ['निर्देशन:'],
            ['• पहेँलो रंग भएका कोषहरू (E–P) मा मात्र डाटा भर्नुहोस्।'],
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
            array_splice($baseInstructions, 6, 0, [ // Insert after "महत्वपूर्ण:"
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
        return ['A' => 70];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A3')->getFont()->setBold(true);
                $sheet->getStyle('A8')->getFont()->setBold(true);
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
        $row1 = array_fill(0, 16, '');
        $row1[0] = $this->project->title;
        $row1[7] = $this->fiscalYear->title;

        $headers = [
            'क्र.सं.',
            'कार्यक्रम/क्रियाकलाप',
            'कुल बजेट',
            '',
            'कुल खर्च',
            '',
            'वार्षिक बजेट',
            '',
            'Q1',
            '',
            'Q2',
            '',
            'Q3',
            '',
            'Q4',
            ''
        ];

        return collect([
            $row1,
            array_fill(0, 16, ''),
            $headers,
            array_fill(0, 16, ''),
            ...$this->getDataRows(),
            ['कुल जम्मा']
        ]);
    }

    private function getDataRows(): array
    {
        $rows = [];
        $index = 1;

        // Load current version definitions only
        $definitions = ProjectActivityDefinition::forProject($this->project->id)
            ->current()
            ->where('expenditure_id', $this->type === 'पूँजीगत' ? 1 : 2)
            ->topLevel()
            ->with('children.children')
            ->ordered()
            ->get();

        if ($definitions->isNotEmpty()) {
            $this->hasRealData = true;

            // Check if any plan exists for this project + fiscal year
            $existingPlan = ProjectActivityPlan::forProject($this->project->id)
                ->where('fiscal_year_id', $this->fiscalYear->id)
                ->active()
                ->first();

            if ($existingPlan) {
                // If any plan exists, use its status to decide protection
                $this->isDraft = ($existingPlan->status === 'draft');
            }
            // Else: no plan yet → treat as draft → $isDraft remains true
        }

        if ($definitions->isEmpty()) {
            return [
                [1, 'मुख्य कार्यक्रम उदाहरण', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
                ['1.1', 'उप कार्यक्रम उदाहरण', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ];
        }

        foreach ($definitions as $def) {
            $rows[] = [
                $index,
                $def->program,
                $def->total_quantity ?? 0,
                $def->total_budget ?? 0,
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
            ];

            $rows = array_merge($rows, $this->buildChildren($def->children, (string) $index));
            $index++;
        }

        return $rows;
    }

    private function buildChildren($children, string $parentCode): array
    {
        $rows = [];
        $i = 1;

        foreach ($children as $child) {
            $code = "{$parentCode}.{$i}";

            $rows[] = [
                $code,
                $child->program,
                $child->total_quantity ?? 0,
                $child->total_budget ?? 0,
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
            ];

            $rows = array_merge($rows, $this->buildChildren($child->children, $code));
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

                // Total row formulas (sum only main activities: no dot in column A)
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

                // Page setup, merges, styles, etc. (unchanged)
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1);

                $sheet->mergeCells('A3:A4');
                $sheet->mergeCells('B3:B4');
                foreach (['C3:D3', 'E3:F3', 'G3:H3', 'I3:J3', 'K3:L3', 'M3:N3', 'O3:P3'] as $r) {
                    $sheet->mergeCells($r);
                }

                $col = 3;
                for ($i = 0; $i < 7; $i++) {
                    $sheet->setCellValueByColumnAndRow($col, 4, 'परिमाण');
                    $sheet->setCellValueByColumnAndRow($col + 1, 4, 'रकम');
                    $col += 2;
                }

                $sheet->freezePane('C5');

                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF'], 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '1F4E79']],
                ];
                $sheet->getStyle('A3:P4')->applyFromArray($headerStyle);

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('H1')->getFont()->setBold(true)->setSize(14);

                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(3)->setRowHeight(25);
                $sheet->getRowDimension(4)->setRowHeight(35);
                for ($row = 5; $row <= $totalRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(22);
                }

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

                $validation = $sheet->getDataValidation('C5:P1000');
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
                    // Lock everything except input cells (E:P)
                    $sheet->getStyle('A1:P1')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
                    $sheet->getStyle('A5:D1000')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
                    $sheet->getStyle('E5:P1000')->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
                    $sheet->getStyle("C{$totalRow}:P{$totalRow}")->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

                    $sheet->getProtection()->setSheet(true);
                    $sheet->getProtection()->setPassword('nea-template');
                    $sheet->getProtection()->setInsertRows(false);
                    $sheet->getProtection()->setDeleteRows(false);
                    $sheet->getProtection()->setInsertColumns(false);
                    $sheet->getProtection()->setDeleteColumns(false);
                }
                // Else: fully editable (no protection) when:
                // - No real data OR
                // - Plan exists but status is 'draft'
            },
        ];
    }
}
