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

        // Sub-sub header row (new: detailed labels for each column group)
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

        // Capital Section (REFINED: Compute totals first to derive globalX from sum of leaves for accurate weights)
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
            $capitalTotals = $this->calculateOverallTotals($capitalDefinitions, $capitalPlans);
            $this->globalXCapital = (float) $capitalTotals['total_budget'];  // REFINED: Use calculated sum from leaves as denominator for weights

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
            $recurrentTotals = $this->calculateOverallTotals($recurrentDefinitions, $recurrentPlans);
            $this->globalXRecurrent = (float) $recurrentTotals['total_budget'];  // REFINED: Use calculated sum from leaves as denominator for weights

            $row = count($data) + 1;
            $data[] = ['चालू खर्च अन्तर्गतका कार्यक्रमहरुः'];
            $this->headerRows[] = $row;

            $activityStartRow = count($data) + 1;
            $data = array_merge($data, $this->buildActivityRows($recurrentDefinitions, $recurrentPlans, 'recurrent', $activityStartRow, $this->globalXRecurrent));
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
            // REFINED: For parents with children, show totally blank from C to Y
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

        // For leaves: Use definition/plan raw fields, but derive var-like weights locally (no DB var_ dependency)
        $displayTotalQuantity = $definition->total_quantity ?? 0;
        $displayTotalBudget = $definition->total_budget ?? 0;
        $displayCompletedQuantity = $plan->completed_quantity ?? 0;
        $displayPlannedQuantity = $plan->planned_quantity ?? 0;
        $displayQ1Quantity = $plan->q1_quantity ?? 0;
        $displayQ2Quantity = $plan->q2_quantity ?? 0;
        $displayQ3Quantity = $plan->q3_quantity ?? 0;
        $displayQ4Quantity = $plan->q4_quantity ?? 0;
        $displayTotalExpense = $plan->total_expense ?? 0;
        $displayPlannedBudget = $plan->planned_budget ?? 0;
        $displayQ1 = $plan->q1_amount ?? 0;
        $displayQ2 = $plan->q2_amount ?? 0;
        $displayQ3 = $plan->q3_amount ?? 0;
        $displayQ4 = $plan->q4_amount ?? 0;

        $isCapital = $expenditureType === 'capital';
        $budget = $displayTotalBudget;
        $weightBudget = $globalX > 0 ? number_format($budget / $globalX * 100, 2) : '0.00';  // REFINED: Uses calculated globalX (sum of leaves) for accurate (E full / section total full) * 100

        if (!$isCapital) {
            $weightExpense = $weightPlanned = $weightQ1 = $weightQ2 = $weightQ3 = $weightQ4 = '0.00';
        } else {
            // REFINED: Explicitly compute column I as (G / D) * F
            $progressRatio = $displayTotalQuantity > 0 ? $displayCompletedQuantity / $displayTotalQuantity : 0;
            $weightExpense = number_format($progressRatio * (float) str_replace(',', '', $weightBudget), 2);  // (G / D) * F

            // REFINED: Similarly for planned (K = (J / D) * F)
            $plannedRatio = $displayTotalQuantity > 0 ? $displayPlannedQuantity / $displayTotalQuantity : 0;
            $weightPlanned = number_format($plannedRatio * (float) str_replace(',', '', $weightBudget), 2);

            // REFINED: Quarterly weights as (quarter_qty / D) * F
            if ($displayPlannedQuantity > 0 && $displayTotalQuantity > 0) {
                $q1Ratio = $displayQ1Quantity / $displayTotalQuantity;
                $weightQ1 = number_format($q1Ratio * (float) str_replace(',', '', $weightBudget), 2);

                $q2Ratio = $displayQ2Quantity / $displayTotalQuantity;
                $weightQ2 = number_format($q2Ratio * (float) str_replace(',', '', $weightBudget), 2);

                $q3Ratio = $displayQ3Quantity / $displayTotalQuantity;
                $weightQ3 = number_format($q3Ratio * (float) str_replace(',', '', $weightBudget), 2);

                $q4Ratio = $displayQ4Quantity / $displayTotalQuantity;
                $weightQ4 = number_format($q4Ratio * (float) str_replace(',', '', $weightBudget), 2);
            } else {
                $weightQ1 = $weightQ2 = $weightQ3 = $weightQ4 = '0.00';
            }
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
        $budgetWeight = $globalX > 0 ? ($totals['total_budget'] / $globalX) * 100 : 0;  // REFINED: For section total, this will be 100.00; for subtotals, proportional
        $expenseWeight = $calculateVar && $globalX > 0 ? ($totals['weighted_expense_contrib'] / $globalX * 100) : 0;  // Aggregated equivalent of sum((G/D)*F)
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

    private function sumLeafNodes(ProjectActivityDefinition $def, array &$totals, SupportCollection $plans): void
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

    private function calculateOverallTotals(Collection $rootDefinitions, SupportCollection $plans): array
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

                // Set reduced height for top header rows (rows 1-3)
                for ($r = 1; $r <= 3; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(18);
                }

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

                // REFINED: Reduced row height for form section (from 28 to 20 for compactness)
                for ($r = $formStart; $r <= $formEnd; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(20);
                }

                // Note row (adjusted, no blank before)
                $noteRowNum = 22;
                $sheet->getStyle("Y{$noteRowNum}")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 8],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
                ]);
                // FIXED: No border for note row
                $sheet->getStyle("A{$noteRowNum}:Y{$noteRowNum}")->applyFromArray($noBorder);

                // REFINED: Reduced height for note row to match compactness
                $sheet->getRowDimension($noteRowNum)->setRowHeight(16);

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

                // Sub-sub header row styling (new)
                $subSubRow = $tableStart + 1;
                $sheet->getStyle("A{$subSubRow}:Y{$subSubRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 8],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8F8F8']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                $sheet->getRowDimension($subSubRow)->setRowHeight(20);

                // Sub-header row styling (numbers)
                $subRow = $tableStart + 2;
                $sheet->getStyle("A{$subRow}:Y{$subRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 8],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F0F0']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                $sheet->getRowDimension($subRow)->setRowHeight(20);

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

                // ========== PARENT ROWS MERGING ==========
                foreach ($this->parentRows as $row) {
                    $sheet->mergeCells("C{$row}:X{$row}");
                    $sheet->getStyle("C{$row}:X{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                // ========== TABLE DATA STYLING ==========
                $tableDataStart = $tableStart + 3;
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
