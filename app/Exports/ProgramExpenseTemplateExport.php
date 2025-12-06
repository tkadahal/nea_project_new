<?php

namespace App\Exports;

use App\Models\ProjectActivityDefinition;
use App\Models\ProjectActivityPlan;
use App\Models\ProjectExpense;
use App\Models\Project;
use App\Models\FiscalYear;
use App\Models\Budget;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ProgramExpenseTemplateExport implements FromCollection, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    protected $projectTitle;
    protected $fiscalYearTitle;
    protected $projectId;
    protected $fiscalYearId;
    protected $quarter;
    protected $reportingPeriod;
    protected $globalXCapital;
    protected $globalXRecurrent;
    protected $capitalPeriodTotal;
    protected $recurrentPeriodTotal;
    protected $totalAnnualPlanned;
    protected $totalProjectCost;

    // Default values set based on the template structure
    public function __construct($projectTitle = '', $fiscalYearTitle = '२०८१/८२', $projectId = null, $fiscalYearId = null, $quarter = 1)
    {
        $this->projectTitle = $projectTitle;
        $this->fiscalYearTitle = $fiscalYearTitle;
        $this->projectId = $projectId;
        $this->fiscalYearId = $fiscalYearId;
        $this->quarter = $quarter;
        $this->reportingPeriod = $this->getQuarterText($quarter);
    }

    private function getQuarterText(int $quarter): string
    {
        $quarters = [
            1 => 'पहिलो त्रैमासिक',
            2 => 'दोस्रो त्रैमासिक',
            3 => 'तेस्रो त्रैमासिक',
            4 => 'चौथो त्रैमासिक'
        ];
        return $quarters[$quarter] ?? 'वार्षिक';
    }

    private function convertToNepaliDigits(string $input): string
    {
        $map = [
            '0' => '०',
            '1' => '१',
            '2' => '२',
            '3' => '३',
            '4' => '४',
            '5' => '५',
            '6' => '६',
            '7' => '७',
            '8' => '८',
            '9' => '९'
        ];
        return strtr($input, $map);
    }

    private function formatQuantity(float $number, int $decimals = 2): string
    {
        return number_format($number, $decimals);
    }

    private function formatAmount(float $number, int $decimals = 2): string
    {
        $formatted = number_format($number, $decimals);
        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function formatPercent(float $number): string
    {
        return number_format($number, 2);
    }

    public function collection()
    {
        // Fetch project and fiscal year details
        $project = $this->projectId ? Project::with('projectManager')->find($this->projectId) : null;
        $fiscalYear = $this->fiscalYearId ? FiscalYear::find($this->fiscalYearId) : null;
        $managerName = $project?->projectManager?->name ?? '';

        // Fetch budget details
        $budget = null;
        $internalBudget = 0.0;
        $govShareBudget = 0.0;
        $govLoanBudget = 0.0;
        $govBudget = 0.0;
        $foreignLoanBudget = 0.0;
        $foreignSubsidyBudget = 0.0;
        $totalInternalBudget = 0.0;
        $totalForeignBudget = 0.0;
        $totalBudget = 0.0;
        if ($project && $fiscalYear) {
            $budget = Budget::where('project_id', $this->projectId)
                ->where('fiscal_year_id', $this->fiscalYearId)
                ->first();
            $internalBudget = (float) ($budget->internal_budget ?? 0);
            $govShareBudget = (float) ($budget->government_share ?? 0);
            $govLoanBudget = (float) ($budget->government_loan ?? 0);
            $govBudget = $govShareBudget + $govLoanBudget;
            $foreignLoanBudget = (float) ($budget->foreign_loan_budget ?? 0);
            $foreignSubsidyBudget = (float) ($budget->foreign_subsidy_budget ?? 0);
            $totalInternalBudget = $internalBudget + $govBudget;
            $totalForeignBudget = $foreignLoanBudget + $foreignSubsidyBudget;
            $totalBudget = (float) ($budget->total_budget ?? ($totalInternalBudget + $totalForeignBudget));
        }

        // Assume project has ministry and budget_subhead fields; adjust as needed
        $ministry = $project?->ministry ?? '';
        $budgetSubhead = $project?->budget_subhead ?? '';

        // Total 23 columns (A-W, indices 0-22)
        $headerRows = [];

        // Add 15 rows on top
        for ($i = 0; $i < 15; $i++) {
            $headerRows[] = array_fill(0, 23, null);
        }

        // Row 1: नेपाल विद्युत प्राधिकरण (A1)
        $headerRows[0][0] = 'नेपाल विद्युत प्राधिकरण';

        // Row 2: ----------------- वार्षिक प्रतिवेदन (A2)
        $headerRows[1][0] = '----------------- वार्षिक प्रतिवेदन';

        // Row 3 left: १. आ.व. : followed by fiscal year title (A3)
        $headerRows[2][0] = '१. आ.व. : ' . ($fiscalYear?->title ?? $this->fiscalYearTitle);

        // Row 3 right: ७. यस अवधिको खर्च रु : (L3) - to be filled later
        $headerRows[2][11] = '७. यस अवधिको खर्च रु : ';

        // Row 4 left: २. बजेट उपशीर्षक नं : (A4)
        $headerRows[3][0] = '२. बजेट उपशीर्षक नं : ' . $budgetSubhead;

        // Row 4 right: (क) अन्तरिक (L4) - to be filled later
        $headerRows[3][11] = '   (क)  अन्तरिक : ';

        // Row 5 left: ३. मन्त्रालय : (A5)
        $headerRows[4][0] = '३. मन्त्रालय : ' . $ministry;

        // Row 5 right: १) नेपाल सरकार : (L5) - to be filled later
        $headerRows[4][11] = '      १)  नेपाल सरकार : ';

        // Row 6 left: ४. आयोजनाको नाम : followed by project title (A6)
        $headerRows[5][0] = '४. आयोजनाको नाम : ' . $this->projectTitle;

        // Row 6 right: २) ने. वि. प्रा :  (L6) - to be filled later
        $headerRows[5][11] = '      २)  ने. वि. प्रा : ';

        // Row 7 left: ५. आयोजना प्रमुखको नाम : followed by manager name (A7)
        $headerRows[6][0] = '५. आयोजना प्रमुखको नाम : ' . $managerName;

        // Row 7 right: ख) वैदेशिक : (L7) - to be filled later
        $headerRows[6][11] = '   ख)  वैदेशिक : ';

        // Row 8 left: ६. यस अवधिको बजेट : (A8)
        $headerRows[7][0] = '६. यस अवधिको बजेट : ' . $this->formatAmount($totalBudget / 1000, 2);

        // Row 8 right: १)  ऋण रु : (L8) - to be filled later
        $headerRows[7][11] = '      १)  ऋण रु : ';

        // Row 9 left: (क)  अन्तरिक :
        $headerRows[8][0] = '   (क)  अन्तरिक : ' . $this->formatAmount($totalInternalBudget / 1000, 2);

        // Row 9 right: २)  अनुदान रु : (L9) - to be filled later
        $headerRows[8][11] = '      २)  अनुदान रु : ';

        // Row 10 left: १)  नेपाल सरकार : (A10)
        $headerRows[9][0] = '      १)  नेपाल सरकार : ' . $this->formatAmount($govBudget / 1000, 2);

        // Row 10 right: ८. यस अवधिको खर्च : (L10) - to be filled later
        $headerRows[9][11] = '८. यस अवधिको खर्च : ';

        // Row 11 left: २)  ने. वि. प्रा : : (A11)
        $headerRows[10][0] = '      २)  ने. वि. प्रा : ' . $this->formatAmount($internalBudget / 1000, 2);

        // Row 11 right: ९. वार्षिक लक्ष्यको तुलनामा खर्च प्रतिशत : (L11) - to be filled later
        $headerRows[10][11] = '९. वार्षिक लक्ष्यको तुलनामा खर्च प्रतिशत : ';

        // Row 12 left: ख)  वैदेशिक : (A12)
        $headerRows[11][0] = '   ख)  वैदेशिक : ' . $this->formatAmount($totalForeignBudget / 1000, 2);

        // Row 12 right: १०. शुरुदेखि यस अवधिसम्मको खर्च प्रतिशत (कुल लागतको तुलनामा) : (L12) - to be filled later
        $headerRows[11][11] = '१०. शुरुदेखि यस अवधिसम्मको खर्च प्रतिशत (कुल लागतको तुलनामा) : ';

        // Row 13 left: १)  ऋण रु : (A13)
        $headerRows[12][0] = '      १)  ऋण रु : ' . $this->formatAmount($foreignLoanBudget / 1000, 2);

        // Row 13 right: (duplicate/fix: set to १० value if spanning, but treat as same label)
        $headerRows[12][11] = '१०. शुरुदेखि यस अवधिसम्मको खर्च प्रतिशत (कुल लागतको तुलनामा) : ';

        // Row 14 left: २)  अनुदान रु : (A14)
        $headerRows[13][0] = '      २)  अनुदान रु : ' . $this->formatAmount($foreignSubsidyBudget / 1000, 2);

        // Row 14 right: ११. बितेको समय प्रतिशतमा (कुल समयको तुलनामा) : (L14) - to be filled later
        $headerRows[13][11] = '११. बितेको समय प्रतिशतमा (कुल समयको तुलनामा) : ';

        // Row 15 left: '' - approximate
        $headerRows[14][0] = '';

        // Row 15 right: १२. शुरुदेखि यस अवधिसम्मको भौतिक प्रगति प्रतिशतमा : (L15) - to be filled later
        $headerRows[14][11] = '१२. शुरुदेखि यस अवधिसम्मको भौतिक प्रगति प्रतिशतमा : ';

        // Row 16: (रकम रु. हजारमा) (V16:W16)
        $headerRows[] = array_fill(0, 23, null);
        $headerRows[15][21] = '(रकम रु. हजारमा)';

        // Row 17: Main Headers
        $headerRows[] = array_fill(0, 23, null);
        $headerRows[16][0] = 'क्रम संख्या';
        $headerRows[16][1] = 'कार्यक्रम/क्रियाकलापहरु';
        $headerRows[16][2] = 'एकाइ';
        $headerRows[16][6] = 'वार्षिक लक्ष्य';
        $periodTargetHeader = 'त्रैमशिक अवधिसम्मको लक्ष्य';
        $headerRows[16][9] = $periodTargetHeader;
        $headerRows[16][12] = 'त्रैमासिक / वार्षिक प्रगति (भौतिक)';
        $headerRows[16][15] = 'त्रैमासिक / वार्षिक प्रगति (वित्तिय)';
        $periodPhysicalHeader = 'त्रैमशिक अवधिसम्मको भौतिक प्रगति (बहुवर्षीय आयोजनाको हकमा)';
        $headerRows[16][17] = $periodPhysicalHeader;
        $periodExpenseHeader = 'त्रैमशिक अवधिसम्मको खर्च';
        $headerRows[16][20] = $periodExpenseHeader;
        $headerRows[16][22] = 'कैफियत';

        // Row 18: Sub Headers
        $headerRows[] = array_fill(0, 23, null);
        $headerRows[17][0] = 'सङ्ख्या';
        $headerRows[17][1] = 'क्रियाकलापहरु';
        $headerRows[17][2] = null;

        // D18-F18: आयोजना अवधिसम्मको लक्ष्य
        $headerRows[17][3] = 'परिमाण';
        $headerRows[17][4] = 'भार';
        $headerRows[17][5] = 'बजेट';

        // G18-I18: वार्षिक लक्ष्य
        $headerRows[17][6] = 'परिमाण';
        $headerRows[17][7] = 'भार';
        $headerRows[17][8] = 'बजेट';

        // J18-L18: यस अवधिसम्मको लक्ष्य
        $headerRows[17][9] = 'परिमाण';
        $headerRows[17][10] = 'भार';
        $headerRows[17][11] = 'बजेट';

        // M18-O18: चौमासिक / वार्षिक प्रगति
        $headerRows[17][12] = 'परिमाण';
        $headerRows[17][13] = 'भार';
        $headerRows[17][14] = 'प्रतिशत';

        // P18-Q18: चौमासिक / वार्षिक प्रगति (वित्तिय)
        $headerRows[17][15] = 'खर्च';
        $headerRows[17][16] = 'खर्च प्रतिशत';

        // R18-T18: यस अवधिसम्मको भौतिक प्रगति
        $headerRows[17][17] = 'परिमाण';
        $headerRows[17][18] = 'भार';
        $headerRows[17][19] = 'प्रतिशत';

        // U18-V18: यस अवधिसम्मको खर्च
        $headerRows[17][20] = 'रकम रु.';
        $headerRows[17][21] = 'प्रतिशत';

        // W18: कैफियत (empty for merge)
        // Already null

        // Row 19: Number headers
        $numberRow = array_fill(0, 23, null);
        for ($col = 0; $col < 23; $col++) {
            $numberRow[$col] = $this->convertToNepaliDigits((string) ($col + 1));
        }
        $headerRows[] = $numberRow;

        if (!$this->projectId || !$this->fiscalYearId || !$project || !$fiscalYear) {
            // Fallback to template with empty rows
            $dataRows = [];
            for ($i = 0; $i < 20; $i++) {
                $dataRows[] = array_fill(0, 23, '');
            }
            return collect(array_merge($headerRows, $dataRows));
        }

        // Fetch all project expenses for the fiscal year to compute period totals (using project relationship)
        $allExpenses = $project->expenses()
            ->with('quarters')
            ->where('fiscal_year_id', $this->fiscalYearId)
            ->get();

        $periodSpent = [
            'internal' => 0.0,
            'government_share' => 0.0,
            'government_loan' => 0.0,
            'foreign_loan' => 0.0,
            'foreign_subsidy' => 0.0,
        ];
        foreach ($allExpenses as $exp) {
            $type = $exp->budget_type;
            if (isset($periodSpent[$type])) {
                foreach ($exp->quarters as $qtr) {
                    if ($qtr->quarter <= $this->quarter) {
                        $periodSpent[$type] += (float) ($qtr->amount ?? 0);
                    }
                }
            }
        }
        $spent_internal = $periodSpent['internal'];
        $spent_gov_share = $periodSpent['government_share'];
        $spent_gov_loan = $periodSpent['government_loan'];
        $spent_gov = $spent_gov_share + $spent_gov_loan;
        $spent_internal_total = $spent_internal + $spent_gov;
        $spent_foreign_loan = $periodSpent['foreign_loan'];
        $spent_foreign_subsidy = $periodSpent['foreign_subsidy'];
        $spent_foreign_total = $spent_foreign_loan + $spent_foreign_subsidy;
        $total_period_spent = $spent_internal_total + $spent_foreign_total;

        // Fill expense amounts in headers
        $headerRows[2][11] .= $this->formatAmount($total_period_spent / 1000, 2);
        $headerRows[3][11] .= $this->formatAmount($spent_internal_total / 1000, 2);
        $headerRows[4][11] .= $this->formatAmount($spent_gov / 1000, 2);
        $headerRows[5][11] .= $this->formatAmount($spent_internal / 1000, 2);
        $headerRows[6][11] .= $this->formatAmount($spent_foreign_total / 1000, 2);
        $headerRows[7][11] .= $this->formatAmount($spent_foreign_loan / 1000, 2);
        $headerRows[8][11] .= $this->formatAmount($spent_foreign_subsidy / 1000, 2);
        $headerRows[9][11] .= $this->formatAmount($total_period_spent / 1000, 2);

        // Fetch active definitions for the project
        $definitions = ProjectActivityDefinition::forProject($this->projectId)
            ->active()
            ->get([
                'id',
                'parent_id',
                'program',
                'expenditure_id',
                'total_budget',
                'total_quantity',
            ]);

        // Fetch plans for the fiscal year with definitions
        $defIds = $definitions->pluck('id');
        $activities = ProjectActivityPlan::whereIn('activity_definition_id', $defIds)
            ->where('fiscal_year_id', $this->fiscalYearId)
            ->active()
            ->with('activityDefinition')
            ->get([
                'id',
                'activity_definition_id',
                'program_override',
                'planned_quantity',
                'planned_budget',
                'q1_quantity',
                'q2_quantity',
                'q3_quantity',
                'q4_quantity',
                'q1_amount',
                'q2_amount',
                'q3_amount',
                'q4_amount',
            ]);

        // Only proceed if activities exist
        if ($activities->isEmpty()) {
            $dataRows = [];
            for ($i = 0; $i < 20; $i++) {
                $dataRows[] = array_fill(0, 23, '');
            }
            // Fill remaining header percents (using defaults where possible)
            $time_percent = ($this->quarter / 4) * 100;
            $headerRows[13][11] .= $this->formatPercent($time_percent);
            $headerRows[14][11] .= '०.००'; // Default physical if no data
            return collect(array_merge($headerRows, $dataRows));
        }

        // Load expenses separately and attach to activities (activity-level expenses)
        $planIds = $activities->pluck('id');
        $expensesCollection = ProjectExpense::with(['quarters'])
            ->whereIn('project_activity_plan_id', $planIds)
            ->get()
            ->groupBy('project_activity_plan_id');

        $activities = $activities->map(function ($act) use ($expensesCollection) {
            $act->setRelation('expenses', $expensesCollection->get($act->id, collect()));
            return $act;
        });

        // Key activities (plans) by definition ID for easy access
        $activityMap = $activities->keyBy(fn($plan) => $plan->activityDefinition->id);
        $groupedActivities = $activities->groupBy(fn($plan) => $plan->activityDefinition->parent_id ?? 'null');

        $capital_roots = $activities->filter(fn($plan) => is_null($plan->activityDefinition->parent_id) && $plan->activityDefinition->expenditure_id == 1);
        $recurrent_roots = $activities->filter(fn($plan) => is_null($plan->activityDefinition->parent_id) && $plan->activityDefinition->expenditure_id == 2);

        // Build hasChildrenMap for parents
        $hasChildrenMap = $groupedActivities->keys()->filter(fn($key) => $key !== 'null')->values()->toArray();

        // Precompute leaf values for all activities (using definition ID as key)
        $leafValues = [];
        foreach ($activities as $act) {
            $defId = $act->activityDefinition->id;
            $period_qty_planned = 0.0;
            $period_amt_planned = 0.0;
            for ($i = 1; $i <= $this->quarter; $i++) {
                $period_qty_planned += (float) ($act->{"q{$i}_quantity"} ?? 0);
                $period_amt_planned += (float) ($act->{"q{$i}_amount"} ?? 0);
            }

            $period_qty_actual = 0.0;
            $period_amt_actual = 0.0;
            $quarter_qty_actual = 0.0;
            $quarter_amt_actual = 0.0;
            foreach ($act->expenses as $exp) {
                foreach ($exp->quarters as $qtr) {
                    if ($qtr->quarter <= $this->quarter) {
                        $period_qty_actual += (float) $qtr->quantity;
                        $period_amt_actual += (float) $qtr->amount;
                    }
                    if ($qtr->quarter == $this->quarter) {
                        $quarter_qty_actual += (float) $qtr->quantity;
                        $quarter_amt_actual += (float) $qtr->amount;
                    }
                }
            }

            $total_qty = (float) ($act->activityDefinition->total_quantity ?? 0);
            $total_budget = (float) ($act->activityDefinition->total_budget ?? 0);
            $annual_qty = (float) ($act->planned_quantity ?? 0);
            $annual_amt = (float) ($act->planned_budget ?? 0);
            $quarter_amt_planned = (float) ($act->{"q{$this->quarter}_amount"} ?? 0);

            // If this is a parent (has children), zero out planned fields to avoid double-counting in subtrees
            if (in_array($defId, $hasChildrenMap)) {
                $annual_qty = 0.0;
                $annual_amt = 0.0;
                $period_qty_planned = 0.0;
                $period_amt_planned = 0.0;
                $quarter_amt_planned = 0.0;
            }

            $weighted_annual_qty = $total_qty > 0 ? ($annual_qty / $total_qty * $total_budget) : 0.0;
            $weighted_quarter_physical = $total_qty > 0 ? ($quarter_qty_actual / $total_qty * $total_budget) : 0.0;
            $weighted_period_physical = $total_qty > 0 ? ($period_qty_actual / $total_qty * $total_budget) : 0.0;

            $leafValues[$defId] = [
                'total_qty' => $total_qty,
                'total_budget' => $total_budget,
                'annual_qty' => $annual_qty,
                'annual_amt' => $annual_amt,
                'period_qty_planned' => $period_qty_planned,
                'period_amt_planned' => $period_amt_planned,
                'period_qty_actual' => $period_qty_actual,
                'period_amt_actual' => $period_amt_actual,
                'quarter_qty_actual' => $quarter_qty_actual,
                'quarter_amt_actual' => $quarter_amt_actual,
                'quarter_amt_planned' => $quarter_amt_planned,
                'weighted_annual_qty' => $weighted_annual_qty,
                'weighted_quarter_physical' => $weighted_quarter_physical,
                'weighted_period_physical' => $weighted_period_physical,
            ];
        }

        // Single recursive computation for all subtree sums (keyed by definition ID)
        $subtreeSums = [];
        $defaultSums = [
            'total_qty' => 0.0,
            'total_budget' => 0.0,
            'annual_qty' => 0.0,
            'annual_amt' => 0.0,
            'period_qty_planned' => 0.0,
            'period_amt_planned' => 0.0,
            'period_qty_actual' => 0.0,
            'period_amt_actual' => 0.0,
            'quarter_qty_actual' => 0.0,
            'quarter_amt_actual' => 0.0,
            'quarter_amt_planned' => 0.0,
            'weighted_annual_qty' => 0.0,
            'weighted_quarter_physical' => 0.0,
            'weighted_period_physical' => 0.0,
        ];
        $computeSubtreeSums = function ($defId) use ($leafValues, $groupedActivities, $defaultSums, &$subtreeSums, &$computeSubtreeSums) {
            if (isset($subtreeSums[$defId])) {
                return $subtreeSums[$defId];
            }

            $sums = $leafValues[$defId] ?? $defaultSums;

            $children = $groupedActivities[$defId] ?? collect();
            foreach ($children as $child) {
                $childDefId = $child->activityDefinition->id;
                $childSums = $computeSubtreeSums($childDefId);
                foreach ($sums as $key => &$value) {
                    $value += $childSums[$key];
                }
            }

            $subtreeSums[$defId] = $sums;
            return $sums;
        };

        $allRoots = $capital_roots->concat($recurrent_roots);
        foreach ($allRoots as $root) {
            $computeSubtreeSums($root->activityDefinition->id);
        }

        // Compute globals using subtree sums for consistency
        $this->globalXCapital = $capital_roots->isEmpty() ? 0.0 : $capital_roots->sum(fn($root) => $subtreeSums[$root->activityDefinition->id]['total_budget']);
        $this->globalXRecurrent = $recurrent_roots->isEmpty() ? 0.0 : $recurrent_roots->sum(fn($root) => $subtreeSums[$root->activityDefinition->id]['total_budget']);
        $this->totalProjectCost = $this->globalXCapital + $this->globalXRecurrent;
        $this->capitalPeriodTotal = $capital_roots->isEmpty() ? 0.0 : $capital_roots->sum(fn($root) => $subtreeSums[$root->activityDefinition->id]['period_amt_planned']);
        $this->recurrentPeriodTotal = $recurrent_roots->isEmpty() ? 0.0 : $recurrent_roots->sum(fn($root) => $subtreeSums[$root->activityDefinition->id]['period_amt_planned']);
        $capitalAnnualPlanned = $capital_roots->isEmpty() ? 0.0 : $capital_roots->sum(fn($root) => $subtreeSums[$root->activityDefinition->id]['annual_amt']);
        $recurrentAnnualPlanned = $recurrent_roots->isEmpty() ? 0.0 : $recurrent_roots->sum(fn($root) => $subtreeSums[$root->activityDefinition->id]['annual_amt']);
        $this->totalAnnualPlanned = $capitalAnnualPlanned + $recurrentAnnualPlanned;

        // Compute cumulative spent for १० (from project start to current period)
        $cumulativeSpent = $total_period_spent;
        $pastFys = FiscalYear::where('start_date', '<', $fiscalYear->start_date)->get();
        foreach ($pastFys as $pastFy) {
            $pastExpenses = $project->expenses()
                ->with('quarters')
                ->where('fiscal_year_id', $pastFy->id)
                ->get();
            foreach ($pastExpenses as $exp) {
                foreach ($exp->quarters as $qtr) {
                    $cumulativeSpent += (float) ($qtr->amount ?? 0);
                }
            }
        }
        $cumulative_percent = $this->totalProjectCost > 0 ? ($cumulativeSpent / $this->totalProjectCost * 100) : 0.0;

        // Fill header percents
        $annual_expense_percent = $this->totalAnnualPlanned > 0 ? ($total_period_spent / $this->totalAnnualPlanned * 100) : 0.0;
        $headerRows[10][11] .= $this->formatPercent($annual_expense_percent);
        $headerRows[11][11] .= $this->formatPercent($cumulative_percent);
        $headerRows[12][11] .= $this->formatPercent($cumulative_percent); // Duplicate row if spanning
        $time_percent = ($this->quarter / 4) * 100;
        $headerRows[13][11] .= $this->formatPercent($time_percent);

        // Build flat hierarchical data rows
        $dataRows = [];

        // Recursive traverse function (now simplified, no accumulation)
        $traverse = function ($acts, $level = 0, $path = [], $globalX, $periodTotal) use (&$dataRows, &$traverse, $groupedActivities, $subtreeSums) {
            foreach ($acts as $index => $act) {
                $def = $act->activityDefinition;
                $defId = $def->id;
                $currentPath = array_merge($path, [$index + 1]);
                $serial = implode('.', $currentPath);
                $parentSerial = $serial; // Use the current serial for total row labeling
                $indent = str_repeat('  ', $level * 2);
                $effectiveProgram = $act->program_override ?? $def->program ?? '';

                $row = array_fill(0, 23, '');
                $row[0] = $this->convertToNepaliDigits($serial);
                $row[1] = $indent . $effectiveProgram;
                $row[2] = ''; // Unit

                $children = $groupedActivities[$defId] ?? collect();
                $hasChildren = $children->isNotEmpty();

                if (!$hasChildren) {
                    // Leaf row: use subtree sums (equals leaf values)
                    $subtree = $subtreeSums[$defId];

                    $row[3] = $this->formatQuantity($subtree['total_qty'], 2);
                    $weightProject = $globalX > 0 ? ($subtree['total_budget'] / $globalX * 100) : 0;
                    $row[4] = $this->formatPercent($weightProject);
                    $row[5] = $this->formatAmount($subtree['total_budget'] / 1000, 2);

                    $row[6] = $this->formatQuantity($subtree['annual_qty'], 2);
                    $weightAnnual = $globalX > 0 ? ($subtree['weighted_annual_qty'] / $globalX * 100) : 0;
                    $row[7] = $this->formatPercent($weightAnnual);
                    $row[8] = $this->formatAmount($subtree['annual_amt'] / 1000, 2);

                    $row[9] = $this->formatQuantity($subtree['period_qty_planned'], 2);
                    $weightPeriod = $periodTotal > 0 ? ($subtree['period_amt_planned'] / $periodTotal * 100) : 0;
                    $row[10] = $this->formatPercent($weightPeriod);
                    $row[11] = $this->formatAmount($subtree['period_amt_planned'] / 1000, 2);

                    $row[12] = $this->formatQuantity($subtree['quarter_qty_actual'], 2);
                    $weightQuarter = $globalX > 0 ? ($subtree['weighted_quarter_physical'] / $globalX * 100) : 0;
                    $row[13] = $this->formatPercent($weightQuarter);
                    $quarterPerc = $subtree['annual_qty'] > 0 ? ($subtree['quarter_qty_actual'] / $subtree['annual_qty'] * 100) : 0;
                    $row[14] = $this->formatPercent($quarterPerc);

                    $row[15] = $this->formatAmount($subtree['quarter_amt_actual'] / 1000, 2);
                    $quarterExpPerc = $subtree['quarter_amt_planned'] > 0 ? ($subtree['quarter_amt_actual'] / $subtree['quarter_amt_planned'] * 100) : 0;
                    $row[16] = $this->formatPercent($quarterExpPerc);

                    $row[17] = $this->formatQuantity($subtree['period_qty_actual'], 2);
                    $weightPeriodPhysical = $globalX > 0 ? ($subtree['weighted_period_physical'] / $globalX * 100) : 0;
                    $row[18] = $this->formatPercent($weightPeriodPhysical);
                    $periodPerc = $subtree['annual_qty'] > 0 ? ($subtree['period_qty_actual'] / $subtree['annual_qty'] * 100) : 0;
                    $row[19] = $this->formatPercent($periodPerc);

                    $row[20] = $this->formatAmount($subtree['period_amt_actual'] / 1000, 2);
                    $expPerc = $subtree['period_amt_planned'] > 0 ? ($subtree['period_amt_actual'] / $subtree['period_amt_planned'] * 100) : 0;
                    $row[21] = $this->formatPercent($expPerc);

                    $row[22] = ''; // Remarks
                } else {
                    // Parent row: zeros for quantities/budgets, empty for weights, zero for percents
                    $row[3] = $this->formatQuantity(0, 2);
                    $row[4] = '';
                    $row[5] = $this->formatAmount(0 / 1000, 2);

                    $row[6] = $this->formatQuantity(0, 2);
                    $row[7] = '';
                    $row[8] = $this->formatAmount(0 / 1000, 2);

                    $row[9] = $this->formatQuantity(0, 2);
                    $row[10] = '';
                    $row[11] = $this->formatAmount(0 / 1000, 2);

                    $row[12] = $this->formatQuantity(0, 2);
                    $row[13] = '';
                    $row[14] = $this->formatPercent(0);

                    $row[15] = $this->formatAmount(0 / 1000, 2);
                    $row[16] = $this->formatPercent(0);

                    $row[17] = $this->formatQuantity(0, 2);
                    $row[18] = '';
                    $row[19] = $this->formatPercent(0);

                    $row[20] = $this->formatAmount(0 / 1000, 2);
                    $row[21] = $this->formatPercent(0);

                    $row[22] = '';
                }

                $dataRows[] = $row;

                if ($hasChildren) {
                    $traverse($children, $level + 1, $currentPath, $globalX, $periodTotal);

                    // Add total row for this parent
                    $totalRow = array_fill(0, 23, '');
                    $totalRow[0] = '';
                    $totalRow[1] = $indent . 'Total of ' . $parentSerial;
                    $totalRow[2] = '';

                    $subtree = $subtreeSums[$defId];

                    // Quantities (now filled with sums instead of blank)
                    $totalRow[3] = $this->formatQuantity($subtree['total_qty'], 2);
                    $totalRow[6] = $this->formatQuantity($subtree['annual_qty'], 2);
                    $totalRow[9] = $this->formatQuantity($subtree['period_qty_planned'], 2);
                    $totalRow[12] = $this->formatQuantity($subtree['quarter_qty_actual'], 2);
                    $totalRow[17] = $this->formatQuantity($subtree['period_qty_actual'], 2);

                    // Weights
                    $weightProject = $globalX > 0 ? ($subtree['total_budget'] / $globalX * 100) : 0;
                    $totalRow[4] = $this->formatPercent($weightProject);

                    $weightAnnual = $globalX > 0 ? ($subtree['weighted_annual_qty'] / $globalX * 100) : 0;
                    $totalRow[7] = $this->formatPercent($weightAnnual);

                    $weightPeriod = $periodTotal > 0 ? ($subtree['period_amt_planned'] / $periodTotal * 100) : 0;
                    $totalRow[10] = $this->formatPercent($weightPeriod);

                    $weightQuarter = $globalX > 0 ? ($subtree['weighted_quarter_physical'] / $globalX * 100) : 0;
                    $totalRow[13] = $this->formatPercent($weightQuarter);

                    $weightPeriodPhysical = $globalX > 0 ? ($subtree['weighted_period_physical'] / $globalX * 100) : 0;
                    $totalRow[18] = $this->formatPercent($weightPeriodPhysical);

                    // Budgets
                    $totalRow[5] = $this->formatAmount($subtree['total_budget'] / 1000, 2);
                    $totalRow[8] = $this->formatAmount($subtree['annual_amt'] / 1000, 2);
                    $totalRow[11] = $this->formatAmount($subtree['period_amt_planned'] / 1000, 2);

                    // Percents
                    $quarterPerc = $subtree['annual_qty'] > 0 ? ($subtree['quarter_qty_actual'] / $subtree['annual_qty'] * 100) : 0;
                    $totalRow[14] = $this->formatPercent($quarterPerc);

                    $quarterExpPerc = $subtree['quarter_amt_planned'] > 0 ? ($subtree['quarter_amt_actual'] / $subtree['quarter_amt_planned'] * 100) : 0;
                    $totalRow[16] = $this->formatPercent($quarterExpPerc);

                    $periodPerc = $subtree['annual_qty'] > 0 ? ($subtree['period_qty_actual'] / $subtree['annual_qty'] * 100) : 0;
                    $totalRow[19] = $this->formatPercent($periodPerc);

                    $totalRow[20] = $this->formatAmount($subtree['period_amt_actual'] / 1000, 2);
                    $expPerc = $subtree['period_amt_planned'] > 0 ? ($subtree['period_amt_actual'] / $subtree['period_amt_planned'] * 100) : 0;
                    $totalRow[21] = $this->formatPercent($expPerc);

                    $totalRow[22] = '';

                    $dataRows[] = $totalRow;
                }
            }
        };

        $defaultSectionSums = $defaultSums;
        $capitalSectionSums = $defaultSectionSums;
        $recurrentSectionSums = $defaultSectionSums;

        // Capital Section
        $capitalHeader = array_fill(0, 23, null);
        $capitalHeader[0] = 'पुँजीगत वर्षान्तक कार्यक्रम :';
        $dataRows[] = $capitalHeader;

        if ($capital_roots->isNotEmpty()) {
            $traverse($capital_roots, 0, [], $this->globalXCapital, $this->capitalPeriodTotal);

            // Capital Section Total
            $sectionSums = $defaultSectionSums;
            foreach ($capital_roots as $root) {
                $rootDefId = $root->activityDefinition->id;
                $rootSums = $subtreeSums[$rootDefId];
                foreach ($sectionSums as $key => &$value) {
                    $value += $rootSums[$key];
                }
            }
            $capitalSectionSums = $sectionSums;

            $capitalTotalRow = array_fill(0, 23, '');
            $capitalTotalRow[1] = '(क) जम्मा';

            // Quantities (now filled with sums instead of blank)
            $capitalTotalRow[3] = $this->formatQuantity($sectionSums['total_qty'], 2);
            $capitalTotalRow[6] = $this->formatQuantity($sectionSums['annual_qty'], 2);
            $capitalTotalRow[9] = $this->formatQuantity($sectionSums['period_qty_planned'], 2);
            $capitalTotalRow[12] = $this->formatQuantity($sectionSums['quarter_qty_actual'], 2);
            $capitalTotalRow[17] = $this->formatQuantity($sectionSums['period_qty_actual'], 2);

            $subtree = $sectionSums;
            $globalX = $this->globalXCapital;
            $periodTotal = $this->capitalPeriodTotal;

            // Weights
            $weightProject = $globalX > 0 ? ($subtree['total_budget'] / $globalX * 100) : 0;
            $capitalTotalRow[4] = $this->formatPercent($weightProject);

            $weightAnnual = $globalX > 0 ? ($subtree['weighted_annual_qty'] / $globalX * 100) : 0;
            $capitalTotalRow[7] = $this->formatPercent($weightAnnual);

            $weightPeriod = $periodTotal > 0 ? ($subtree['period_amt_planned'] / $periodTotal * 100) : 0;
            $capitalTotalRow[10] = $this->formatPercent($weightPeriod);

            $weightQuarter = $globalX > 0 ? ($subtree['weighted_quarter_physical'] / $globalX * 100) : 0;
            $capitalTotalRow[13] = $this->formatPercent($weightQuarter);

            $weightPeriodPhysical = $globalX > 0 ? ($subtree['weighted_period_physical'] / $globalX * 100) : 0;
            $capitalTotalRow[18] = $this->formatPercent($weightPeriodPhysical);

            // Budgets
            $capitalTotalRow[5] = $this->formatAmount($subtree['total_budget'] / 1000, 2);
            $capitalTotalRow[8] = $this->formatAmount($subtree['annual_amt'] / 1000, 2);
            $capitalTotalRow[11] = $this->formatAmount($subtree['period_amt_planned'] / 1000, 2);

            // Percents
            $quarterPerc = $subtree['annual_qty'] > 0 ? ($subtree['quarter_qty_actual'] / $subtree['annual_qty'] * 100) : 0;
            $capitalTotalRow[14] = $this->formatPercent($quarterPerc);

            $quarterExpPerc = $subtree['quarter_amt_planned'] > 0 ? ($subtree['quarter_amt_actual'] / $subtree['quarter_amt_planned'] * 100) : 0;
            $capitalTotalRow[16] = $this->formatPercent($quarterExpPerc);

            $periodPerc = $subtree['annual_qty'] > 0 ? ($subtree['period_qty_actual'] / $subtree['annual_qty'] * 100) : 0;
            $capitalTotalRow[19] = $this->formatPercent($periodPerc);

            $capitalTotalRow[20] = $this->formatAmount($subtree['period_amt_actual'] / 1000, 2);
            $expPerc = $subtree['period_amt_planned'] > 0 ? ($subtree['period_amt_actual'] / $subtree['period_amt_planned'] * 100) : 0;
            $capitalTotalRow[21] = $this->formatPercent($expPerc);

            $capitalTotalRow[22] = '';

            $dataRows[] = $capitalTotalRow;
        }

        // Recurrent Section
        $recurrentHeader = array_fill(0, 23, null);
        $recurrentHeader[0] = '(ख) वाह्य सहायता कार्यक्रम';
        $dataRows[] = $recurrentHeader;

        if ($recurrent_roots->isNotEmpty()) {
            $traverse($recurrent_roots, 0, [], $this->globalXRecurrent, $this->recurrentPeriodTotal);

            // Recurrent Section Total
            $sectionSums = $defaultSectionSums;
            foreach ($recurrent_roots as $root) {
                $rootDefId = $root->activityDefinition->id;
                $rootSums = $subtreeSums[$rootDefId];
                foreach ($sectionSums as $key => &$value) {
                    $value += $rootSums[$key];
                }
            }
            $recurrentSectionSums = $sectionSums;

            $recurrentTotalRow = array_fill(0, 23, '');
            $recurrentTotalRow[1] = '(ख) जम्मा';

            // Quantities (now filled with sums instead of blank)
            $recurrentTotalRow[3] = $this->formatQuantity($sectionSums['total_qty'], 2);
            $recurrentTotalRow[6] = $this->formatQuantity($sectionSums['annual_qty'], 2);
            $recurrentTotalRow[9] = $this->formatQuantity($sectionSums['period_qty_planned'], 2);
            $recurrentTotalRow[12] = $this->formatQuantity($sectionSums['quarter_qty_actual'], 2);
            $recurrentTotalRow[17] = $this->formatQuantity($sectionSums['period_qty_actual'], 2);

            $subtree = $sectionSums;
            $globalX = $this->globalXRecurrent;
            $periodTotal = $this->recurrentPeriodTotal;

            // Weights
            $weightProject = $globalX > 0 ? ($subtree['total_budget'] / $globalX * 100) : 0;
            $recurrentTotalRow[4] = $this->formatPercent($weightProject);

            $weightAnnual = $globalX > 0 ? ($subtree['weighted_annual_qty'] / $globalX * 100) : 0;
            $recurrentTotalRow[7] = $this->formatPercent($weightAnnual);

            $weightPeriod = $periodTotal > 0 ? ($subtree['period_amt_planned'] / $periodTotal * 100) : 0;
            $recurrentTotalRow[10] = $this->formatPercent($weightPeriod);

            $weightQuarter = $globalX > 0 ? ($subtree['weighted_quarter_physical'] / $globalX * 100) : 0;
            $recurrentTotalRow[13] = $this->formatPercent($weightQuarter);

            $weightPeriodPhysical = $globalX > 0 ? ($subtree['weighted_period_physical'] / $globalX * 100) : 0;
            $recurrentTotalRow[18] = $this->formatPercent($weightPeriodPhysical);

            // Budgets
            $recurrentTotalRow[5] = $this->formatAmount($subtree['total_budget'] / 1000, 2);
            $recurrentTotalRow[8] = $this->formatAmount($subtree['annual_amt'] / 1000, 2);
            $recurrentTotalRow[11] = $this->formatAmount($subtree['period_amt_planned'] / 1000, 2);

            // Percents
            $quarterPerc = $subtree['annual_qty'] > 0 ? ($subtree['quarter_qty_actual'] / $subtree['annual_qty'] * 100) : 0;
            $recurrentTotalRow[14] = $this->formatPercent($quarterPerc);

            $quarterExpPerc = $subtree['quarter_amt_planned'] > 0 ? ($subtree['quarter_amt_actual'] / $subtree['quarter_amt_planned'] * 100) : 0;
            $recurrentTotalRow[16] = $this->formatPercent($quarterExpPerc);

            $periodPerc = $subtree['annual_qty'] > 0 ? ($subtree['period_qty_actual'] / $subtree['annual_qty'] * 100) : 0;
            $recurrentTotalRow[19] = $this->formatPercent($periodPerc);

            $recurrentTotalRow[20] = $this->formatAmount($subtree['period_amt_actual'] / 1000, 2);
            $expPerc = $subtree['period_amt_planned'] > 0 ? ($subtree['period_amt_actual'] / $subtree['period_amt_planned'] * 100) : 0;
            $recurrentTotalRow[21] = $this->formatPercent($expPerc);

            $recurrentTotalRow[22] = '';

            $dataRows[] = $recurrentTotalRow;
        }

        // Compute overall physical progress for header १२
        $total_annual_qty = $capitalSectionSums['annual_qty'] + $recurrentSectionSums['annual_qty'];
        $total_period_qty_actual = $capitalSectionSums['period_qty_actual'] + $recurrentSectionSums['period_qty_actual'];
        $physical_percent = $total_annual_qty > 0 ? ($total_period_qty_actual / $total_annual_qty * 100) : 0.0;
        $headerRows[14][11] .= $this->formatPercent($physical_percent);

        return collect(array_merge($headerRows, $dataRows));
    }

    public function title(): string
    {
        $safeTitle = str_replace(['/', '\\'], '_', $this->fiscalYearTitle);
        return 'Progress Report ' . $safeTitle;
    }

    public function styles(Worksheet $sheet)
    {
        // Merge full columns for row 1 and row 2
        $sheet->mergeCells('A1:W1');
        $sheet->mergeCells('A2:W2');

        // For rows 3 to 15, split into two halves (A-K and L-W)
        for ($i = 3; $i <= 15; $i++) {
            $sheet->mergeCells("A{$i}:K{$i}");
            $sheet->mergeCells("L{$i}:W{$i}");
        }

        // Row 16: (रकम रु. हजारमा) (V16:W16)
        $sheet->mergeCells('V16:W16');
        $sheet->getStyle('V16')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('V16')->getFont()->setSize(10);

        // --- HEADER MERGES (Rows 17 & 18) ---

        // Vertical Merges
        $sheet->mergeCells('A17:A18');
        $sheet->mergeCells('B17:B18');
        $sheet->mergeCells('C17:C18');
        $sheet->mergeCells('W17:W18');

        // Horizontal Merges (Row 17)
        $sheet->mergeCells('D17:F17');
        $sheet->mergeCells('G17:I17');
        $sheet->mergeCells('J17:L17');
        $sheet->mergeCells('M17:O17');
        $sheet->mergeCells('P17:Q17');
        $sheet->mergeCells('R17:T17');
        $sheet->mergeCells('U17:V17');

        // --- STYLES FOR HEADERS ---
        $headerRange = 'A17:W18';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true);
        $sheet->getStyle($headerRange)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);

        // Row 19: Number headers
        $sheet->getStyle('A19:W19')->getFont()->setSize(9)->setBold(true);
        $sheet->getStyle('A19:W19')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A19:W19')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
        $sheet->getStyle('A19:W19')->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);
        $sheet->getRowDimension(19)->setRowHeight(20);

        // Dynamic data rows styling (from row 20 to last row)
        $lastRow = $sheet->getHighestRow();
        for ($row = 20; $row <= $lastRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(20);
            $dataRange = 'A' . $row . ':W' . $row;
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
                'alignment' => [
                    'wrapText' => true,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            // Alignment
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row . ':V' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('W' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            // Reduce font size for data rows
            $sheet->getStyle($dataRange)->getFont()->setSize(8);
        }

        // Set font for Devanagari support
        $fullRange = 'A1:W' . $lastRow;
        $sheet->getStyle($fullRange)->getFont()->setName('Nirmala UI');

        // Adjust row heights for headers
        $sheet->getRowDimension(16)->setRowHeight(18);
        $sheet->getRowDimension(17)->setRowHeight(35);
        $sheet->getRowDimension(18)->setRowHeight(35);

        // Style for top rows
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        // Style for form fields rows 3-15
        for ($i = 3; $i <= 15; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(25);
            $sheet->getStyle("A{$i}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$i}")->getFont()->setSize(10);
            $sheet->getStyle("L{$i}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("L{$i}")->getFont()->setSize(10);
        }

        // Bold for sub-items with (क), (ख), etc.
        $sheet->getStyle('A6:A9')->getFont()->setBold(true);
        $sheet->getStyle('L7:L11')->getFont()->setBold(true);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   // क्रम सङ्ख्या
            'B' => 30,  // कार्यक्रम/क्रियाकलापहरु
            'C' => 6,   // एकाइ
            'D' => 8,   // परिमाण (hidden)
            'E' => 6,   // भार (hidden)
            'F' => 8,   // बजेट (hidden)
            'G' => 8,   // परिमाण
            'H' => 6,   // भार
            'I' => 8,   // बजेट
            'J' => 8,   // परिमाण
            'K' => 6,   // भार
            'L' => 8,   // बजेट
            'M' => 8,   // परिमाण
            'N' => 6,   // भार
            'O' => 8,   // प्रतिशत
            'P' => 8,   // खर्च (new)
            'Q' => 6,   // खर्च प्रतिशत (new)
            'R' => 8,   // परिमाण
            'S' => 6,   // भार
            'T' => 8,   // प्रतिशत
            'U' => 8,   // रकम रु.
            'V' => 6,   // प्रतिशत
            'W' => 15,  // कैफियत
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Hide columns D, E, F
                $sheet->getColumnDimension('D')->setVisible(false);
                $sheet->getColumnDimension('E')->setVisible(false);
                $sheet->getColumnDimension('F')->setVisible(false);

                // Set page orientation to Landscape
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);

                // Set paper size to A4
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);

                // Fit to one page wide
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0); // 0 = automatic height

                // Set margins (in inches) - narrow margins for more content
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setRight(0.5);
                $sheet->getPageMargins()->setLeft(0.5);
                $sheet->getPageMargins()->setBottom(0.5);
                $sheet->getPageMargins()->setHeader(0.3);
                $sheet->getPageMargins()->setFooter(0.3);

                // Center on page horizontally
                $sheet->getPageSetup()->setHorizontalCentered(true);

                // Set row/column headings to print on every page
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(16, 19);

                // Merge and style section headers
                $lastRow = $sheet->getHighestRow();
                for ($row = 20; $row <= $lastRow; $row++) {
                    $headerCellValue = $sheet->getCell('A' . $row)->getValue();
                    if (is_string($headerCellValue) && (str_contains($headerCellValue, 'पुँजीगत वर्षान्तक कार्यक्रम') || str_contains($headerCellValue, 'वाह्य सहायता कार्यक्रम'))) {
                        $sheet->mergeCells('A' . $row . ':W' . $row);
                        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
                        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                        $sheet->getRowDimension($row)->setRowHeight(25);
                    }

                    // Highlight total rows (yellow background, bold)
                    $cellAValue = $sheet->getCell('A' . $row)->getValue();
                    $cellBValue = $sheet->getCell('B' . $row)->getValue();
                    if ((is_string($cellBValue) && str_contains($cellBValue, 'Total of ')) || (is_string($cellAValue) && str_contains($cellAValue, 'जम्मा')) || (is_string($cellBValue) && str_contains($cellBValue, 'जम्मा'))) {
                        $range = 'A' . $row . ':W' . $row;
                        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
                        $sheet->getStyle($range)->getFont()->setBold(true);
                    }
                }
            },
        ];
    }
}
