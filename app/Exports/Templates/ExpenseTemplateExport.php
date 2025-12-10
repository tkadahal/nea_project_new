<?php

declare(strict_types=1);

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;
use App\Models\Project;
use App\Models\FiscalYear;
use App\Models\ProjectActivityDefinition;
use App\Models\ProjectActivityPlan;
use App\Models\ProjectExpense;

class ExpenseTemplateExport implements FromCollection, ShouldAutoSize, WithTitle, WithEvents
{
    protected $projectTitle;
    protected $fiscalTitle;
    protected $projectId;
    protected $fiscalYearId;
    protected $quarter;
    protected $quarterKey;
    protected $dataOffset = 6;
    protected $totalRanges;
    protected $rows; // NEW: For accessing in events to set plan IDs

    public function __construct($projectTitle, $fiscalTitle, $projectId, $fiscalYearId, $quarter)
    {
        $this->projectTitle = $projectTitle;
        $this->fiscalTitle = $fiscalTitle;
        $this->projectId = $projectId;
        $this->fiscalYearId = $fiscalYearId;
        $this->quarter = (int) $quarter;
        $this->quarterKey = 'q' . $this->quarter;
    }

    public function collection()
    {
        $this->totalRanges = collect();

        $project = Project::findOrFail($this->projectId);
        $fiscalYear = FiscalYear::findOrFail($this->fiscalYearId);
        $quarterNumber = $this->quarter;

        // Load capital definitions and plans
        $capitalDefinitions = ProjectActivityDefinition::forProject($project->id)
            ->whereNull('parent_id')
            ->where('expenditure_id', 1)
            ->active()
            ->with('children.children.children')
            ->get();

        $capitalDefIds = $capitalDefinitions->flatMap(function ($def) {
            return collect([$def])->merge($def->getDescendants());
        })->pluck('id')->unique();

        $capitalPlans = ProjectActivityPlan::whereIn('activity_definition_id', $capitalDefIds)
            ->where('fiscal_year_id', $fiscalYear->id)
            ->active()
            ->get()
            ->keyBy('activity_definition_id');

        $capitalDefToPlanMap = $capitalPlans->pluck('id', 'activity_definition_id')->toArray();

        // Load expenses for capital
        $capitalPlanIds = array_values($capitalDefToPlanMap);
        $capitalExpenses = ProjectExpense::whereIn('project_activity_plan_id', $capitalPlanIds)
            ->with(['quarters' => function ($q) use ($quarterNumber) {
                $q->where('quarter', $quarterNumber);
            }])
            ->get()
            ->keyBy('project_activity_plan_id');

        // Load recurrent definitions and plans
        $recurrentDefinitions = ProjectActivityDefinition::forProject($project->id)
            ->whereNull('parent_id')
            ->where('expenditure_id', 2)
            ->active()
            ->with('children.children.children')
            ->get();

        $recurrentDefIds = $recurrentDefinitions->flatMap(function ($def) {
            return collect([$def])->merge($def->getDescendants());
        })->pluck('id')->unique();

        $recurrentPlans = ProjectActivityPlan::whereIn('activity_definition_id', $recurrentDefIds)
            ->where('fiscal_year_id', $fiscalYear->id)
            ->active()
            ->get()
            ->keyBy('activity_definition_id');

        $recurrentDefToPlanMap = $recurrentPlans->pluck('id', 'activity_definition_id')->toArray();

        // Load expenses for recurrent
        $recurrentPlanIds = array_values($recurrentDefToPlanMap);
        $recurrentExpenses = ProjectExpense::whereIn('project_activity_plan_id', $recurrentPlanIds)
            ->with(['quarters' => function ($q) use ($quarterNumber) {
                $q->where('quarter', $quarterNumber);
            }])
            ->get()
            ->keyBy('project_activity_plan_id');

        // Build rows
        $rows = new Collection();

        // Capital section header (10 elements, planId null)
        $rows->push([null, 'पूँजीगत कार्यक्रमहरू', null, null, null, null, null, null, -2, null]);

        // Capital tree rows
        $nextTopNumCapital = 1;
        $capitalReturn = $this->appendTreeRows($capitalDefinitions, $capitalPlans, $fiscalYear->id, $capitalDefToPlanMap, $capitalExpenses, $rows, $quarterNumber, [], $nextTopNumCapital, $this->totalRanges);

        // Capital subtotal row (serial blank, planId null)
        $capitalTotalCollRow = $rows->count() + 1;
        $capitalQtyFormula = '=SUM(' . implode(',', $capitalReturn['qtySubtreeRefs']) . ')';
        $capitalAmtFormula = '=SUM(' . implode(',', $capitalReturn['amtSubtreeRefs']) . ')';
        $rows->push([null, 'पूँजीगत कार्यक्रमहरूको जम्मा', $capitalReturn['planned_qty_total'], $capitalReturn['planned_budget_total'], $capitalReturn['planned_q_qty'], $capitalReturn['planned_q_amt'], null, null, -1, null]);
        $this->totalRanges->put($capitalTotalCollRow, ['qtyFormula' => $capitalQtyFormula, 'amtFormula' => $capitalAmtFormula]);

        // Recurrent section header (planId null)
        $rows->push([null, 'चालू कार्यक्रमहरू', null, null, null, null, null, null, -2, null]);

        // Recurrent tree rows
        $nextTopNumRecurrent = 1;
        $recurrentReturn = $this->appendTreeRows($recurrentDefinitions, $recurrentPlans, $fiscalYear->id, $recurrentDefToPlanMap, $recurrentExpenses, $rows, $quarterNumber, [], $nextTopNumRecurrent, $this->totalRanges);

        // Recurrent subtotal row (planId null)
        $recurrentTotalCollRow = $rows->count() + 1;
        $recurrentQtyFormula = '=SUM(' . implode(',', $recurrentReturn['qtySubtreeRefs']) . ')';
        $recurrentAmtFormula = '=SUM(' . implode(',', $recurrentReturn['amtSubtreeRefs']) . ')';
        $rows->push([null, 'चालू कार्यक्रमहरूको जम्मा', $recurrentReturn['planned_qty_total'], $recurrentReturn['planned_budget_total'], $recurrentReturn['planned_q_qty'], $recurrentReturn['planned_q_amt'], null, null, -1, null]);
        $this->totalRanges->put($recurrentTotalCollRow, ['qtyFormula' => $recurrentQtyFormula, 'amtFormula' => $recurrentAmtFormula]);

        // Grand total row (planId null)
        $grandTotalCollRow = $rows->count() + 1;
        $grandPlannedQty = $capitalReturn['planned_qty_total'] + $recurrentReturn['planned_qty_total'];
        $grandPlannedBudget = $capitalReturn['planned_budget_total'] + $recurrentReturn['planned_budget_total'];
        $grandPlannedQQty = $capitalReturn['planned_q_qty'] + $recurrentReturn['planned_q_qty'];
        $grandPlannedQAmt = $capitalReturn['planned_q_amt'] + $recurrentReturn['planned_q_amt'];
        $grandQtyRefs = array_merge($capitalReturn['qtySubtreeRefs'], $recurrentReturn['qtySubtreeRefs']);
        $grandAmtRefs = array_merge($capitalReturn['amtSubtreeRefs'], $recurrentReturn['amtSubtreeRefs']);
        $grandQtyFormula = '=SUM(' . implode(',', $grandQtyRefs) . ')';
        $grandAmtFormula = '=SUM(' . implode(',', $grandAmtRefs) . ')';
        $rows->push([null, 'कुल जम्मा', $grandPlannedQty, $grandPlannedBudget, $grandPlannedQQty, $grandPlannedQAmt, null, null, -1, null]);
        $this->totalRanges->put($grandTotalCollRow, ['qtyFormula' => $grandQtyFormula, 'amtFormula' => $grandAmtFormula]);

        $this->rows = $rows; // NEW: Store for events

        return $rows;
    }

    private function appendTreeRows($roots, $plans, $fiscalYearId, $defToPlanMap, $expenses, &$rows, $quarterNumber, $path = [], &$nextTopNum, &$totalRanges): array
    {
        $totalPlannedQty = 0.0;
        $totalPlannedBudget = 0.0;
        $totalPlannedQQty = 0.0;
        $totalPlannedQAmt = 0.0;
        $totalActualQty = 0.0;
        $totalActualAmt = 0.0;

        $qtySubtreeRefs = [];
        $amtSubtreeRefs = [];

        foreach ($roots as $index => $definition) {
            $planId = $defToPlanMap[$definition->id] ?? null;
            if (!$planId) {
                continue;
            }

            $plan = $plans->get($definition->id);
            $depth = $definition->getDepthAttribute();
            $title = $plan?->program_override ?? $definition->program;

            $expense = $expenses->get($planId);
            $quarterData = $expense?->quarters->firstWhere('quarter', $quarterNumber);
            $thisActualQty = $quarterData?->quantity ?? 0;
            $thisActualAmt = $quarterData?->amount ?? 0;

            $thisPlannedQuantityTotal = (float) ($plan->planned_quantity ?? 0);
            $thisPlannedBudgetTotal = (float) ($plan->planned_budget ?? 0);
            $thisPlannedQAmount = (float) ($plan->{"q{$quarterNumber}_amount"} ?? 0);
            $thisPlannedQQuantity = (float) ($plan->{"q{$quarterNumber}_quantity"} ?? 0);

            $hasChildren = $definition->children && $definition->children->count() > 0;

            if ($hasChildren) {
                $thisPlannedQuantityTotal = 0.0;
                $thisPlannedBudgetTotal = 0.0;
                $thisPlannedQQuantity = 0.0;
                $thisPlannedQAmount = 0.0;
                $thisActualQty = 0.0;
                $thisActualAmt = 0.0;
            }

            $rowPlannedQuantityTotal = $hasChildren ? null : $thisPlannedQuantityTotal;
            $rowPlannedBudgetTotal = $hasChildren ? null : $thisPlannedBudgetTotal;
            $rowPlannedQQuantity = $hasChildren ? null : $thisPlannedQQuantity;
            $rowPlannedQAmount = $hasChildren ? null : $thisPlannedQAmount;
            $rowActualQty = $hasChildren ? null : ($thisActualQty > 0 ? $thisActualQty : null);
            $rowActualAmt = $hasChildren ? null : ($thisActualAmt > 0 ? $thisActualAmt : null);

            // Generate hierarchical serial
            if (empty($path)) {
                $position = $nextTopNum;
                $nextTopNum++;
            } else {
                $position = $index + 1;
            }
            $currentPath = array_merge($path, [$position]);
            $serialStr = implode('.', $currentPath);

            // FIXED: Add planId as 10th element (index 9)
            $row = [
                $serialStr,
                $title,
                $rowPlannedQuantityTotal,
                $rowPlannedBudgetTotal,
                $rowPlannedQQuantity,
                $rowPlannedQAmount,
                $rowActualQty,
                $rowActualAmt,
                $depth,
                $planId, // NEW: Plan ID for import (column J)
            ];
            $rows->push($row);
            $activityExcelRow = $rows->count() + $this->dataOffset;

            $subtreePlannedQty = $thisPlannedQuantityTotal;
            $subtreePlannedBudget = $thisPlannedBudgetTotal;
            $subtreePlannedQQty = $thisPlannedQQuantity;
            $subtreePlannedQAmt = $thisPlannedQAmount;
            $subtreeActualQty = $thisActualQty;
            $subtreeActualAmt = $thisActualAmt;

            if ($hasChildren) {
                // Recurse for children
                $childReturn = $this->appendTreeRows($definition->children, $plans, $fiscalYearId, $defToPlanMap, $expenses, $rows, $quarterNumber, $currentPath, $nextTopNum, $totalRanges);

                // Add subtotal row for this parent (planId null)
                $subtotalCollRow = $rows->count() + 1;
                $subtotalExcelRow = $subtotalCollRow + $this->dataOffset;
                $subtotalTitle = $title . ' को जम्मा';
                $subtotalPlannedQty = $thisPlannedQuantityTotal + $childReturn['planned_qty_total'];
                $subtotalPlannedBudget = $thisPlannedBudgetTotal + $childReturn['planned_budget_total'];
                $subtotalPlannedQQty = $thisPlannedQQuantity + $childReturn['planned_q_qty'];
                $subtotalPlannedQAmt = $thisPlannedQAmount + $childReturn['planned_q_amt'];
                $subtotalActualQty = $thisActualQty + $childReturn['actual_qty'];
                $subtotalActualAmt = $thisActualAmt + $childReturn['actual_amt'];

                $qtyFormula = '=SUM(' . implode(',', $childReturn['qtySubtreeRefs']) . ')';
                $amtFormula = '=SUM(' . implode(',', $childReturn['amtSubtreeRefs']) . ')';

                $subtotalRow = [
                    null,
                    $subtotalTitle,
                    $subtotalPlannedQty,
                    $subtotalPlannedBudget,
                    $subtotalPlannedQQty,
                    $subtotalPlannedQAmt,
                    null,
                    null,
                    $depth - 1,
                    null, // No planId for subtotal
                ];
                $rows->push($subtotalRow);

                $totalRanges->put($subtotalCollRow, ['qtyFormula' => $qtyFormula, 'amtFormula' => $amtFormula]);

                // Subtree sums are the subtotal sums
                $subtreePlannedQty = $subtotalPlannedQty;
                $subtreePlannedBudget = $subtotalPlannedBudget;
                $subtreePlannedQQty = $subtotalPlannedQQty;
                $subtreePlannedQAmt = $subtotalPlannedQAmt;
                $subtreeActualQty = $subtotalActualQty;
                $subtreeActualAmt = $subtotalActualAmt;

                // Subtree ref is the subtotal cell
                $qtySubtreeRefs[] = 'G' . $subtotalExcelRow;
                $amtSubtreeRefs[] = 'H' . $subtotalExcelRow;
            } else {
                // Leaf subtree ref is the activity cell
                $qtySubtreeRefs[] = 'G' . $activityExcelRow;
                $amtSubtreeRefs[] = 'H' . $activityExcelRow;
            }

            // Add to overall totals
            $totalPlannedQty += $subtreePlannedQty;
            $totalPlannedBudget += $subtreePlannedBudget;
            $totalPlannedQQty += $subtreePlannedQQty;
            $totalPlannedQAmt += $subtreePlannedQAmt;
            $totalActualQty += $subtreeActualQty;
            $totalActualAmt += $subtreeActualAmt;
        }

        return [
            'planned_qty_total' => $totalPlannedQty,
            'planned_budget_total' => $totalPlannedBudget,
            'planned_q_qty' => $totalPlannedQQty,
            'planned_q_amt' => $totalPlannedQAmt,
            'actual_qty' => $totalActualQty,
            'actual_amt' => $totalActualAmt,
            'qtySubtreeRefs' => $qtySubtreeRefs,
            'amtSubtreeRefs' => $amtSubtreeRefs,
        ];
    }

    private function getQuarterNepali(): array
    {
        return [
            1 => 'पहिलो',
            2 => 'दोस्रो',
            3 => 'तेस्रो',
            4 => 'चौथो',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Insert 6 rows at the top (3 for metadata, 1 for main header, 1 for subheader, 1 for column numbers)
                $sheet->insertNewRowBefore(1, 6);

                // Set top header rows (merged across A1:H1, etc.)
                $sheet->setCellValue('A1', "परियोजना: " . $this->projectTitle);
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $quarterName = $this->getQuarterNepali()[$this->quarter] ?? '';
                $sheet->setCellValue('A2', "आर्थिक वर्ष: " . $this->fiscalTitle);
                $sheet->mergeCells('A2:H2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A3', "त्रैमास: {$quarterName} त्रैमास");
                $sheet->mergeCells('A3:H3');
                $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Main header row 4
                $sheet->setCellValue('A4', 'क.सं.');
                $sheet->setCellValue('B4', 'कार्यक्रम/क्रियाकलाप');
                $sheet->setCellValue('C4', 'वार्षिक लक्ष्य');
                $sheet->mergeCells('C4:D4');
                $sheet->getStyle('C4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('E4', 'त्रैमासिक लक्ष्य');
                $sheet->mergeCells('E4:F4');
                $sheet->getStyle('E4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('G4', 'त्रैमासिक खर्च');
                $sheet->mergeCells('G4:H4');
                $sheet->getStyle('G4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Subheader row 5
                $sheet->setCellValue('C5', 'परिमाण');
                $sheet->setCellValue('D5', 'बजेट');
                $sheet->setCellValue('E5', 'परिमाण');
                $sheet->setCellValue('F5', 'बजेट');
                $sheet->setCellValue('G5', 'परिमाण');
                $sheet->setCellValue('H5', 'रकम');

                // Bold and center headers
                $sheet->getStyle('A4:H5')->getFont()->setBold(true);
                $sheet->getStyle('A4:H5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B4:B5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

                // Highlight table header with background color
                $sheet->getStyle('A4:H5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');

                // Column numbers row 6 (Nepali numerals) - make bold
                $sheet->setCellValue('A6', '१');
                $sheet->setCellValue('B6', '२');
                $sheet->setCellValue('C6', '३');
                $sheet->setCellValue('D6', '४');
                $sheet->setCellValue('E6', '५');
                $sheet->setCellValue('F6', '६');
                $sheet->setCellValue('G6', '७');
                $sheet->setCellValue('H6', '८');
                $sheet->getStyle('A6:H6')->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('A6:H6')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Apply the same highlight color to the number row
                $sheet->getStyle('A6:H6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');

                // Data starts at row 7
                $lastRow = $sheet->getHighestRow();

                // FIXED: Set plan IDs in column J from $this->rows
                $planCol = 'J';
                $sheet->getColumnDimension($planCol)->setVisible(false);
                $sheet->getColumnDimension($planCol)->setWidth(0);
                $collOffset = 6; // Collection index = excel row - 6
                for ($excelRow = 7; $excelRow <= $lastRow; $excelRow++) {
                    $collIndex = $excelRow - $collOffset - 1; // 0-based
                    if (isset($this->rows[$collIndex]) && isset($this->rows[$collIndex][9])) {
                        $planId = $this->rows[$collIndex][9];
                        $sheet->setCellValue($planCol . $excelRow, $planId);
                    }
                }

                // Set formulas for total rows
                if ($this->totalRanges) {
                    foreach ($this->totalRanges as $collRow => $data) {
                        $excelRow = $collRow + $this->dataOffset;
                        $sheet->setCellValue("G{$excelRow}", $data['qtyFormula']);
                        $sheet->setCellValue("H{$excelRow}", $data['amtFormula']);
                    }
                }

                // Set Nepali font for Devanagari support
                $sheet->getStyle("A1:H{$lastRow}")->getFont()->setName('Mangal');

                // Hide Depth column (I)
                $sheet->getColumnDimension('I')->setVisible(false);

                // Alignments for columns
                $sheet->getStyle("A7:A{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C7:H{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B7:B{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

                // Style data rows: Indent based on depth, bold for sections/totals, merge for parents and sections
                $titleCol = 'B';
                $depthCol = 'I';
                for ($row = 7; $row <= $lastRow; $row++) {
                    $title = $sheet->getCell($titleCol . $row)->getValue();
                    $depth = $sheet->getCell($depthCol . $row)->getValue();

                    // Merge for section headers (depth -2)
                    if ($depth == -2) {
                        $sheet->mergeCells("A{$row}:H{$row}");
                        $sheet->setCellValue("A{$row}", $title);
                        $sheet->setCellValue("B{$row}", '');
                        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    }

                    // Merge C:H for parent rows (where planned values are null, indicating no direct values/children planned here)
                    if ($depth >= 0 && strpos((string) $title, 'जम्मा') === false && is_null($sheet->getCell("C{$row}")->getValue())) {
                        $sheet->mergeCells("C{$row}:H{$row}");
                        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    }

                    // Bold and special fill for sections, subtotals, and grand total
                    if ($depth < 0 || strpos((string) $title, 'जम्मा') !== false || $title === 'कुल जम्मा') {
                        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);

                        // Determine color based on row type
                        $color = 'E3F2FD'; // default light blue for sections
                        if ($depth == -2) {
                            $color = 'E3F2FD'; // light blue for section headers
                        } elseif ($title === 'कुल जम्मा') {
                            $color = 'E8F5E8'; // light green for grand total
                        } elseif (strpos((string) $title, 'जम्मा') !== false) {
                            $color = 'FFF2CC'; // light yellow for subtotals
                        }

                        $sheet->getStyle("A{$row}:H{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
                    } elseif ($depth > 0 && $title) {
                        // Indent title cell based on depth
                        $sheet->getStyle($titleCol . $row)->getAlignment()->setIndent($depth * 2);
                    }
                }

                // Make table borders visible
                $sheet->getStyle("A4:H{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }

    public function title(): string
    {
        $q = $this->quarter;
        return $this->projectTitle . ' - ' . $this->fiscalTitle . ' - Q' . $q . ' Expense Template';
    }
}
