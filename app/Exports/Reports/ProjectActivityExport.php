<?php

declare(strict_types=1);

namespace App\Exports\Reports;

use App\Models\ProjectActivityPlan;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\ProjectActivityDefinition;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection as SupportCollection;

class ProjectActivityExport implements FromArray, WithTitle, WithStyles, WithEvents
{
    protected $projectId;
    protected $fiscalYearId;
    protected $project;
    protected $fiscalYear;
    protected $totalRows = [];
    protected $headerRows = [];
    protected $parentRows = [];
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

    private function toNepaliDigits($num): string
    {
        $arabic = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $devanagari = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
        return str_replace($arabic, $devanagari, (string) $num);
    }

    public function array(): array
    {
        $data = [];

        // ========== HEADER SECTION (Refined: Removed all blank rows for compactness) ==========
        $data[] = ['नेपाल सरकार'];
        $data[] = ['.................. मन्त्रालय/निकाय'];
        $data[] = ['वार्षिक कार्यक्रम'];

        // Form section rows (compact, no blanks)
        // Row 4 (adjusted for compactness)
        $row4 = array_fill(0, 25, '');
        $fyTitle = $this->fiscalYear->title ?? '';
        $row4[0] = '१. आ.व.:– ' . $fyTitle;
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
        $row8[0] = '५. कार्यक्रम/आयोजनाको नाम: ' . $projectTitle;
        $row8[7] = '(३) जनसहभागिता:';
        $row8[15] = '(३) जनसहभागिता:';
        $data[] = $row8;

        // Row 9
        $row9 = array_fill(0, 25, '');
        $row9[0] = '६. स्थान: (क) जिल्ला:';
        $row9[7] = '(ख) वैदेशिक';
        $row9[15] = '(ख) वैदेशिक';
        $data[] = $row9;

        // Row 10
        $row10 = array_fill(0, 25, '');
        $row10[0] = '(ख) गाउँपालिका/नगरपालिका:';
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
        $row12[0] = '७. कार्यक्रम/आयोजना सुरु भएको मिति: ' . $startDate;
        $row12[7] = '(ग) मुद्रा:';
        $row12[15] = '(२) अनुदान:';
        $data[] = $row12;

        // Row 13
        $row13 = array_fill(0, 25, '');
        $endDate = $this->project->end_date ? $this->project->end_date->format('Y-m-d') : '';
        $row13[0] = '८. कार्यक्रम/आयोजना पूरा हुने मिति: ' . $endDate;
        $row13[7] = '(घ) दातृपक्ष/संस्था:';
        $data[] = $row13;

        // Row 14
        $row14 = array_fill(0, 25, '');
        $managerName = $this->project->projectManager ? ($this->project->projectManager->name ?? '') : '';
        $row14[0] = '९. आयोजना/कार्यालय प्रमुखको नाम: ' . $managerName;
        $row14[15] = '१२. गत आ.व. सम्मको खर्च रु. (सोझै भुक्तानी र वस्तुगत अनुदान समेत)';
        $data[] = $row14;

        // Rows 15-21
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

        // Note about amount format
        $noteRow = array_fill(0, 25, '');
        $noteRow[24] = '(रकम रु. हजारमा)';
        $data[] = $noteRow;

        // ========== TABLE SECTION ==========
        $this->tableHeaderRow = count($data) + 1;

        // Main Table Headers
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

        // Sub-sub header row
        $subSubHeader = array_fill(0, 25, '');
        $subSubHeader[3] = 'परिमाण'; // D
        $subSubHeader[4] = 'लागत'; // E
        $subSubHeader[5] = 'भार'; // F
        $subSubHeader[6] = 'सम्पन्न परिमाण'; // G
        $subSubHeader[7] = 'खर्च'; // H
        $subSubHeader[8] = 'भारित प्रगति'; // I
        $subSubHeader[9] = 'परिमाण'; // J
        $subSubHeader[10] = 'भार'; // K
        $subSubHeader[11] = 'बजेट'; // L
        $subSubHeader[12] = 'परिमाण'; // M
        $subSubHeader[13] = 'भार'; // N
        $subSubHeader[14] = 'बजेट'; // O
        $subSubHeader[15] = 'परिमाण'; // P
        $subSubHeader[16] = 'भार'; // Q
        $subSubHeader[17] = 'बजेट'; // R
        $subSubHeader[18] = 'परिमाण'; // S
        $subSubHeader[19] = 'भार'; // T
        $subSubHeader[20] = 'बजेट'; // U
        $subSubHeader[21] = 'परिमाण'; // V
        $subSubHeader[22] = 'भार'; // W
        $subSubHeader[23] = 'बजेट'; // X
        $data[] = $subSubHeader;

        // Numbers row with Nepali digits
        $subHeader = [];
        for ($i = 1; $i <= 25; $i++) {
            $subHeader[] = $this->toNepaliDigits($i);
        }
        $data[] = $subHeader;

        // Capital Section
        $capitalTotals = [];
        $capitalDefinitions = ProjectActivityDefinition::forProject($this->projectId)
            ->whereNull('parent_id')
            ->where('expenditure_id', 1)
            ->orderBy('sort_index')
            ->with('children.children')
            ->get();

        $capitalDefIds = $capitalDefinitions->flatMap(function ($def) {
            return collect([$def])->merge($def->getDescendants());
        })->pluck('id')->unique();

        $capitalPlans = ProjectActivityPlan::whereIn('activity_definition_id', $capitalDefIds)
            ->where('fiscal_year_id', $this->fiscalYearId)
            ->get()
            ->keyBy('activity_definition_id');

        if ($capitalDefinitions->isNotEmpty()) {
            $capitalTotals = $this->calculateOverallTotals($capitalDefinitions, $capitalPlans);
            $this->globalXCapital = (float) $capitalTotals['total_budget'];

            $row = count($data) + 1;
            $data[] = ['पूँजीगत खर्च अन्तर्गतका कार्यक्रमहरूः'];
            $this->headerRows[] = $row;

            $activityStartRow = count($data) + 1;
            $data = array_merge($data, $this->buildActivityRows($capitalDefinitions, $capitalPlans, 'capital', $activityStartRow, $this->globalXCapital));
            $row = count($data) + 1;
            $data[] = $this->buildTotalRow('(क)', 'पूँजीगत कार्यक्रम हरूको जम्मा', $capitalTotals, $this->globalXCapital, true);
            $this->totalRows[] = $row;
        }

        // Recurrent Section
        $recurrentTotals = [];
        $recurrentDefinitions = ProjectActivityDefinition::forProject($this->projectId)
            ->whereNull('parent_id')
            ->where('expenditure_id', 2)
            ->orderBy('sort_index')
            ->with('children.children')
            ->get();

        $recurrentDefIds = $recurrentDefinitions->flatMap(function ($def) {
            return collect([$def])->merge($def->getDescendants());
        })->pluck('id')->unique();

        $recurrentPlans = ProjectActivityPlan::whereIn('activity_definition_id', $recurrentDefIds)
            ->where('fiscal_year_id', $this->fiscalYearId)
            ->get()
            ->keyBy('activity_definition_id');

        if ($recurrentDefinitions->isNotEmpty()) {
            $recurrentTotals = $this->calculateOverallTotals($recurrentDefinitions, $recurrentPlans);
            $this->globalXRecurrent = (float) $recurrentTotals['total_budget'];

            $row = count($data) + 1;
            $data[] = ['चालू खर्च अन्तर्गतका कार्यक्रमहरुः'];
            $this->headerRows[] = $row;

            $activityStartRow = count($data) + 1;
            $data = array_merge($data, $this->buildActivityRows($recurrentDefinitions, $recurrentPlans, 'recurrent', $activityStartRow, $this->globalXRecurrent));
            $row = count($data) + 1;
            $data[] = $this->buildTotalRow('(ख)', 'चालू खर्च अन्तर्गित का कार्यक्रम हरू को जम्मा', $recurrentTotals, $this->globalXRecurrent, false);
            $this->totalRows[] = $row;

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

        // Grand Total
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

        // Signature Section
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

    private function buildActivityRows(Collection $rootDefinitions, SupportCollection $plans, string $expenditureType, int $activityStartRow, float $globalX): array
    {
        $rows = [];
        $parentCounter = 1;

        foreach ($rootDefinitions as $parentDef) {
            $parentPlan = $plans->get($parentDef->id);
            $rows[] = $this->buildActivityRow($parentPlan, $parentDef, $this->toNepaliDigits($parentCounter), $expenditureType, $globalX);
            $currentLocalRow = count($rows);
            if (($parentDef->children ?? collect())->isNotEmpty()) {
                $this->parentRows[] = $activityStartRow + $currentLocalRow - 1;
            }

            $children = $parentDef->children ?? collect();
            $hasChildren = $children->isNotEmpty();

            if ($hasChildren) {
                $childCounter = 1;
                foreach ($children as $childDef) {
                    $childPlan = $plans->get($childDef->id);
                    $childNumber = $this->toNepaliDigits($parentCounter) . '.' . $this->toNepaliDigits($childCounter);
                    $rows[] = $this->buildActivityRow($childPlan, $childDef, $childNumber, $expenditureType, $globalX);
                    $childCurrentLocalRow = count($rows);
                    if (($childDef->children ?? collect())->isNotEmpty()) {
                        $this->parentRows[] = $activityStartRow + $childCurrentLocalRow - 1;
                    }

                    $grandchildren = $childDef->children ?? collect();
                    $grandchildCounter = 1;
                    foreach ($grandchildren as $grandchildDef) {
                        $grandchildPlan = $plans->get($grandchildDef->id);
                        $grandchildNumber = $this->toNepaliDigits($parentCounter) . '.' . $this->toNepaliDigits($childCounter) . '.' . $this->toNepaliDigits($grandchildCounter);
                        $rows[] = $this->buildActivityRow($grandchildPlan, $grandchildDef, $grandchildNumber, $expenditureType, $globalX);
                        $grandchildCounter++;
                    }
                    $childCounter++;
                }

                $parentTotals = $this->calculateTotalsForParent($parentDef, $plans);
                $localRow = count($rows) + 1;
                $rows[] = $this->buildTotalRow('', 'Total of ' . $parentCounter, $parentTotals, $globalX, $expenditureType === 'capital');
                $this->totalRows[] = $activityStartRow + $localRow - 1;
            }

            $parentCounter++;
        }

        return $rows;
    }

    private function buildActivityRow(?ProjectActivityPlan $plan, ProjectActivityDefinition $definition, string $number, string $expenditureType, float $globalX): array
    {
        $hasChildren = ($definition->children ?? collect())->isNotEmpty();
        $program = $plan->effective_program ?? $definition->program ?? '';

        if ($hasChildren) {
            return [
                $number,
                $program,
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
            ];
        }

        // Leaf nodes
        $displayTotalQuantity = (float) ($definition->total_quantity ?? 0);
        $displayTotalBudget = (float) ($definition->total_budget ?? 0);
        $displayCompletedQuantity = (float) ($plan->completed_quantity ?? 0);
        $displayPlannedQuantity = (float) ($plan->planned_quantity ?? 0);
        $displayQ1Quantity = (float) ($plan->q1_quantity ?? 0);
        $displayQ2Quantity = (float) ($plan->q2_quantity ?? 0);
        $displayQ3Quantity = (float) ($plan->q3_quantity ?? 0);
        $displayQ4Quantity = (float) ($plan->q4_quantity ?? 0);
        $displayTotalExpense = (float) ($plan->total_expense ?? 0);
        $displayPlannedBudget = (float) ($plan->planned_budget ?? 0);
        $displayQ1 = (float) ($plan->q1_amount ?? 0);
        $displayQ2 = (float) ($plan->q2_amount ?? 0);
        $displayQ3 = (float) ($plan->q3_amount ?? 0);
        $displayQ4 = (float) ($plan->q4_amount ?? 0);

        $isCapital = $expenditureType === 'capital';
        $budget = $displayTotalBudget;

        // FIXED: Calculate raw weight first (float), then format for display
        $rawWeightBudget = $globalX > 0 ? ($budget / $globalX) * 100 : 0.0;
        $weightBudget = number_format($rawWeightBudget, 2);

        if (!$isCapital) {
            $weightExpense = $weightPlanned = $weightQ1 = $weightQ2 = $weightQ3 = $weightQ4 = '0.00';
        } else {
            $progressRatio = $displayTotalQuantity > 0 ? $displayCompletedQuantity / $displayTotalQuantity : 0;
            $weightExpense = number_format($progressRatio * $rawWeightBudget, 2);

            $plannedRatio = $displayTotalQuantity > 0 ? $displayPlannedQuantity / $displayTotalQuantity : 0;
            $weightPlanned = number_format($plannedRatio * $rawWeightBudget, 2);

            $q1Ratio = $displayTotalQuantity > 0 ? $displayQ1Quantity / $displayTotalQuantity : 0;
            $weightQ1 = number_format($q1Ratio * $rawWeightBudget, 2);

            $q2Ratio = $displayTotalQuantity > 0 ? $displayQ2Quantity / $displayTotalQuantity : 0;
            $weightQ2 = number_format($q2Ratio * $rawWeightBudget, 2);

            $q3Ratio = $displayTotalQuantity > 0 ? $displayQ3Quantity / $displayTotalQuantity : 0;
            $weightQ3 = number_format($q3Ratio * $rawWeightBudget, 2);

            $q4Ratio = $displayTotalQuantity > 0 ? $displayQ4Quantity / $displayTotalQuantity : 0;
            $weightQ4 = number_format($q4Ratio * $rawWeightBudget, 2);
        }

        return [
            $number,
            $program,
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

    private function calculateTotalsForParent(ProjectActivityDefinition $parentDef, SupportCollection $plans): array
    {
        $totals = [
            'total_quantity' => 0.0,
            'completed_quantity' => 0.0,
            'planned_quantity' => 0.0,
            'q1_quantity' => 0.0,
            'q2_quantity' => 0.0,
            'q3_quantity' => 0.0,
            'q4_quantity' => 0.0,
            'total_budget' => 0.0,
            'total_expense' => 0.0,
            'planned_budget' => 0.0,
            'q1' => 0.0,
            'q2' => 0.0,
            'q3' => 0.0,
            'q4' => 0.0,
            'weighted_expense_contrib' => 0.0,
            'weighted_planned_contrib' => 0.0,
            'weighted_q1_contrib' => 0.0,
            'weighted_q2_contrib' => 0.0,
            'weighted_q3_contrib' => 0.0,
            'weighted_q4_contrib' => 0.0,
        ];
        $this->sumLeafNodes($parentDef, $totals, $plans);
        return $totals;
    }

    private function sumLeafNodes(ProjectActivityDefinition $def, array &$totals, SupportCollection $plans): void
    {
        $children = $def->children ?? collect();
        if ($children->isEmpty()) {
            $plan = $plans->get($def->id);

            $totalQuantity = (float) ($def->total_quantity ?? 0);
            $budget = (float) ($def->total_budget ?? 0);

            $totals['total_quantity'] += $totalQuantity;
            $totals['total_budget'] += $budget;

            if ($plan) {
                $totals['completed_quantity'] += (float) ($plan->completed_quantity ?? 0);
                $totals['planned_quantity'] += (float) ($plan->planned_quantity ?? 0);
                $totals['q1_quantity'] += (float) ($plan->q1_quantity ?? 0);
                $totals['q2_quantity'] += (float) ($plan->q2_quantity ?? 0);
                $totals['q3_quantity'] += (float) ($plan->q3_quantity ?? 0);
                $totals['q4_quantity'] += (float) ($plan->q4_quantity ?? 0);
                $totals['total_expense'] += (float) ($plan->total_expense ?? 0);
                $totals['planned_budget'] += (float) ($plan->planned_budget ?? 0);
                $totals['q1'] += (float) ($plan->q1_amount ?? 0);
                $totals['q2'] += (float) ($plan->q2_amount ?? 0);
                $totals['q3'] += (float) ($plan->q3_amount ?? 0);
                $totals['q4'] += (float) ($plan->q4_amount ?? 0);
            }

            if ($totalQuantity > 0 && $def->expenditure_id == 1) {
                $progress = $totals['completed_quantity'] / $totalQuantity;  // Use accumulated, but since leaf, same
                $totals['weighted_expense_contrib'] += $progress * $budget;

                $plannedProgress = $totals['planned_quantity'] / $totalQuantity;
                $totals['weighted_planned_contrib'] += $plannedProgress * $budget;

                if ($totals['planned_quantity'] > 0) {
                    $q1Progress = $totals['q1_quantity'] / $totalQuantity;
                    $totals['weighted_q1_contrib'] += $q1Progress * $budget;

                    $q2Progress = $totals['q2_quantity'] / $totalQuantity;
                    $totals['weighted_q2_contrib'] += $q2Progress * $budget;

                    $q3Progress = $totals['q3_quantity'] / $totalQuantity;
                    $totals['weighted_q3_contrib'] += $q3Progress * $budget;

                    $q4Progress = $totals['q4_quantity'] / $totalQuantity;
                    $totals['weighted_q4_contrib'] += $q4Progress * $budget;
                }
            }
        } else {
            foreach ($children as $child) {
                $this->sumLeafNodes($child, $totals, $plans);
            }
        }
    }

    private function calculateOverallTotals(Collection $rootDefinitions, SupportCollection $plans): array
    {
        $totals = [
            'total_quantity' => 0.0,
            'completed_quantity' => 0.0,
            'planned_quantity' => 0.0,
            'q1_quantity' => 0.0,
            'q2_quantity' => 0.0,
            'q3_quantity' => 0.0,
            'q4_quantity' => 0.0,
            'total_budget' => 0.0,
            'total_expense' => 0.0,
            'planned_budget' => 0.0,
            'q1' => 0.0,
            'q2' => 0.0,
            'q3' => 0.0,
            'q4' => 0.0,
            'weighted_expense_contrib' => 0.0,
            'weighted_planned_contrib' => 0.0,
            'weighted_q1_contrib' => 0.0,
            'weighted_q2_contrib' => 0.0,
            'weighted_q3_contrib' => 0.0,
            'weighted_q4_contrib' => 0.0,
        ];
        foreach ($rootDefinitions as $rootDef) {
            $rootTotals = $this->calculateTotalsForParent($rootDef, $plans);
            foreach ($rootTotals as $key => $value) {
                $totals[$key] += $value;
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

                // COLUMN WIDTHS
                $sheet->getColumnDimension('A')->setWidth(4.5);
                $sheet->getColumnDimension('B')->setWidth(22);
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

                // HEADER SECTION
                $sheet->mergeCells('A1:Y1');
                $sheet->mergeCells('A2:Y2');
                $sheet->mergeCells('A3:Y3');
                $sheet->getStyle("A2:Y2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $noBorder = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]]];
                $sheet->getStyle("A1:Y3")->applyFromArray($noBorder);

                for ($r = 1; $r <= 3; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(18);
                }

                // FORM SECTION
                $formStart = 4;
                $formEnd = 21;
                $sheet->getStyle("A{$formStart}:Y{$formEnd}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("A{$formStart}:Y{$formEnd}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle("A{$formStart}:Y{$formEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("A{$formStart}:Y{$formEnd}")->getFont()->setSize(9);
                $sheet->getStyle("A{$formStart}:Y{$formEnd}")->applyFromArray($noBorder);

                for ($r = $formStart; $r <= $formEnd; $r++) {
                    $sheet->mergeCells("A{$r}:G{$r}");
                    $sheet->mergeCells("H{$r}:O{$r}");
                    $sheet->mergeCells("P{$r}:Y{$r}");
                }

                $boldRows = [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];
                foreach ($boldRows as $row) {
                    $sheet->getStyle("A{$row}")->applyFromArray(['font' => ['bold' => true, 'size' => 9]]);
                }
                $sheet->getStyle("H4")->applyFromArray(['font' => ['bold' => true, 'size' => 9]]);
                $sheet->getStyle("P4")->applyFromArray(['font' => ['bold' => true, 'size' => 9]]);
                $sheet->getStyle("P14")->applyFromArray(['font' => ['bold' => true, 'size' => 9]]);

                for ($r = $formStart; $r <= $formEnd; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(20);
                }

                // Note row
                $noteRowNum = 22;
                $sheet->getStyle("Y{$noteRowNum}")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 8],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
                ]);
                $sheet->getStyle("A{$noteRowNum}:Y{$noteRowNum}")->applyFromArray($noBorder);
                $sheet->getRowDimension($noteRowNum)->setRowHeight(16);

                // TABLE SECTION
                $tableStart = $this->tableHeaderRow;

                $sheet->mergeCells("D{$tableStart}:F{$tableStart}");
                $sheet->mergeCells("G{$tableStart}:I{$tableStart}");
                $sheet->mergeCells("J{$tableStart}:L{$tableStart}");
                $sheet->mergeCells("M{$tableStart}:O{$tableStart}");
                $sheet->mergeCells("P{$tableStart}:R{$tableStart}");
                $sheet->mergeCells("S{$tableStart}:U{$tableStart}");
                $sheet->mergeCells("V{$tableStart}:X{$tableStart}");

                $sheet->getStyle("A{$tableStart}:Y{$tableStart}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 8],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
                ]);
                $sheet->getRowDimension($tableStart)->setRowHeight(35);

                $subSubRow = $tableStart + 1;
                $sheet->getStyle("A{$subSubRow}:Y{$subSubRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 8],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F8F8']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
                ]);
                $sheet->getRowDimension($subSubRow)->setRowHeight(20);

                $subRow = $tableStart + 2;
                $sheet->getStyle("A{$subRow}:Y{$subRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 8],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
                ]);
                $sheet->getRowDimension($subRow)->setRowHeight(20);

                // SECTION HEADERS
                foreach ($this->headerRows as $row) {
                    $sheet->mergeCells("A{$row}:Y{$row}");
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(22);
                }

                // TOTAL ROWS
                foreach ($this->totalRows as $row) {
                    $sheet->getStyle("A{$row}:Y{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
                        'font' => ['bold' => true, 'size' => 8]
                    ]);
                }

                // PARENT ROWS MERGING
                foreach ($this->parentRows as $row) {
                    $sheet->mergeCells("C{$row}:X{$row}");
                    $sheet->getStyle("C{$row}:X{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                // TABLE DATA
                $tableDataStart = $tableStart + 3;
                $borderEndRow = $this->footerStart ? ($this->footerStart - 1) : $sheet->getHighestRow();

                if ($borderEndRow >= $tableDataStart) {
                    $sheet->getStyle("A{$tableDataStart}:Y{$borderEndRow}")->getFont()->setSize(8);
                    $sheet->getStyle("A{$tableDataStart}:Y{$borderEndRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
                    ]);

                    $sheet->getStyle("A{$tableDataStart}:A{$borderEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B{$tableDataStart}:B{$borderEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
                    $sheet->getStyle("C{$tableDataStart}:C{$borderEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("Y{$tableDataStart}:Y{$borderEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);

                    $numericCols = ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X'];
                    foreach ($numericCols as $col) {
                        $sheet->getStyle("{$col}{$tableDataStart}:{$col}{$borderEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    for ($r = $tableDataStart; $r <= $borderEndRow; $r++) {
                        $sheet->getRowDimension($r)->setRowHeight(20);
                    }
                }

                // FOOTER
                if ($this->footerStart) {
                    $lastRow = $sheet->getHighestRow();
                    $sheet->getStyle("A{$this->footerStart}:Y{$lastRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                        'font' => ['bold' => true, 'size' => 9]
                    ]);

                    for ($r = $this->footerStart; $r <= $lastRow; $r++) {
                        $sheet->getRowDimension($r)->setRowHeight(22);
                    }
                }

                // PAGE SETUP
                $lastRow = $sheet->getHighestRow();
                $sheet->getPageSetup()->setPrintArea("A1:Y{$lastRow}");
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToPage(true);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                $sheet->getPageMargins()->setTop(0.3);
                $sheet->getPageMargins()->setRight(0.25);
                $sheet->getPageMargins()->setLeft(0.25);
                $sheet->getPageMargins()->setBottom(0.3);
                $sheet->getPageMargins()->setHeader(0.15);
                $sheet->getPageMargins()->setFooter(0.15);

                $sheet->getPageSetup()->setHorizontalCentered(true);
                $sheet->setShowGridlines(false);
            },
        ];
    }
}
