<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\ProjectActivityDefinition;
use App\Models\ProjectActivityPlan;
use Illuminate\Database\Eloquent\Collection;

class ProjectActivityExport implements FromArray, WithTitle, WithStyles, WithEvents
{
    protected $projectId;
    protected $fiscalYearId;
    protected $project;
    protected $fiscalYear;
    protected $totalRows = [];
    protected $headerRows = [];
    protected $footerStart;
    protected $globalXCapital;
    protected $globalXRecurrent;
    protected $tableHeaderRow;

    public function __construct($projectId, $fiscalYearId, $project, $fiscalYear)
    {
        $this->projectId = $projectId;
        $this->fiscalYearId = $fiscalYearId;
        $this->project = $project;
        $this->fiscalYear = $fiscalYear;
        $this->project->load('projectManager');
    }

    public function array(): array
    {
        // MODIFIED: Compute global x for capital and recurrent from definitions (fixed totals for roots)
        $this->globalXCapital = ProjectActivityDefinition::where('project_id', $this->projectId)
            ->whereNull('parent_id')
            ->where('expenditure_id', 1)
            ->sum('total_budget');
        $this->globalXCapital = (float) $this->globalXCapital;

        $this->globalXRecurrent = ProjectActivityDefinition::where('project_id', $this->projectId)
            ->whereNull('parent_id')
            ->where('expenditure_id', 2)
            ->sum('total_budget');
        $this->globalXRecurrent = (float) $this->globalXRecurrent;

        $data = [];

        // ========== HEADER SECTION (Refined: Removed all blank rows for compactness) ==========
        $data[] = ['नेपाल सरकार'];
        $data[] = ['.................. मन्त्रालय/निकाय'];
        $data[] = ['वार्षिक कार्यक्रम'];

        // Form section rows (compact, no blanks)
        // Row 4 (adjusted for compactness)
        $row4 = array_fill(0, 25, '');
        $fyTitle = $this->fiscalYear->title ?? '';
        $row4[0] = '१. आ.व.:– ' . $fyTitle;  // FIXED: Concat label + value in A
        $row4[7] = '१०. वार्षिक बजेट रु.:';
        $row4[15] = '११. कार्यक्रम/आयोजनाको कुल लागत:';
        $data[] = $row4;

        // Row 5
        $row5 = array_fill(0, 25, '');
        $row5[0] = '२. बजेट उपशीर्षक नं.:';
        $row5[7] = '(क) आन्तरिक';
        $row5[15] = '(क) आन्तरिक';
        $data[] = $row5;

        // Row 6
        $row6 = array_fill(0, 25, '');
        $row6[0] = '३. मन्त्रालय/निकाय:';
        $row6[7] = '(१) नेपाल सरकार:';
        $row6[15] = '(१) नेपाल सरकार:';
        $data[] = $row6;

        // Row 7
        $row7 = array_fill(0, 25, '');
        $row7[0] = '४. विभाग/कार्यालय:';
        $row7[7] = '(२) संस्था/निकाय:';
        $row7[15] = '(२) संस्था/निकाय:';
        $data[] = $row7;

        // Row 8
        $row8 = array_fill(0, 25, '');
        $projectTitle = $this->project->title ?? '';
        $row8[0] = '५. कार्यक्रम/आयोजनाको नाम: ' . $projectTitle;  // FIXED: Concat
        $row8[7] = '(३) जनसहभागिता:';
        $row8[15] = '(३) जनसहभागिता:';
        $data[] = $row8;

        // Row 9
        $row9 = array_fill(0, 25, '');
        $row9[0] = '६. स्थान: (क) जिल्ला:';  // FIXED: Concat sub-label into A
        $row9[7] = '(ख) वैदेशिक';
        $row9[15] = '(ख) वैदेशिक';
        $data[] = $row9;

        // Row 10
        $row10 = array_fill(0, 25, '');
        $row10[0] = '(ख) गाउँपालिका/नगरपालिका:';  // FIXED: Move sub-label to A (no main label)
        $row10[7] = '(१) ऋण:';
        $row10[15] = '(१) ऋण:';
        $data[] = $row10;

        // Row 11
        $row11 = array_fill(0, 25, '');
        $row11[0] = '(ग) वडा नं.:';
        $row11[7] = 'सट्टा दर:';
        $data[] = $row11;

        // Row 12
        $row12 = array_fill(0, 25, '');
        $startDate = $this->project->start_date ? $this->project->start_date->format('Y-m-d') : '';
        $row12[0] = '७. कार्यक्रम/आयोजना सुरु भएको मिति: ' . $startDate;  // FIXED: Concat
        $row12[7] = '(ग) मुद्रा:';
        $row12[15] = '(२) अनुदान:';
        $data[] = $row12;

        // Row 13
        $row13 = array_fill(0, 25, '');
        $endDate = $this->project->end_date ? $this->project->end_date->format('Y-m-d') : '';
        $row13[0] = '८. कार्यक्रम/आयोजना पूरा हुने मिति: ' . $endDate;  // FIXED: Concat
        $row13[7] = '(घ) दातृपक्ष/संस्था:';
        $data[] = $row13;

        // Row 14
        $row14 = array_fill(0, 25, '');
        $managerName = $this->project->projectManager ? ($this->project->projectManager->name ?? '') : '';
        $row14[0] = '९. आयोजना/कार्यालय प्रमुखको नाम: ' . $managerName;  // FIXED: Concat
        $row14[15] = '१२. गत आ.व. सम्मको खर्च रु. (सोझै भुक्तानी र वस्तुगत अनुदान समेत)';
        $data[] = $row14;

        // Rows 15-21 unchanged (no values in B)
        $row15 = array_fill(0, 25, '');
        $row15[15] = '(क) आन्तरिक';
        $data[] = $row15;

        $row16 = array_fill(0, 25, '');
        $row16[15] = '(१) नेपाल सरकार:';
        $data[] = $row16;

        $row17 = array_fill(0, 25, '');
        $row17[15] = '(२) संस्था/निकाय:';
        $data[] = $row17;

        $row18 = array_fill(0, 25, '');
        $row18[15] = '(३) जनसहभागिता:';
        $data[] = $row18;

        $row19 = array_fill(0, 25, '');
        $row19[15] = '(ख) वैदेशिक';
        $data[] = $row19;

        $row20 = array_fill(0, 25, '');
        $row20[15] = '(१) ऋण:';
        $data[] = $row20;

        $row21 = array_fill(0, 25, '');
        $row21[15] = '(२) अनुदान:';
        $data[] = $row21;

        // Note about amount format (no blank before)
        $noteRow = array_fill(0, 25, '');
        $noteRow[24] = '(रकम रु. हजारमा)';
        $data[] = $noteRow;

        // ========== TABLE SECTION (No blank before headers) ==========
        $this->tableHeaderRow = count($data) + 1;

        // Main Table Headers (Simplified: Single row only)
        $data[] = [
            'क्र.सं.',
            'कार्यक्रम/क्रियाकलाप',
            'एकाइ',
            'कार्यक्रम/आयोजनाको कुल क्रियाकलाप',
            '',
            '',
            'सम्पूर्ण कार्य मध्ये गत आर्थिक वर्ष सम्मको',
            '',
            '',
            'वार्षिक लक्ष्य',
            '',
            '',
            'प्रथम त्रैमासिक',
            '',
            '',
            'दोस्रो त्रैमासिक',
            '',
            '',
            'तेस्रो त्रैमासिक',
            '',
            '',
            'चौथो त्रैमासिक',
            '',
            '',
            'कैफियत'
        ];

        // Capital Section
        $capitalTotals = [];
        $capitalDefinitions = ProjectActivityDefinition::forProject($this->projectId)
            ->whereNull('parent_id')
            ->where('expenditure_id', 1)
            ->active()
            ->with('children.children')
            ->get();

        $capitalDefIds = $capitalDefinitions->flatMap(function ($def) {
            return collect([$def])->merge($def->getDescendants());
        })->pluck('id')->unique();

        $capitalPlans = ProjectActivityPlan::whereIn('activity_definition_id', $capitalDefIds)
            ->where('fiscal_year_id', $this->fiscalYearId)
            ->active()
            ->get()
            ->keyBy('activity_definition_id');

        if ($capitalDefinitions->isNotEmpty()) {
            $row = count($data) + 1;
            $data[] = ['पूँजीगत खर्च अन्तर्गतका कार्यक्रमहरूः'];
            $this->headerRows[] = $row;

            $activityStartRow = count($data) + 1;
            $data = array_merge($data, $this->buildActivityRows($capitalDefinitions, $capitalPlans, 'capital', $activityStartRow, $this->globalXCapital));
            $capitalTotals = $this->calculateOverallTotals($capitalDefinitions, $capitalPlans);
            $row = count($data) + 1;
            $data[] = $this->buildTotalRow('(क)', 'पूँजीगत कार्यक्रम हरूको जम्मा', $capitalTotals, $this->globalXCapital, true);
            $this->totalRows[] = $row;
        }

        // Recurrent Section
        $recurrentTotals = [];
        $recurrentDefinitions = ProjectActivityDefinition::forProject($this->projectId)
            ->whereNull('parent_id')
            ->where('expenditure_id', 2)
            ->active()
            ->with('children.children')
            ->get();

        $recurrentDefIds = $recurrentDefinitions->flatMap(function ($def) {
            return collect([$def])->merge($def->getDescendants());
        })->pluck('id')->unique();

        $recurrentPlans = ProjectActivityPlan::whereIn('activity_definition_id', $recurrentDefIds)
            ->where('fiscal_year_id', $this->fiscalYearId)
            ->active()
            ->get()
            ->keyBy('activity_definition_id');

        if ($recurrentDefinitions->isNotEmpty()) {
            $row = count($data) + 1;
            $data[] = ['चालू खर्च अन्तर्गतका कार्यक्रमहरुः'];
            $this->headerRows[] = $row;

            $activityStartRow = count($data) + 1;
            $data = array_merge($data, $this->buildActivityRows($recurrentDefinitions, $recurrentPlans, 'recurrent', $activityStartRow, $this->globalXRecurrent));
            $recurrentTotals = $this->calculateOverallTotals($recurrentDefinitions, $recurrentPlans);
            $row = count($data) + 1;
            $data[] = $this->buildTotalRow('(ख)', 'चालू खर्च अन्तर्गित का कार्यक्रम हरू को जम्मा', $recurrentTotals, $this->globalXRecurrent, false);
            $this->totalRows[] = $row;

            // Recurrent extras (no blanks)
            $zeroTotals = [
                'total_quantity' => 0,
                'completed_quantity' => 0,
                'planned_quantity' => 0,
                'q1_quantity' => 0,
                'q2_quantity' => 0,
                'q3_quantity' => 0,
                'q4_quantity' => 0,
                'total_budget' => 0,
                'total_expense' => 0,
                'planned_budget' => 0,
                'q1' => 0,
                'q2' => 0,
                'q3' => 0,
                'q4' => 0,
                'weighted_expense_contrib' => 0,
                'weighted_planned_contrib' => 0,
                'weighted_q1_contrib' => 0,
                'weighted_q2_contrib' => 0,
                'weighted_q3_contrib' => 0,
                'weighted_q4_contrib' => 0,
            ];
            $row = count($data) + 1;
            $data[] = $this->buildTotalRow('', 'चालूतर्फ उपभोग खर्चको जम्मा', $zeroTotals, $this->globalXRecurrent, false);
            $this->totalRows[] = $row;
            $row = count($data) + 1;
            $data[] = $this->buildTotalRow('', 'चालूतर्फ सञ्चालन खर्चको जम्मा', $zeroTotals, $this->globalXRecurrent, false);
            $this->totalRows[] = $row;
        }

        // Grand Total (no blanks before)
        $globalXGrand = $this->globalXCapital + $this->globalXRecurrent;
        $grandTotals = [
            'total_quantity' => ($capitalTotals['total_quantity'] ?? 0) + ($recurrentTotals['total_quantity'] ?? 0),
            'completed_quantity' => ($capitalTotals['completed_quantity'] ?? 0) + ($recurrentTotals['completed_quantity'] ?? 0),
            'planned_quantity' => ($capitalTotals['planned_quantity'] ?? 0) + ($recurrentTotals['planned_quantity'] ?? 0),
            'q1_quantity' => ($capitalTotals['q1_quantity'] ?? 0) + ($recurrentTotals['q1_quantity'] ?? 0),
            'q2_quantity' => ($capitalTotals['q2_quantity'] ?? 0) + ($recurrentTotals['q2_quantity'] ?? 0),
            'q3_quantity' => ($capitalTotals['q3_quantity'] ?? 0) + ($recurrentTotals['q3_quantity'] ?? 0),
            'q4_quantity' => ($capitalTotals['q4_quantity'] ?? 0) + ($recurrentTotals['q4_quantity'] ?? 0),
            'total_budget' => ($capitalTotals['total_budget'] ?? 0) + ($recurrentTotals['total_budget'] ?? 0),
            'total_expense' => ($capitalTotals['total_expense'] ?? 0) + ($recurrentTotals['total_expense'] ?? 0),
            'planned_budget' => ($capitalTotals['planned_budget'] ?? 0) + ($recurrentTotals['planned_budget'] ?? 0),
            'q1' => ($capitalTotals['q1'] ?? 0) + ($recurrentTotals['q1'] ?? 0),
            'q2' => ($capitalTotals['q2'] ?? 0) + ($recurrentTotals['q2'] ?? 0),
            'q3' => ($capitalTotals['q3'] ?? 0) + ($recurrentTotals['q3'] ?? 0),
            'q4' => ($capitalTotals['q4'] ?? 0) + ($recurrentTotals['q4'] ?? 0),
            'weighted_expense_contrib' => ($capitalTotals['weighted_expense_contrib'] ?? 0) + ($recurrentTotals['weighted_expense_contrib'] ?? 0),
            'weighted_planned_contrib' => ($capitalTotals['weighted_planned_contrib'] ?? 0) + ($recurrentTotals['weighted_planned_contrib'] ?? 0),
            'weighted_q1_contrib' => ($capitalTotals['weighted_q1_contrib'] ?? 0) + ($recurrentTotals['weighted_q1_contrib'] ?? 0),
            'weighted_q2_contrib' => ($capitalTotals['weighted_q2_contrib'] ?? 0) + ($recurrentTotals['weighted_q2_contrib'] ?? 0),
            'weighted_q3_contrib' => ($capitalTotals['weighted_q3_contrib'] ?? 0) + ($recurrentTotals['weighted_q3_contrib'] ?? 0),
            'weighted_q4_contrib' => ($capitalTotals['weighted_q4_contrib'] ?? 0) + ($recurrentTotals['weighted_q4_contrib'] ?? 0),
        ];
        $row = count($data) + 1;
        $data[] = $this->buildTotalRow('(ग)', 'कुल जम्मा (पूँजीगत + चालू)', $grandTotals, $globalXGrand, false);
        $this->totalRows[] = $row;

        // Signature Section (no blanks before)
        $this->footerStart = count($data) + 1;
        $footerRow1 = array_fill(0, 25, '');
        $footerRow1[0] = 'कार्यालय वा आयोजना प्रमुख:';
        $footerRow1[8] = 'विभागीय वा निकाय प्रमुख:';
        $footerRow1[16] = 'प्रमाणित गर्ने:';
        $data[] = $footerRow1;

        $footerRow2 = array_fill(0, 25, '');
        $footerRow2[0] = 'मिति:';
        $footerRow2[8] = 'मिति:';
        $footerRow2[16] = 'मिति:';
        $data[] = $footerRow2;

        return $data;
    }

    private function buildActivityRows(Collection $rootDefinitions, Collection $plans, string $expenditureType, int $startRow, float $globalX): array
    {
        $rows = [];
        $parentCounter = 1;

        foreach ($rootDefinitions as $parentDef) {
            $parentPlan = $plans->get($parentDef->id);
            $rows[] = $this->buildActivityRow($parentPlan, $parentDef, (string)$parentCounter, $expenditureType, $globalX);

            $children = $parentDef->children ?? collect();
            $hasChildren = $children->isNotEmpty();

            if ($hasChildren) {
                $childCounter = 1;
                foreach ($children as $childDef) {
                    $childPlan = $plans->get($childDef->id);
                    $childNumber = $parentCounter . '.' . $childCounter;
                    $rows[] = $this->buildActivityRow($childPlan, $childDef, $childNumber, $expenditureType, $globalX);

                    $grandchildren = $childDef->children ?? collect();
                    $grandchildCounter = 1;
                    foreach ($grandchildren as $grandchildDef) {
                        $grandchildPlan = $plans->get($grandchildDef->id);
                        $grandchildNumber = $parentCounter . '.' . $childCounter . '.' . $grandchildCounter;
                        $rows[] = $this->buildActivityRow($grandchildPlan, $grandchildDef, $grandchildNumber, $expenditureType, $globalX);
                        $grandchildCounter++;
                    }
                    $childCounter++;
                }

                $parentTotals = $this->calculateTotalsForParent($parentDef, $plans);
                $localRow = count($rows) + 1;
                $rows[] = $this->buildTotalRow('', 'Total of ' . $parentCounter, $parentTotals, $globalX, $expenditureType === 'capital');
                $this->totalRows[] = $startRow + $localRow - 1;
            }

            $parentCounter++;
        }

        return $rows;
    }

    private function buildActivityRow(?ProjectActivityPlan $plan, ProjectActivityDefinition $definition, string $number, string $expenditureType, float $globalX): array
    {
        $hasChildren = ($definition->children ?? collect())->isNotEmpty();
        // MODIFIED: Use definition's fixed total_quantity/total_budget for base display
        $displayTotalQuantity = $hasChildren ? 0.00 : $definition->total_quantity ?? 0;
        $displayTotalBudget = $hasChildren ? 0.00 : $definition->total_budget ?? 0;
        $displayCompletedQuantity = $hasChildren ? 0.00 : ($plan->completed_quantity ?? 0);
        $displayPlannedQuantity = $hasChildren ? 0.00 : ($plan->planned_quantity ?? 0);
        $displayQ1Quantity = $hasChildren ? 0.00 : ($plan->q1_quantity ?? 0);
        $displayQ2Quantity = $hasChildren ? 0.00 : ($plan->q2_quantity ?? 0);
        $displayQ3Quantity = $hasChildren ? 0.00 : ($plan->q3_quantity ?? 0);
        $displayQ4Quantity = $hasChildren ? 0.00 : ($plan->q4_quantity ?? 0);

        $displayTotalExpense = $hasChildren ? 0.00 : ($plan->total_expense ?? 0);
        $displayPlannedBudget = $hasChildren ? 0.00 : ($plan->planned_budget ?? 0);
        $displayQ1 = $hasChildren ? 0.00 : ($plan->q1_amount ?? 0);
        $displayQ2 = $hasChildren ? 0.00 : ($plan->q2_amount ?? 0);
        $displayQ3 = $hasChildren ? 0.00 : ($plan->q3_amount ?? 0);
        $displayQ4 = $hasChildren ? 0.00 : ($plan->q4_amount ?? 0);

        // MODIFIED: Weight budget from definition's total_budget (fixed)
        $weightBudget = $hasChildren ? '0.00' : number_format(($definition->total_budget ?? 0) / $globalX * 100, 2);

        $isCapital = $expenditureType === 'capital';
        // MODIFIED: Weights use plan's vars, which now base on definition totals (per model update)
        $weightExpense = $hasChildren || !$isCapital ? '0.00' : number_format(($plan->var_total_expense ?? 0) * 100, 2);
        $weightPlanned = $hasChildren || !$isCapital ? '0.00' : number_format(($plan->var_planned_budget ?? 0) * 100, 2);
        $weightQ1 = $hasChildren || !$isCapital ? '0.00' : number_format(($plan->var_q1 ?? 0) * 100, 2);
        $weightQ2 = $hasChildren || !$isCapital ? '0.00' : number_format(($plan->var_q2 ?? 0) * 100, 2);
        $weightQ3 = $hasChildren || !$isCapital ? '0.00' : number_format(($plan->var_q3 ?? 0) * 100, 2);
        $weightQ4 = $hasChildren || !$isCapital ? '0.00' : number_format(($plan->var_q4 ?? 0) * 100, 2);

        return [
            $number,
            $plan->effective_program ?? $definition->program ?? '',
            '',
            number_format($displayTotalQuantity, 2),
            number_format($displayTotalBudget / 1000, 0),
            $weightBudget,
            number_format($displayCompletedQuantity, 2),
            number_format($displayTotalExpense / 1000, 0),
            $weightExpense,
            number_format($displayPlannedQuantity, 2),
            $weightPlanned,
            number_format($displayPlannedBudget / 1000, 0),
            number_format($displayQ1Quantity, 2),
            $weightQ1,
            number_format($displayQ1 / 1000, 0),
            number_format($displayQ2Quantity, 2),
            $weightQ2,
            number_format($displayQ2 / 1000, 0),
            number_format($displayQ3Quantity, 2),
            $weightQ3,
            number_format($displayQ3 / 1000, 0),
            number_format($displayQ4Quantity, 2),
            $weightQ4,
            number_format($displayQ4 / 1000, 0),
            ''
        ];
    }

    private function buildTotalRow(string $prefix, string $label, array $totals, float $globalX, bool $calculateVar): array
    {
        $budgetWeight = $globalX > 0 ? ($totals['total_budget'] / $globalX) * 100 : 0;
        $expenseWeight = $calculateVar && $globalX > 0 ? ($totals['weighted_expense_contrib'] / $globalX * 100) : 0;
        $plannedWeight = $calculateVar && $globalX > 0 ? ($totals['weighted_planned_contrib'] / $globalX * 100) : 0;
        $q1Weight = $calculateVar && $globalX > 0 ? ($totals['weighted_q1_contrib'] / $globalX * 100) : 0;
        $q2Weight = $calculateVar && $globalX > 0 ? ($totals['weighted_q2_contrib'] / $globalX * 100) : 0;
        $q3Weight = $calculateVar && $globalX > 0 ? ($totals['weighted_q3_contrib'] / $globalX * 100) : 0;
        $q4Weight = $calculateVar && $globalX > 0 ? ($totals['weighted_q4_contrib'] / $globalX * 100) : 0;

        return [
            $prefix ?: '',
            $label,
            '',
            '',
            number_format($totals['total_budget'] / 1000, 0),
            number_format($budgetWeight, 2),
            '',
            number_format($totals['total_expense'] / 1000, 0),
            number_format($expenseWeight, 2),
            '',
            number_format($plannedWeight, 2),
            number_format($totals['planned_budget'] / 1000, 0),
            '',
            number_format($q1Weight, 2),
            number_format($totals['q1'] / 1000, 0),
            '',
            number_format($q2Weight, 2),
            number_format($totals['q2'] / 1000, 0),
            '',
            number_format($q3Weight, 2),
            number_format($totals['q3'] / 1000, 0),
            '',
            number_format($q4Weight, 2),
            number_format($totals['q4'] / 1000, 0),
            ''
        ];
    }

    private function calculateTotalsForParent(ProjectActivityDefinition $parentDef, Collection $plans): array
    {
        $totals = [
            'total_quantity' => 0,
            'completed_quantity' => 0,
            'planned_quantity' => 0,
            'q1_quantity' => 0,
            'q2_quantity' => 0,
            'q3_quantity' => 0,
            'q4_quantity' => 0,
            'total_budget' => 0,
            'total_expense' => 0,
            'planned_budget' => 0,
            'q1' => 0,
            'q2' => 0,
            'q3' => 0,
            'q4' => 0,
            'weighted_expense_contrib' => 0,
            'weighted_planned_contrib' => 0,
            'weighted_q1_contrib' => 0,
            'weighted_q2_contrib' => 0,
            'weighted_q3_contrib' => 0,
            'weighted_q4_contrib' => 0,
        ];
        $this->sumLeafNodes($parentDef, $totals, $plans);
        return $totals;
    }

    private function sumLeafNodes(ProjectActivityDefinition $def, array &$totals, Collection $plans): void
    {
        $children = $def->children ?? collect();
        if ($children->isEmpty()) {
            $plan = $plans->get($def->id);
            if (!$plan) {
                // MODIFIED: Even without plan, use definition's fixed totals for quantity/budget
                $totals['total_quantity'] += $def->total_quantity ?? 0;
                $totals['total_budget'] += $def->total_budget ?? 0;
                return;
            }

            // MODIFIED: Use definition's fixed total_quantity/total_budget as base for weights
            $totalQuantity = $def->total_quantity ?? 0;
            $budget = $def->total_budget ?? 0;
            $totals['total_quantity'] += $totalQuantity;
            $totals['total_budget'] += $budget;
            $totals['completed_quantity'] += $plan->completed_quantity ?? 0;
            $totals['planned_quantity'] += $plan->planned_quantity ?? 0;
            $totals['q1_quantity'] += $plan->q1_quantity ?? 0;
            $totals['q2_quantity'] += $plan->q2_quantity ?? 0;
            $totals['q3_quantity'] += $plan->q3_quantity ?? 0;
            $totals['q4_quantity'] += $plan->q4_quantity ?? 0;
            $totals['total_expense'] += $plan->total_expense ?? 0;
            $totals['planned_budget'] += $plan->planned_budget ?? 0;
            $totals['q1'] += $plan->q1_amount ?? 0;
            $totals['q2'] += $plan->q2_amount ?? 0;
            $totals['q3'] += $plan->q3_amount ?? 0;
            $totals['q4'] += $plan->q4_amount ?? 0;

            if ($totalQuantity > 0 && $def->expenditure_id == 1) {
                $progress = ($plan->completed_quantity ?? 0) / $totalQuantity;
                $totals['weighted_expense_contrib'] += $progress * $budget;

                $plannedProgress = ($plan->planned_quantity ?? 0) / $totalQuantity;
                $totals['weighted_planned_contrib'] += $plannedProgress * $budget;

                $plannedQuantity = $plan->planned_quantity ?? 0;
                if ($plannedQuantity > 0) {
                    $q1Progress = ($plan->q1_quantity ?? 0) / $totalQuantity;
                    $totals['weighted_q1_contrib'] += $q1Progress * $budget;

                    $q2Progress = ($plan->q2_quantity ?? 0) / $totalQuantity;
                    $totals['weighted_q2_contrib'] += $q2Progress * $budget;

                    $q3Progress = ($plan->q3_quantity ?? 0) / $totalQuantity;
                    $totals['weighted_q3_contrib'] += $q3Progress * $budget;

                    $q4Progress = ($plan->q4_quantity ?? 0) / $totalQuantity;
                    $totals['weighted_q4_contrib'] += $q4Progress * $budget;
                }
            }
        } else {
            foreach ($children as $child) {
                $this->sumLeafNodes($child, $totals, $plans);
            }
        }
    }

    private function calculateOverallTotals(Collection $rootDefinitions, Collection $plans): array
    {
        $totals = [
            'total_quantity' => 0,
            'completed_quantity' => 0,
            'planned_quantity' => 0,
            'q1_quantity' => 0,
            'q2_quantity' => 0,
            'q3_quantity' => 0,
            'q4_quantity' => 0,
            'total_budget' => 0,
            'total_expense' => 0,
            'planned_budget' => 0,
            'q1' => 0,
            'q2' => 0,
            'q3' => 0,
            'q4' => 0,
            'weighted_expense_contrib' => 0,
            'weighted_planned_contrib' => 0,
            'weighted_q1_contrib' => 0,
            'weighted_q2_contrib' => 0,
            'weighted_q3_contrib' => 0,
            'weighted_q4_contrib' => 0,
        ];
        foreach ($rootDefinitions as $rootDef) {
            $rootTotals = $this->calculateTotalsForParent($rootDef, $plans);
            foreach ($totals as $key => &$value) {
                $value += $rootTotals[$key];
            }
        }
        return $totals;
    }

    public function title(): string
    {
        return 'वार्षिक कार्यक्रम';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => false, 'size' => 10], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            3 => ['font' => ['bold' => false, 'size' => 10], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            5 => ['font' => ['bold' => true, 'size' => 10, 'underline' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function (\Maatwebsite\Excel\Events\AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ========== COLUMN WIDTHS (Tweaked: Widen B for concatenated text) ==========
                $sheet->getColumnDimension('A')->setWidth(4.5);
                $sheet->getColumnDimension('B')->setWidth(22);  // FIXED: Wider for long Nepali + values
                $sheet->getColumnDimension('C')->setWidth(5);
                $sheet->getColumnDimension('D')->setWidth(6);
                $sheet->getColumnDimension('E')->setWidth(6);
                $sheet->getColumnDimension('F')->setWidth(5);
                $sheet->getColumnDimension('G')->setWidth(6);
                $sheet->getColumnDimension('H')->setWidth(6);
                $sheet->getColumnDimension('I')->setWidth(5);
                $sheet->getColumnDimension('J')->setWidth(6);
                $sheet->getColumnDimension('K')->setWidth(5);
                $sheet->getColumnDimension('L')->setWidth(6);
                $sheet->getColumnDimension('M')->setWidth(6);
                $sheet->getColumnDimension('N')->setWidth(5);
                $sheet->getColumnDimension('O')->setWidth(6);
                $sheet->getColumnDimension('P')->setWidth(6);
                $sheet->getColumnDimension('Q')->setWidth(5);
                $sheet->getColumnDimension('R')->setWidth(6);
                $sheet->getColumnDimension('S')->setWidth(6);
                $sheet->getColumnDimension('T')->setWidth(5);
                $sheet->getColumnDimension('U')->setWidth(6);
                $sheet->getColumnDimension('V')->setWidth(6);
                $sheet->getColumnDimension('W')->setWidth(5);
                $sheet->getColumnDimension('X')->setWidth(6);
                $sheet->getColumnDimension('Y')->setWidth(10);

                // ========== HEADER SECTION STYLING (Rows 1-3, adjusted for compactness) ==========
                $sheet->mergeCells('A1:Y1');
                $sheet->mergeCells('A2:Y2');
                $sheet->mergeCells('A3:Y3');

                // FIXED: Center align row 2 like row 1
                $sheet->getStyle("A2:Y2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // FIXED: No borders for header rows
                $noBorder = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_NONE,
                        ]
                    ]
                ];
                $sheet->getStyle("A1:Y3")->applyFromArray($noBorder);

                // ========== FORM SECTION STYLING (Rows 4-21, FIXED alignments + borders + heights) ==========
                $formStart = 4;
                $formEnd = 21;

                // FIXED: Wrap + top + LEFT alignment for all form cells
                $sheet->getStyle("A{$formStart}:Y{$formEnd}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("A{$formStart}:Y{$formEnd}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle("A{$formStart}:Y{$formEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);  // FIXED: Explicit left
                $sheet->getStyle("A{$formStart}:Y{$formEnd}")->getFont()->setSize(9);

                // FIXED: No borders for form section (like footer)
                $sheet->getStyle("A{$formStart}:Y{$formEnd}")->applyFromArray($noBorder);

                // Merge columns for form section
                for ($r = $formStart; $r <= $formEnd; $r++) {
                    $sheet->mergeCells("A{$r}:G{$r}");
                    $sheet->mergeCells("H{$r}:O{$r}");
                    $sheet->mergeCells("P{$r}:Y{$r}");
                }

                // FIXED: Bold primary labels + sub-labels (adjusted)
                $boldRows = [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];  // FIXED: Include sub-rows 9-11
                foreach ($boldRows as $row) {
                    $sheet->getStyle("A{$row}")->applyFromArray(['font' => ['bold' => true, 'size' => 9]]);
                }
                $sheet->getStyle("H4")->applyFromArray(['font' => ['bold' => true, 'size' => 9]]);
                $sheet->getStyle("P4")->applyFromArray(['font' => ['bold' => true, 'size' => 9]]);
                $sheet->getStyle("P14")->applyFromArray(['font' => ['bold' => true, 'size' => 9]]);

                // FIXED: Taller rows for long concatenated text
                for ($r = $formStart; $r <= $formEnd; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(28);  // FIXED: +4px
                }

                // Note row (adjusted, no blank before)
                $noteRowNum = 22;
                $sheet->getStyle("Y{$noteRowNum}")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 8],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
                ]);
                // FIXED: No border for note row
                $sheet->getStyle("A{$noteRowNum}:Y{$noteRowNum}")->applyFromArray($noBorder);

                // ========== TABLE SECTION STYLING ==========
                $tableStart = $this->tableHeaderRow;

                // Table header merges (Simplified: Single row only)
                $sheet->mergeCells("D{$tableStart}:F{$tableStart}");
                $sheet->mergeCells("G{$tableStart}:I{$tableStart}");
                $sheet->mergeCells("J{$tableStart}:L{$tableStart}");
                $sheet->mergeCells("M{$tableStart}:O{$tableStart}");
                $sheet->mergeCells("P{$tableStart}:R{$tableStart}");
                $sheet->mergeCells("S{$tableStart}:U{$tableStart}");
                $sheet->mergeCells("V{$tableStart}:X{$tableStart}");

                // Table header styling (Simplified: Single row)
                $sheet->getStyle("A{$tableStart}:Y{$tableStart}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 8],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9E1F2']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // Set header row height (taller for wrap)
                $sheet->getRowDimension($tableStart)->setRowHeight(35);

                // ========== SECTION HEADERS STYLING ==========
                foreach ($this->headerRows as $row) {
                    $sheet->mergeCells("A{$row}:Y{$row}");
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFF2CC']
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000']
                            ]
                        ]
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(22);
                }

                // ========== TOTAL ROWS STYLING ==========
                foreach ($this->totalRows as $row) {
                    $sheet->getStyle("A{$row}:Y{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E2EFDA']
                        ],
                        'font' => ['bold' => true, 'size' => 8]
                    ]);
                }

                // ========== TABLE DATA STYLING ==========
                $tableDataStart = $tableStart + 1;
                $borderEndRow = $this->footerStart ? ($this->footerStart - 1) : $sheet->getHighestRow();

                if ($borderEndRow >= $tableDataStart) {
                    // Apply smaller font to all table data
                    $sheet->getStyle("A{$tableDataStart}:Y{$borderEndRow}")->getFont()->setSize(8);

                    // Apply borders
                    $sheet->getStyle("A{$tableDataStart}:Y{$borderEndRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000']
                            ]
                        ]
                    ]);

                    // Column alignments
                    $sheet->getStyle("A{$tableDataStart}:A{$borderEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B{$tableDataStart}:B{$borderEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
                    $sheet->getStyle("C{$tableDataStart}:C{$borderEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("Y{$tableDataStart}:Y{$borderEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);

                    // Numeric columns right-aligned
                    $numericCols = ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X'];
                    foreach ($numericCols as $col) {
                        $sheet->getStyle("{$col}{$tableDataStart}:{$col}{$borderEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    // Set consistent row heights for data rows
                    for ($r = $tableDataStart; $r <= $borderEndRow; $r++) {
                        $sheet->getRowDimension($r)->setRowHeight(20);
                    }
                }

                // ========== FOOTER SECTION ==========
                if ($this->footerStart) {
                    $lastRow = $sheet->getHighestRow();
                    $sheet->getStyle("A{$this->footerStart}:Y{$lastRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                        'font' => ['bold' => true, 'size' => 9]
                    ]);

                    // Set footer row heights
                    for ($r = $this->footerStart; $r <= $lastRow; $r++) {
                        $sheet->getRowDimension($r)->setRowHeight(22);
                    }
                }

                // ========== PAGE SETUP FOR A4 LANDSCAPE ==========
                $lastRow = $sheet->getHighestRow();
                $sheet->getPageSetup()->setPrintArea("A1:Y{$lastRow}");
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

                // Fit to one page width
                $sheet->getPageSetup()->setFitToPage(true);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                // Set margins (in inches) - narrower for better fit
                $sheet->getPageMargins()->setTop(0.3);
                $sheet->getPageMargins()->setRight(0.25);
                $sheet->getPageMargins()->setLeft(0.25);
                $sheet->getPageMargins()->setBottom(0.3);
                $sheet->getPageMargins()->setHeader(0.15);
                $sheet->getPageMargins()->setFooter(0.15);

                // Print settings
                $sheet->getPageSetup()->setHorizontalCentered(true);
                $sheet->setShowGridlines(false);
            },
        ];
    }
}
