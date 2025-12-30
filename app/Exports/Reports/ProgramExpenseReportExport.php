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
        if ($input === '' || $input === null) {
            return '';
        }
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
        $formatted = number_format($number, $decimals);
        return $this->convertToNepaliDigits($formatted);
    }

    private function formatAmount(float $number, int $decimals = 2): string
    {
        $formatted = number_format($number, $decimals);
        $clean = rtrim(rtrim($formatted, '0'), '.'); // removes trailing .00
        return $this->convertToNepaliDigits($clean);
    }

    private function formatPercent(float $number): string
    {
        $formatted = number_format($number, 2);
        return $this->convertToNepaliDigits($formatted);
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

        // Main headers
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

        $dataRows = [];

        if (!$this->projectId || !$this->fiscalYearId || !$project || !$fiscalYear) {
            // Empty report
            for ($i = 0; $i < 40; $i++) {
                $dataRows[] = array_fill(0, 23, '');
            }

            $time_percent = ($this->quarter / 4) * 100;
            $headerRows[13][11] .= $this->formatPercent($time_percent);
            $headerRows[14][11] .= '०.००';
        } else {
            // === Accurate spent amounts ===
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

            // Fill header spent amounts
            $headerRows[2][11] .= $this->formatAmount($total_period_spent / 1000, 2);
            $headerRows[3][11] .= $this->formatAmount($spent_internal_total / 1000, 2);
            $headerRows[4][11] .= $this->formatAmount($spent_gov / 1000, 2);
            $headerRows[5][11] .= $this->formatAmount($spent_internal / 1000, 2);
            $headerRows[6][11] .= $this->formatAmount($spent_foreign_total / 1000, 2);
            $headerRows[7][11] .= $this->formatAmount($spent_foreign_loan / 1000, 2);
            $headerRows[8][11] .= $this->formatAmount($spent_foreign_subsidy / 1000, 2);

            $annual_expense_percent = $totalBudget > 0 ? ($total_period_spent / $totalBudget) * 100 : 0.0;
            $headerRows[9][11] .= $this->formatPercent($annual_expense_percent);

            // === Activity processing ===
            $definitions = ProjectActivityDefinition::forProject($this->projectId)
                ->current()
                ->get([
                    'id',
                    'parent_id',
                    'program',
                    'expenditure_id',
                    'total_budget',
                    'total_quantity',
                    'sort_index',
                ]);

            $defIds = $definitions->pluck('id');

            $activities = ProjectActivityPlan::whereIn('activity_definition_version_id', $defIds)
                ->where('fiscal_year_id', $this->fiscalYearId)
                ->with('definitionVersion')
                ->get([
                    'id',
                    'activity_definition_version_id',
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
                for ($i = 0; $i < 20; $i++) {
                    $dataRows[] = array_fill(0, 23, '');
                }
                $time_percent = ($this->quarter / 4) * 100;
                $headerRows[13][11] .= $this->formatPercent($time_percent);
                $headerRows[14][11] .= '०.००';
            } else {
                $planIds = $activities->pluck('id');
                $expensesCollection = ProjectExpense::with(['quarters'])
                    ->whereIn('project_activity_plan_id', $planIds)
                    ->get()
                    ->groupBy('project_activity_plan_id');

                $activities = $activities->map(function ($act) use ($expensesCollection) {
                    $act->setRelation('expenses', $expensesCollection->get($act->id, collect()));
                    return $act;
                });

                $activityMap = $activities->keyBy(fn($plan) => $plan->definitionVersion->id);
                $groupedActivities = $activities->groupBy(fn($plan) => $plan->definitionVersion->parent_id ?? 'null');

                $capital_roots = $activities->filter(fn($plan) => is_null($plan->definitionVersion->parent_id) && $plan->definitionVersion->expenditure_id == 1);
                $recurrent_roots = $activities->filter(fn($plan) => is_null($plan->definitionVersion->parent_id) && $plan->definitionVersion->expenditure_id == 2);

                $hasChildrenMap = $groupedActivities->keys()->filter(fn($key) => $key !== 'null')->values()->toArray();

                $leafValues = [];
                foreach ($activities as $act) {
                    $defId = $act->definitionVersion->id;
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

                    $total_qty = (float) ($act->definitionVersion->total_quantity ?? 0);
                    $total_budget = (float) ($act->definitionVersion->total_budget ?? 0);
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

                $defaultSums = array_fill_keys(array_keys($leafValues[array_key_first($leafValues)] ?? []), 0.0);

                $subtreeSums = [];
                $computeSubtreeSums = function ($defId) use ($leafValues, $groupedActivities, $defaultSums, &$subtreeSums, &$computeSubtreeSums) {
                    if (isset($subtreeSums[$defId])) {
                        return $subtreeSums[$defId];
                    }

                    $sums = $leafValues[$defId] ?? $defaultSums;

                    $children = $groupedActivities[$defId] ?? collect();
                    foreach ($children as $child) {
                        $childDefId = $child->definitionVersion->id;
                        $childSums = $computeSubtreeSums($childDefId);
                        foreach ($childSums as $key => $val) {
                            $sums[$key] += $val;
                        }
                    }

                    $subtreeSums[$defId] = $sums;
                    return $sums;
                };

                $allRoots = $capital_roots->concat($recurrent_roots);
                foreach ($allRoots as $root) {
                    $computeSubtreeSums($root->definitionVersion->id);
                }

                $this->globalXCapital = $capital_roots->isEmpty() ? 0.0 : $capital_roots->sum(fn($root) => $subtreeSums[$root->definitionVersion->id]['total_budget']);
                $this->globalXRecurrent = $recurrent_roots->isEmpty() ? 0.0 : $recurrent_roots->sum(fn($root) => $subtreeSums[$root->definitionVersion->id]['total_budget']);
                $this->totalProjectCost = $this->globalXCapital + $this->globalXRecurrent;
                $this->capitalPeriodTotal = $capital_roots->isEmpty() ? 0.0 : $capital_roots->sum(fn($root) => $subtreeSums[$root->definitionVersion->id]['period_amt_planned']);
                $this->recurrentPeriodTotal = $recurrent_roots->isEmpty() ? 0.0 : $recurrent_roots->sum(fn($root) => $subtreeSums[$root->definitionVersion->id]['period_amt_planned']);

                // Cumulative spent for header ९
                $cumulativeSpent = $total_period_spent;
                $pastFys = FiscalYear::where('start_date', '<', $fiscalYear->start_date)->get();
                foreach ($pastFys as $pastFy) {
                    $pastAlloc = ProjectExpenseFundingAllocation::where('project_id', $this->projectId)
                        ->where('fiscal_year_id', $pastFy->id)
                        ->get();
                    $cumulativeSpent += $pastAlloc->sum(fn($a) => $a->internal_budget + $a->government_share + $a->government_loan + $a->foreign_loan_budget + $a->foreign_subsidy_budget);
                }
                $cumulative_percent = $this->totalProjectCost > 0 ? ($cumulativeSpent / $this->totalProjectCost * 100) : 0.0;
                $headerRows[11][11] .= $this->formatPercent($cumulative_percent);

                $time_percent = ($this->quarter / 4) * 100;
                $headerRows[13][11] .= $this->formatPercent($time_percent);

                // === Build data rows ===
                $traverse = function ($acts, $level = 0) use (&$dataRows, &$traverse, $groupedActivities, $subtreeSums) {
                    foreach ($acts as $act) {
                        $def = $act->definitionVersion;
                        $defId = $def->id;
                        $sortIndex = $def->sort_index ?? '';
                        $indent = str_repeat('    ', $level);
                        $effectiveProgram = $act->program_override ?? $def->program ?? '';

                        $children = $groupedActivities[$defId] ?? collect();
                        $hasChildren = $children->isNotEmpty();

                        $subtree = $subtreeSums[$defId];

                        $globalX = ($def->expenditure_id == 1) ? $this->globalXCapital : $this->globalXRecurrent;
                        $periodTotal = ($def->expenditure_id == 1) ? $this->capitalPeriodTotal : $this->recurrentPeriodTotal;

                        if ($hasChildren) {
                            // === ONLY ONE PARENT ROW ===
                            $parentRow = array_fill(0, 23, '');
                            $parentRow[0] = $this->convertToNepaliDigits($sortIndex);
                            $parentRow[1] = $indent . $effectiveProgram;
                            $dataRows[] = $parentRow;

                            // Recurse into children
                            $traverse($children, $level + 1);

                            // === SUBTOTAL ROW ===
                            $totalRow = array_fill(0, 23, '');
                            $totalRow[1] = $indent . 'जम्मा ' . $sortIndex;

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
                        } else {
                            // === LEAF ROW ===
                            $row = array_fill(0, 23, '');
                            $row[0] = $this->convertToNepaliDigits($sortIndex);
                            $row[1] = $indent . $effectiveProgram;

                            $row[3] = $this->formatQuantity($subtree['total_qty'], 2);
                            $row[4] = $this->formatPercent($globalX > 0 ? ($subtree['total_budget'] / $globalX * 100) : 0);
                            $row[5] = $this->formatAmount($subtree['total_budget'] / 1000, 2);
                            $row[6] = $this->formatQuantity($subtree['annual_qty'], 2);
                            $row[7] = $this->formatPercent($globalX > 0 ? ($subtree['weighted_annual_qty'] / $globalX * 100) : 0);
                            $row[8] = $this->formatAmount($subtree['annual_amt'] / 1000, 2);
                            $row[9] = $this->formatQuantity($subtree['period_qty_planned'], 2);
                            $row[10] = $this->formatPercent($periodTotal > 0 ? ($subtree['period_amt_planned'] / $periodTotal * 100) : 0);
                            $row[11] = $this->formatAmount($subtree['period_amt_planned'] / 1000, 2);
                            $row[12] = $this->formatQuantity($subtree['quarter_qty_actual'], 2);
                            $row[13] = $this->formatPercent($globalX > 0 ? ($subtree['weighted_quarter_physical'] / $globalX * 100) : 0);
                            $row[14] = $this->formatPercent($subtree['annual_qty'] > 0 ? ($subtree['quarter_qty_actual'] / $subtree['annual_qty'] * 100) : 0);
                            $row[15] = $this->formatAmount($subtree['quarter_amt_actual'] / 1000, 2);
                            $row[16] = $this->formatPercent($subtree['quarter_amt_planned'] > 0 ? ($subtree['quarter_amt_actual'] / $subtree['quarter_amt_planned'] * 100) : 0);
                            $row[17] = $this->formatQuantity($subtree['period_qty_actual'], 2);
                            $row[18] = $this->formatPercent($globalX > 0 ? ($subtree['weighted_period_physical'] / $globalX * 100) : 0);
                            $row[19] = $this->formatPercent($subtree['annual_qty'] > 0 ? ($subtree['period_qty_actual'] / $subtree['annual_qty'] * 100) : 0);
                            $row[20] = $this->formatAmount($subtree['period_amt_actual'] / 1000, 2);
                            $row[21] = $this->formatPercent($subtree['period_amt_planned'] > 0 ? ($subtree['period_amt_actual'] / $subtree['period_amt_planned'] * 100) : 0);

                            $dataRows[] = $row;
                        }
                    }
                };

                // Capital Section
                $capitalHeader = array_fill(0, 23, null);
                $capitalHeader[0] = 'पुँजीगत कार्यक्रमहरु :';
                $dataRows[] = $capitalHeader;

                $capitalSectionSums = $defaultSums;
                if ($capital_roots->isNotEmpty()) {
                    $traverse($capital_roots);
                    foreach ($capital_roots as $root) {
                        $sums = $subtreeSums[$root->definitionVersion->id];
                        foreach ($sums as $key => $val) {
                            $capitalSectionSums[$key] += $val;
                        }
                    }
                    $dataRows[] = $this->makeTotalRow('(क) जम्मा', $capitalSectionSums, $this->globalXCapital, $this->capitalPeriodTotal);
                }

                // Recurrent Section
                $recurrentHeader = array_fill(0, 23, null);
                $recurrentHeader[0] = '(ख) चालु कार्यक्रमहरु';
                $dataRows[] = $recurrentHeader;

                $recurrentSectionSums = $defaultSums;
                if ($recurrent_roots->isNotEmpty()) {
                    $traverse($recurrent_roots);
                    foreach ($recurrent_roots as $root) {
                        $sums = $subtreeSums[$root->definitionVersion->id];
                        foreach ($sums as $key => $val) {
                            $recurrentSectionSums[$key] += $val;
                        }
                    }
                    $dataRows[] = $this->makeTotalRow('(ख) जम्मा', $recurrentSectionSums, $this->globalXRecurrent, $this->recurrentPeriodTotal);
                }

                // Grand Total
                $grandSums = $defaultSums;
                foreach ($capitalSectionSums as $key => $val) $grandSums[$key] += $val;
                foreach ($recurrentSectionSums as $key => $val) $grandSums[$key] += $val;

                $dataRows[] = $this->makeTotalRow('जम्मा', $grandSums, $this->totalProjectCost, ($this->capitalPeriodTotal + $this->recurrentPeriodTotal));

                // Physical progress percentage
                $total_annual_qty = $capitalSectionSums['annual_qty'] + $recurrentSectionSums['annual_qty'];
                $total_period_qty_actual = $capitalSectionSums['period_qty_actual'] + $recurrentSectionSums['period_qty_actual'];
                $physical_percent = $total_annual_qty > 0 ? ($total_period_qty_actual / $total_annual_qty * 100) : 0.0;
                $headerRows[14][11] .= $this->formatPercent($physical_percent);
            }
        }

        // === Calculate Quarterly Financial Progress Percentage ===
        $period_planned_total = $grandSums['period_amt_planned'];
        $period_actual_total = $grandSums['period_amt_actual'];

        $quarterly_financial_progress = $period_planned_total > 0
            ? ($period_actual_total / $period_planned_total) * 100
            : 0.0;

        // === Signature block with calculated progress percentage ===
        $dataRows[] = array_fill(0, 23, ''); // blank row for spacing

        $progressRow = array_fill(0, 23, '');
        $progressRow[0] = $this->reportingPeriod
            . ' प्रगति प्रतिशत : '
            . $this->formatPercent($quarterly_financial_progress);
        $dataRows[] = $progressRow;

        $signatureHeader = array_fill(0, 23, '');
        $signatureHeader[2] = 'आयोजना/कार्यालय प्रमुख :-';
        $signatureHeader[10] = 'विभागीय/संस्था प्रमुख :-';
        $signatureHeader[18] = 'प्रमाणित गर्ने :-';
        $dataRows[] = $signatureHeader;

        $dateRow = array_fill(0, 23, '');
        $dateRow[2] = 'मिति :-';
        $dateRow[10] = 'मिति :-';
        $dateRow[18] = 'मिति :-';
        $dataRows[] = $dateRow;

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

                // Page setup
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setRight(0.5);
                $sheet->getPageMargins()->setLeft(0.5);
                $sheet->getPageMargins()->setBottom(0.5);
                $sheet->getPageMargins()->setHeader(0.3);
                $sheet->getPageMargins()->setFooter(0.3);
                $sheet->getPageSetup()->setHorizontalCentered(true);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(16, 19);

                // Style section headers and total rows (yellow highlight)
                $lastRowBeforeSignatures = $sheet->getHighestRow(); // Temporary, only for this loop

                for ($row = 20; $row <= $lastRowBeforeSignatures; $row++) {
                    $headerCellValue = $sheet->getCell('A' . $row)->getValue();
                    if (is_string($headerCellValue) && preg_match('/(पुँजीगत|चालु)\s*कार्यक्रमहरु/', $headerCellValue)) {
                        $sheet->mergeCells('A' . $row . ':W' . $row);
                        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
                        $sheet->getStyle('A' . $row)->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                            ->setVertical(Alignment::VERTICAL_CENTER);
                        $sheet->getRowDimension($row)->setRowHeight(25);
                    }

                    // Highlight total rows (including parent totals and section/grand totals)
                    $cellAValue = $sheet->getCell('A' . $row)->getValue();
                    $cellBValue = $sheet->getCell('B' . $row)->getValue();
                    if (
                        (is_string($cellBValue) && str_contains($cellBValue, 'Total of ')) ||
                        (is_string($cellAValue) && str_contains($cellAValue, 'जम्मा')) ||
                        (is_string($cellBValue) && str_contains($cellBValue, 'जम्मा'))
                    ) {
                        $range = 'A' . $row . ':W' . $row;
                        $sheet->getStyle($range)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FFFF00');
                        $sheet->getStyle($range)->getFont()->setBold(true);
                    }
                }

                // === MERGE C TO V FOR PARENT ROWS (ONLY SERIAL + NAME) ===
                $highestRow = $sheet->getHighestRow();
                $signatureStartRow = $highestRow - 3; // Last 4 rows: blank + progress + 2 signature rows

                for ($row = 20; $row < $signatureStartRow; $row++) {
                    $valA = $sheet->getCell("A{$row}")->getValue(); // sort_index in Nepali digits
                    $valB = $sheet->getCell("B{$row}")->getValue(); // program name (indented)
                    $valD = $sheet->getCell("D{$row}")->getValue(); // quantity column - empty for parents

                    // Conditions to identify true parent rows
                    if (
                        !empty($valA) &&
                        !empty($valB) &&
                        empty($valD) &&
                        !str_contains($valB, 'जम्मा') &&      // exclude subtotal rows
                        !str_contains($valA, 'पुँजीगत') &&    // exclude section headers
                        !str_contains($valA, 'चालु')
                    ) {
                        $sheet->mergeCells("C{$row}:V{$row}");
                        // Optional: better left alignment for program name
                        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    }
                }

                // === NOW get the TRUE final row count (includes the 3 signature rows) ===
                $lastRow = $sheet->getHighestRow();

                // The 3 signature rows are the last 3 rows
                $progressRowNum = $lastRow - 2; // त्रैमासिक प्रगति प्रतिशत :
                $sigHeaderRow    = $lastRow - 1; // Signature titles
                $dateRowNum      = $lastRow;     // Dates

                // 1. Progress percentage row
                $sheet->mergeCells("A{$progressRowNum}:W{$progressRowNum}");
                $sheet->getStyle("A{$progressRowNum}:W{$progressRowNum}")->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle("A{$progressRowNum}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($progressRowNum)->setRowHeight(30);
                $sheet->getStyle("A{$progressRowNum}:W{$progressRowNum}")->getBorders()->applyFromArray([
                    'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                ]);

                // 2. Signature titles row
                $sheet->mergeCells("A{$sigHeaderRow}:H{$sigHeaderRow}");
                $sheet->mergeCells("I{$sigHeaderRow}:P{$sigHeaderRow}");
                $sheet->mergeCells("Q{$sigHeaderRow}:W{$sigHeaderRow}");

                $sheet->getStyle("A{$sigHeaderRow}:W{$sigHeaderRow}")->getFont()->setSize(10);
                $sheet->getStyle("A{$sigHeaderRow}:W{$sigHeaderRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($sigHeaderRow)->setRowHeight(40);
                $sheet->getStyle("A{$sigHeaderRow}:W{$sigHeaderRow}")->getBorders()->applyFromArray([
                    'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                ]);

                // 3. Date row
                $sheet->mergeCells("A{$dateRowNum}:H{$dateRowNum}");
                $sheet->mergeCells("I{$dateRowNum}:P{$dateRowNum}");
                $sheet->mergeCells("Q{$dateRowNum}:W{$dateRowNum}");

                $sheet->getStyle("A{$dateRowNum}:W{$dateRowNum}")->getFont()->setSize(10);
                $sheet->getStyle("A{$dateRowNum}:W{$dateRowNum}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getRowDimension($dateRowNum)->setRowHeight(50);
                $sheet->getStyle("A{$dateRowNum}:W{$dateRowNum}")->getBorders()->applyFromArray([
                    'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                ]);
            },
        ];
    }

    private function makeTotalRow(string $label, array $sums, float $globalX, float $periodTotal): array
    {
        $row = array_fill(0, 23, '');

        $row[1] = $label;

        $row[3] = $this->formatQuantity($sums['total_qty'], 2);
        $row[4] = $this->formatPercent($globalX > 0 ? ($sums['total_budget'] / $globalX * 100) : 0);
        $row[5] = $this->formatAmount($sums['total_budget'] / 1000, 2);

        $row[6] = $this->formatQuantity($sums['annual_qty'], 2);
        $row[7] = $this->formatPercent($globalX > 0 ? ($sums['weighted_annual_qty'] / $globalX * 100) : 0);
        $row[8] = $this->formatAmount($sums['annual_amt'] / 1000, 2);

        $row[9] = $this->formatQuantity($sums['period_qty_planned'], 2);
        $row[10] = $this->formatPercent($periodTotal > 0 ? ($sums['period_amt_planned'] / $periodTotal * 100) : 0);
        $row[11] = $this->formatAmount($sums['period_amt_planned'] / 1000, 2);

        $row[12] = $this->formatQuantity($sums['quarter_qty_actual'], 2);
        $row[13] = $this->formatPercent($globalX > 0 ? ($sums['weighted_quarter_physical'] / $globalX * 100) : 0);
        $row[14] = $this->formatPercent($sums['annual_qty'] > 0 ? ($sums['quarter_qty_actual'] / $sums['annual_qty'] * 100) : 0);

        $row[15] = $this->formatAmount($sums['quarter_amt_actual'] / 1000, 2);
        $row[16] = $this->formatPercent($sums['quarter_amt_planned'] > 0 ? ($sums['quarter_amt_actual'] / $sums['quarter_amt_planned'] * 100) : 0);

        $row[17] = $this->formatQuantity($sums['period_qty_actual'], 2);
        $row[18] = $this->formatPercent($globalX > 0 ? ($sums['weighted_period_physical'] / $globalX * 100) : 0);
        $row[19] = $this->formatPercent($sums['annual_qty'] > 0 ? ($sums['period_qty_actual'] / $sums['annual_qty'] * 100) : 0);

        $row[20] = $this->formatAmount($sums['period_amt_actual'] / 1000, 2);
        $row[21] = $this->formatPercent($sums['period_amt_planned'] > 0 ? ($sums['period_amt_actual'] / $sums['period_amt_planned'] * 100) : 0);

        return $row;
    }
}
