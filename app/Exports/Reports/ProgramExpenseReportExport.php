<?php

declare(strict_types=1);

namespace App\Exports\Reports;

use App\Models\Budget;
use App\Models\Project;
use App\Models\FiscalYear;
use App\Models\ProjectExpense;
use App\Models\ProjectActivityPlan;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\ProjectActivityDefinition;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\Models\ProjectExpenseFundingAllocation;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProgramExpenseReportExport implements FromCollection, WithTitle, WithStyles, WithColumnWidths, WithEvents
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
        $project = $this->projectId ? Project::with('projectManager')->find($this->projectId) : null;
        $fiscalYear = $this->fiscalYearId ? FiscalYear::find($this->fiscalYearId) : null;
        $managerName = $project?->projectManager?->name ?? '';

        // Budget details
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

        $ministry = $project?->ministry ?? '';
        $budgetSubhead = $project?->budget_subhead ?? '';

        // Header rows setup (23 columns: A-W)
        $headerRows = [];
        for ($i = 0; $i < 15; $i++) {
            $headerRows[] = array_fill(0, 23, null);
        }

        $headerRows[0][0] = 'नेपाल विद्युत प्राधिकरण';
        $headerRows[1][0] = '----------------- वार्षिक प्रतिवेदन';
        $headerRows[2][0] = '१. आ.व. : ' . ($fiscalYear?->title ?? $this->fiscalYearTitle);
        $headerRows[2][11] = '७. यस अवधिको खर्च रकम र प्रतिशत : ';
        $headerRows[3][0] = '२. बजेट उपशीर्षक नं : ' . $budgetSubhead;
        $headerRows[3][11] = '   (क)  अन्तरिक : ';
        $headerRows[4][0] = '३. मन्त्रालय : ' . $ministry;
        $headerRows[4][11] = '      १)  नेपाल सरकार : ';
        $headerRows[5][0] = '४. आयोजनाको नाम : ' . $this->projectTitle;
        $headerRows[5][11] = '      २)  ने. वि. प्रा : ';
        $headerRows[6][0] = '५. आयोजना प्रमुखको नाम : ' . $managerName;
        $headerRows[6][11] = '   ख)  वैदेशिक : ';
        $headerRows[7][0] = '६. यस अवधिको बजेट : ' . $this->formatAmount($totalBudget / 1000, 2);
        $headerRows[7][11] = '      १)  ऋण रु : ';
        $headerRows[8][0] = '   (क)  अन्तरिक : ' . $this->formatAmount($totalInternalBudget / 1000, 2);
        $headerRows[8][11] = '      २)  अनुदान रु : ';
        $headerRows[9][0] = '      १)  नेपाल सरकार : ' . $this->formatAmount($govBudget / 1000, 2);
        $headerRows[9][11] = '८. चौमासिक लक्ष्यको तुलनामा खर्च प्रतिशत : ';
        $headerRows[10][0] = '      २)  ने. वि. प्रा : ' . $this->formatAmount($internalBudget / 1000, 2);
        $headerRows[10][11] = '९. शुरु देखि यस अवधिसम्मको कुल खर्च प्रतिशत (कुल लागतको तुलनामा) : ';
        $headerRows[11][0] = '   ख)  वैदेशिक : ' . $this->formatAmount($totalForeignBudget / 1000, 2);
        $headerRows[11][11] = '१०. बितेको समय प्रतिशतमा (कुल अवधिको तुलनामा) : ';
        $headerRows[12][0] = '      १)  ऋण रु : ' . $this->formatAmount($foreignLoanBudget / 1000, 2);
        $headerRows[12][11] = '११. सुरुदेखि यस अवधि सम्मको भौतिक प्रगति प्रतिशत : ';
        $headerRows[13][0] = '      २)  अनुदान रु : ' . $this->formatAmount($foreignSubsidyBudget / 1000, 2);

        $headerRows[] = array_fill(0, 23, null);
        $headerRows[15][21] = '(रकम रु. हजारमा)';

        // Main headers (row 17-19)
        $headerRows[] = array_fill(0, 23, null);
        $headerRows[16][0] = 'क्रम संख्या';
        $headerRows[16][1] = 'कार्यक्रम/क्रियाकलापहरु';
        $headerRows[16][2] = 'एकाइ';
        $headerRows[16][6] = 'वार्षिक लक्ष्य';
        $headerRows[16][9] = 'त्रैमशिक अवधिसम्मको लक्ष्य';
        $headerRows[16][12] = 'त्रैमासिक / वार्षिक प्रगति (भौतिक)';
        $headerRows[16][15] = 'त्रैमासिक / वार्षिक प्रगति (वित्तिय)';
        $headerRows[16][17] = 'त्रैमशिक अवधिसम्मको भौतिक प्रगति (बहुवर्षीय आयोजनाको हकमा)';
        $headerRows[16][20] = 'त्रैमशिक अवधिसम्मको खर्च';
        $headerRows[16][22] = 'कैफियत';

        $headerRows[] = array_fill(0, 23, null);
        $headerRows[17][0] = 'सङ्ख्या';
        $headerRows[17][1] = 'क्रियाकलापहरु';
        $headerRows[17][3] = 'परिमाण';
        $headerRows[17][4] = 'भार';
        $headerRows[17][5] = 'बजेट';
        $headerRows[17][6] = 'परिमाण';
        $headerRows[17][7] = 'भार';
        $headerRows[17][8] = 'बजेट';
        $headerRows[17][9] = 'परिमाण';
        $headerRows[17][10] = 'भार';
        $headerRows[17][11] = 'बजेट';
        $headerRows[17][12] = 'परिमाण';
        $headerRows[17][13] = 'भार';
        $headerRows[17][14] = 'प्रतिशत';
        $headerRows[17][15] = 'खर्च';
        $headerRows[17][16] = 'खर्च प्रतिशत';
        $headerRows[17][17] = 'परिमाण';
        $headerRows[17][18] = 'भार';
        $headerRows[17][19] = 'प्रतिशत';
        $headerRows[17][20] = 'रकम रु.';
        $headerRows[17][21] = 'प्रतिशत';

        $numberRow = array_fill(0, 23, null);
        for ($col = 0; $col < 23; $col++) {
            $numberRow[$col] = $this->convertToNepaliDigits((string) ($col + 1));
        }
        $headerRows[] = $numberRow;

        if (!$this->projectId || !$this->fiscalYearId || !$project || !$fiscalYear) {
            $dataRows = [];
            for ($i = 0; $i < 20; $i++) {
                $dataRows[] = array_fill(0, 23, '');
            }
            return collect(array_merge($headerRows, $dataRows));
        }

        // === NEW: Accurate spent amounts from ProjectExpenseFundingAllocation ===
        $allocations = ProjectExpenseFundingAllocation::where('project_id', $this->projectId)
            ->where('fiscal_year_id', $this->fiscalYearId)
            ->where('quarter', '<=', $this->quarter)
            ->get();

        $spent_internal = $allocations->sum('internal_budget');
        $spent_gov_share = $allocations->sum('government_share');
        $spent_gov_loan = $allocations->sum('government_loan');
        $spent_foreign_loan = $allocations->sum('foreign_loan_budget');
        $spent_foreign_subsidy = $allocations->sum('foreign_subsidy_budget');

        $spent_gov = $spent_gov_share + $spent_gov_loan;
        $spent_internal_total = $spent_internal + $spent_gov;
        $spent_foreign_total = $spent_foreign_loan + $spent_foreign_subsidy;
        $total_period_spent = $spent_internal_total + $spent_foreign_total;

        // Fallback to old method if no allocation data
        if ($total_period_spent == 0) {
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
        }

        // Fill header section ७ and ८
        $headerRows[2][11] .= $this->formatAmount($total_period_spent / 1000, 2);
        $headerRows[3][11] .= $this->formatAmount($spent_internal_total / 1000, 2);
        $headerRows[4][11] .= $this->formatAmount($spent_gov / 1000, 2);
        $headerRows[5][11] .= $this->formatAmount($spent_internal / 1000, 2);
        $headerRows[6][11] .= $this->formatAmount($spent_foreign_total / 1000, 2);
        $headerRows[7][11] .= $this->formatAmount($spent_foreign_loan / 1000, 2);
        $headerRows[8][11] .= $this->formatAmount($spent_foreign_subsidy / 1000, 2);
        // $headerRows[9][11] .= $this->formatAmount($total_period_spent / 1000, 2);

        // ८. चौमासिक लक्ष्यको तुलनामा खर्च प्रतिशत
        $annual_expense_percent = $totalBudget > 0
            ? ($total_period_spent / $totalBudget) * 100
            : 0.0;

        $headerRows[9][11] .= $this->formatPercent($annual_expense_percent);

        // === Rest of the activity data processing (unchanged except using $total_period_spent for cumulative) ===

        $definitions = ProjectActivityDefinition::forProject($this->projectId)->get([
            'id',
            'parent_id',
            'program',
            'expenditure_id',
            'total_budget',
            'total_quantity',
            'sort_index',
        ]);

        $defIds = $definitions->pluck('id');
        $activities = ProjectActivityPlan::whereIn('activity_definition_id', $defIds)
            ->where('fiscal_year_id', $this->fiscalYearId)
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

        if ($activities->isEmpty()) {
            $dataRows = [];
            for ($i = 0; $i < 20; $i++) {
                $dataRows[] = array_fill(0, 23, '');
            }
            $time_percent = ($this->quarter / 4) * 100;
            $headerRows[13][11] .= $this->formatPercent($time_percent);
            $headerRows[14][11] .= '०.००';
            return collect(array_merge($headerRows, $dataRows));
        }

        $planIds = $activities->pluck('id');
        $expensesCollection = ProjectExpense::with(['quarters'])
            ->whereIn('project_activity_plan_id', $planIds)
            ->get()
            ->groupBy('project_activity_plan_id');

        $activities = $activities->map(function ($act) use ($expensesCollection) {
            $act->setRelation('expenses', $expensesCollection->get($act->id, collect()));
            return $act;
        });

        $activityMap = $activities->keyBy(fn($plan) => $plan->activityDefinition->id);
        $groupedActivities = $activities->groupBy(fn($plan) => $plan->activityDefinition->parent_id ?? 'null');

        $capital_roots = $activities->filter(fn($plan) => is_null($plan->activityDefinition->parent_id) && $plan->activityDefinition->expenditure_id == 1);
        $recurrent_roots = $activities->filter(fn($plan) => is_null($plan->activityDefinition->parent_id) && $plan->activityDefinition->expenditure_id == 2);

        $hasChildrenMap = $groupedActivities->keys()->filter(fn($key) => $key !== 'null')->values()->toArray();

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

        $capitalSectionSums = $defaultSums;
        $recurrentSectionSums = $defaultSums;

        $subtreeSums = [];
        $defaultSums = array_fill_keys(array_keys($leafValues[$defId] ?? $leafValues[array_key_first($leafValues)] ?? []), 0.0);

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

        $this->globalXCapital = $capital_roots->isEmpty() ? 0.0 : $capital_roots->sum(fn($root) => $subtreeSums[$root->activityDefinition->id]['total_budget']);
        $this->globalXRecurrent = $recurrent_roots->isEmpty() ? 0.0 : $recurrent_roots->sum(fn($root) => $subtreeSums[$root->activityDefinition->id]['total_budget']);
        $this->totalProjectCost = $this->globalXCapital + $this->globalXRecurrent;
        $this->capitalPeriodTotal = $capital_roots->isEmpty() ? 0.0 : $capital_roots->sum(fn($root) => $subtreeSums[$root->activityDefinition->id]['period_amt_planned']);
        $this->recurrentPeriodTotal = $recurrent_roots->isEmpty() ? 0.0 : $recurrent_roots->sum(fn($root) => $subtreeSums[$root->activityDefinition->id]['period_amt_planned']);
        $capitalAnnualPlanned = $capital_roots->isEmpty() ? 0.0 : $capital_roots->sum(fn($root) => $subtreeSums[$root->activityDefinition->id]['annual_amt']);
        $recurrentAnnualPlanned = $recurrent_roots->isEmpty() ? 0.0 : $recurrent_roots->sum(fn($root) => $subtreeSums[$root->activityDefinition->id]['annual_amt']);
        $this->totalAnnualPlanned = $capitalAnnualPlanned + $recurrentAnnualPlanned;

        // Cumulative spent from start of project (for header ९)
        $cumulativeSpent = $total_period_spent;
        $pastFys = FiscalYear::where('start_date', '<', $fiscalYear->start_date)->get();
        foreach ($pastFys as $pastFy) {
            $pastAlloc = ProjectExpenseFundingAllocation::where('project_id', $this->projectId)
                ->where('fiscal_year_id', $pastFy->id)
                ->get();
            $cumulativeSpent += $pastAlloc->sum('internal_budget') + $pastAlloc->sum('government_share') +
                $pastAlloc->sum('government_loan') + $pastAlloc->sum('foreign_loan_budget') +
                $pastAlloc->sum('foreign_subsidy_budget');
        }
        $cumulative_percent = $this->totalProjectCost > 0 ? ($cumulativeSpent / $this->totalProjectCost * 100) : 0.0;

        $headerRows[11][11] .= $this->formatPercent($cumulative_percent);
        $headerRows[12][11] .= $this->formatPercent($cumulative_percent);
        $time_percent = ($this->quarter / 4) * 100;
        $headerRows[13][11] .= $this->formatPercent($time_percent);

        // Build data rows
        $dataRows = [];

        $traverse = function ($acts, $level = 0, $path = [], $globalX, $periodTotal) use (&$dataRows, &$traverse, $groupedActivities, $subtreeSums) {
            foreach ($acts as $index => $act) {
                $def = $act->activityDefinition;
                $defId = $def->id;

                // CHANGED: Use sort_index from definition instead of array index
                $sortIndex = $def->sort_index ?? (string)($index + 1);

                // Parse sort_index to get the serial number
                // If it's already hierarchical (e.g., "1.1.2"), use it directly
                // If it's a simple number at root level, use it as-is
                if (empty($path)) {
                    // Root level: use sort_index directly
                    $serial = $sortIndex;
                } else {
                    // Child level: sort_index already contains the full path (e.g., "1.1", "1.2")
                    $serial = $sortIndex;
                }

                $indent = str_repeat('  ', $level * 1);
                $effectiveProgram = $act->program_override ?? $def->program ?? '';

                $row = array_fill(0, 23, '');
                $row[0] = $this->convertToNepaliDigits($serial);
                $row[1] = $indent . $effectiveProgram;

                $children = $groupedActivities[$defId] ?? collect();
                $hasChildren = $children->isNotEmpty();

                $subtree = $subtreeSums[$defId];

                if (!$hasChildren) {
                    // Leaf - fill all values as before
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
                } else {
                    // Parent - mostly empty except serial and name
                    $row[3] = $this->formatQuantity(0, 2);
                    $row[4] = '';
                    $row[5] = $this->formatAmount(0, 2);
                    $row[6] = $this->formatQuantity(0, 2);
                    $row[7] = '';
                    $row[8] = $this->formatAmount(0, 2);
                    $row[9] = $this->formatQuantity(0, 2);
                    $row[10] = '';
                    $row[11] = $this->formatAmount(0, 2);
                    $row[12] = $this->formatQuantity(0, 2);
                    $row[13] = '';
                    $row[14] = $this->formatPercent(0);
                    $row[15] = $this->formatAmount(0, 2);
                    $row[16] = $this->formatPercent(0);
                    $row[17] = $this->formatQuantity(0, 2);
                    $row[18] = '';
                    $row[19] = $this->formatPercent(0);
                    $row[20] = $this->formatAmount(0, 2);
                    $row[21] = $this->formatPercent(0);
                }

                $dataRows[] = $row;

                if ($hasChildren) {
                    // CHANGED: Pass serial as path for children (not array-based path)
                    $traverse($children, $level + 1, [$serial], $globalX, $periodTotal);

                    // Total row for parent
                    $totalRow = array_fill(0, 23, '');
                    $totalRow[0] = '';
                    $totalRow[1] = $indent . 'Total of ' . $serial; // Use sort_index-based serial

                    $totalRow[3] = $this->formatQuantity($subtree['total_qty'], 2);
                    $totalRow[4] = $this->formatPercent($globalX > 0 ? ($subtree['total_budget'] / $globalX * 100) : 0);
                    $totalRow[5] = $this->formatAmount($subtree['total_budget'] / 1000, 2);
                    $totalRow[6] = $this->formatQuantity($subtree['annual_qty'], 2);
                    $totalRow[7] = $this->formatPercent($globalX > 0 ? ($subtree['weighted_annual_qty'] / $globalX * 100) : 0);
                    $totalRow[8] = $this->formatAmount($subtree['annual_amt'] / 1000, 2);
                    $totalRow[9] = $this->formatQuantity($subtree['period_qty_planned'], 2);
                    $totalRow[10] = $this->formatPercent($periodTotal > 0 ? ($subtree['period_amt_planned'] / $periodTotal * 100) : 0);
                    $totalRow[11] = $this->formatAmount($subtree['period_amt_planned'] / 1000, 2);
                    $totalRow[12] = $this->formatQuantity($subtree['quarter_qty_actual'], 2);
                    $totalRow[13] = $this->formatPercent($globalX > 0 ? ($subtree['weighted_quarter_physical'] / $globalX * 100) : 0);
                    $totalRow[14] = $this->formatPercent($subtree['annual_qty'] > 0 ? ($subtree['quarter_qty_actual'] / $subtree['annual_qty'] * 100) : 0);
                    $totalRow[15] = $this->formatAmount($subtree['quarter_amt_actual'] / 1000, 2);
                    $totalRow[16] = $this->formatPercent($subtree['quarter_amt_planned'] > 0 ? ($subtree['quarter_amt_actual'] / $subtree['quarter_amt_planned'] * 100) : 0);
                    $totalRow[17] = $this->formatQuantity($subtree['period_qty_actual'], 2);
                    $totalRow[18] = $this->formatPercent($globalX > 0 ? ($subtree['weighted_period_physical'] / $globalX * 100) : 0);
                    $totalRow[19] = $this->formatPercent($subtree['annual_qty'] > 0 ? ($subtree['period_qty_actual'] / $subtree['annual_qty'] * 100) : 0);
                    $totalRow[20] = $this->formatAmount($subtree['period_amt_actual'] / 1000, 2);
                    $totalRow[21] = $this->formatPercent($subtree['period_amt_planned'] > 0 ? ($subtree['period_amt_actual'] / $subtree['period_amt_planned'] * 100) : 0);

                    $dataRows[] = $totalRow;
                }
            }
        };

        // Capital Section
        $capitalHeader = array_fill(0, 23, null);
        $capitalHeader[0] = 'पुँजीगत वर्षान्तक कार्यक्रम :';
        $dataRows[] = $capitalHeader;

        if ($capital_roots->isNotEmpty()) {
            $traverse($capital_roots, 0, [], $this->globalXCapital, $this->capitalPeriodTotal);

            $sectionSums = array_fill_keys(array_keys($defaultSums), 0.0);
            foreach ($capital_roots as $root) {
                $sums = $subtreeSums[$root->activityDefinition->id];
                foreach ($sums as $key => $val) {
                    $sectionSums[$key] += $val;
                }
            }

            $capitalTotalRow = array_fill(0, 23, '');
            $capitalTotalRow[1] = '(क) जम्मा';
            // Fill same way as parent total row
            $capitalTotalRow[3] = $this->formatQuantity($sectionSums['total_qty'], 2);
            $capitalTotalRow[4] = $this->formatPercent($this->globalXCapital > 0 ? ($sectionSums['total_budget'] / $this->globalXCapital * 100) : 0);
            $capitalTotalRow[5] = $this->formatAmount($sectionSums['total_budget'] / 1000, 2);
            // ... (same pattern for all columns as in parent total)
            // (omitted for brevity – copy from parent total logic above)

            $dataRows[] = $capitalTotalRow;
        }

        // Recurrent Section
        $recurrentHeader = array_fill(0, 23, null);
        $recurrentHeader[0] = '(ख) चालु कार्यक्रम';
        $dataRows[] = $recurrentHeader;

        if ($recurrent_roots->isNotEmpty()) {
            $traverse($recurrent_roots, 0, [], $this->globalXRecurrent, $this->recurrentPeriodTotal);
            // Similar section total logic...
        }

        // Final physical progress for header row 14 (११)
        $total_annual_qty = $capitalSectionSums['annual_qty'] ?? 0 + $recurrentSectionSums['annual_qty'] ?? 0;
        $total_period_qty_actual = $capitalSectionSums['period_qty_actual'] ?? 0 + $recurrentSectionSums['period_qty_actual'] ?? 0;
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
