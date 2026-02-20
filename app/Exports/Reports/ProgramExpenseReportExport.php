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
    protected Project    $project;
    protected FiscalYear $fiscalYear;
    protected int        $quarter;
    protected string     $reportingPeriod;

    // Derived scalar shortcuts (set once in constructor)
    protected string $projectTitle;
    protected string $fiscalYearTitle;
    protected int    $projectId;
    protected int    $fiscalYearId;

    // Computed during collection()
    protected float $globalXCapital       = 0.0;
    protected float $globalXRecurrent     = 0.0;
    protected float $capitalPeriodTotal   = 0.0;
    protected float $recurrentPeriodTotal = 0.0;
    protected float $totalProjectCost     = 0.0;

    /**
     * Accept real Eloquent models — no IDs, no re-querying.
     * All relationships needed in collection() are loaded right here.
     */
    public function __construct(Project $project, FiscalYear $fiscalYear, int $quarter = 1)
    {
        // One load() call — no extra queries anywhere else in this class
        $project->load(['projectManager', 'budgetHeading']);

        $this->project    = $project;
        $this->fiscalYear = $fiscalYear;
        $this->quarter    = $quarter;

        // Convenience shortcuts
        $this->projectId       = $project->id;
        $this->fiscalYearId    = $fiscalYear->id;
        $this->projectTitle    = $project->title    ?? '';
        $this->fiscalYearTitle = $fiscalYear->title ?? '';
        $this->reportingPeriod = $this->getQuarterText($quarter);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function getQuarterText(int $quarter): string
    {
        return [
            1 => 'पहिलो त्रैमासिक',
            2 => 'दोस्रो त्रैमासिक',
            3 => 'तेस्रो त्रैमासिक',
            4 => 'चौथो त्रैमासिक',
        ][$quarter] ?? 'वार्षिक';
    }

    private function convertToNepaliDigits(string $input): string
    {
        if ($input === '') {
            return '';
        }

        return strtr($input, [
            '0' => '०',
            '1' => '१',
            '2' => '२',
            '3' => '३',
            '4' => '४',
            '5' => '५',
            '6' => '६',
            '7' => '७',
            '8' => '८',
            '9' => '९',
        ]);
    }

    private function formatQuantity(float $number, int $decimals = 2): string
    {
        return $this->convertToNepaliDigits(number_format($number, $decimals));
    }

    private function formatAmount(float $number, int $decimals = 2): string
    {
        $clean = rtrim(rtrim(number_format($number, $decimals), '0'), '.');
        return $this->convertToNepaliDigits($clean);
    }

    private function formatPercent(float $number): string
    {
        return $this->convertToNepaliDigits(number_format($number, 2));
    }

    // -------------------------------------------------------------------------
    // Collection
    // -------------------------------------------------------------------------

    public function collection()
    {
        // ── Relationship values — no extra queries, loaded in constructor ──────
        $managerName   = $this->project->projectManager?->name ?? '';
        $ministry      = $this->project->ministry?->title      ?? $this->project->ministry      ?? '';
        $budgetSubhead = $this->project->budgetHeading?->title   ?? $this->project->budgetHeading ?? '';

        // ── Budget for this fiscal year ───────────────────────────────────────
        $budget = Budget::where('project_id', $this->projectId)
            ->where('fiscal_year_id', $this->fiscalYearId)
            ->first();

        $internalBudget       = (float) ($budget?->internal_budget       ?? 0);
        $govShareBudget       = (float) ($budget?->government_share       ?? 0);
        $govLoanBudget        = (float) ($budget?->government_loan        ?? 0);
        $govBudget            = $govShareBudget + $govLoanBudget;
        $foreignLoanBudget    = (float) ($budget?->foreign_loan_budget    ?? 0);
        $foreignSubsidyBudget = (float) ($budget?->foreign_subsidy_budget ?? 0);
        $totalInternalBudget  = $internalBudget + $govBudget;
        $totalForeignBudget   = $foreignLoanBudget + $foreignSubsidyBudget;
        $totalBudget          = (float) ($budget?->total_budget ?? ($totalInternalBudget + $totalForeignBudget));

        // ── Build header rows (23 columns: A–W) ──────────────────────────────
        $headerRows = [];
        for ($i = 0; $i < 15; $i++) {
            $headerRows[] = array_fill(0, 23, null);
        }

        $headerRows[0][0]   = 'नेपाल विद्युत प्राधिकरण';
        $headerRows[1][0]   = '----------------- वार्षिक प्रतिवेदन';
        $headerRows[2][0]   = '१. आ.व. : ' . $this->fiscalYearTitle;
        $headerRows[2][11]  = '७. यस अवधिको खर्च रकम र प्रतिशत : ';
        $headerRows[3][0]   = '२. बजेट उपशीर्षक नं : ' . $budgetSubhead;
        $headerRows[3][11]  = '   (क)  अन्तरिक : ';
        $headerRows[4][0]   = '३. मन्त्रालय : ' . $ministry;
        $headerRows[4][11]  = '      १)  नेपाल सरकार : ';
        $headerRows[5][0]   = '४. आयोजनाको नाम : ' . $this->projectTitle;
        $headerRows[5][11]  = '      २)  ने. वि. प्रा : ';
        $headerRows[6][0]   = '५. आयोजना प्रमुखको नाम : ' . $managerName;
        $headerRows[6][11]  = '   ख)  वैदेशिक : ';
        $headerRows[7][0]   = '६. यस अवधिको बजेट : ' . $this->formatAmount($totalBudget / 1000, 2);
        $headerRows[7][11]  = '      १)  ऋण रु : ';
        $headerRows[8][0]   = '   (क)  अन्तरिक : ' . $this->formatAmount($totalInternalBudget / 1000, 2);
        $headerRows[8][11]  = '      २)  अनुदान रु : ';
        $headerRows[9][0]   = '      १)  नेपाल सरकार : ' . $this->formatAmount($govBudget / 1000, 2);
        $headerRows[9][11]  = '८. त्रैमासिक लक्ष्यको तुलनामा खर्च प्रतिशत : ';
        $headerRows[10][0]  = '      २)  ने. वि. प्रा : ' . $this->formatAmount($internalBudget / 1000, 2);
        $headerRows[10][11] = '९. शुरु देखि यस अवधिसम्मको कुल खर्च प्रतिशत (कुल लागतको तुलनामा) : ';
        $headerRows[11][0]  = '   ख)  वैदेशिक : ' . $this->formatAmount($totalForeignBudget / 1000, 2);
        $headerRows[11][11] = '१०. बितेको समय प्रतिशतमा (कुल अवधिको तुलनामा) : ';
        $headerRows[12][0]  = '      १)  ऋण रु : ' . $this->formatAmount($foreignLoanBudget / 1000, 2);
        $headerRows[12][11] = '११. सुरुदेखि यस अवधि सम्मको भौतिक प्रगति प्रतिशत : ';
        $headerRows[13][0]  = '      २)  अनुदान रु : ' . $this->formatAmount($foreignSubsidyBudget / 1000, 2);
        $headerRows[2][0]   = '१. आ.व. : ' . $this->fiscalYearTitle;
        $headerRows[2][11]  = '७. यस अवधिको खर्च रकम र प्रतिशत : ';
        $headerRows[3][0]   = '२. बजेट उपशीर्षक नं : ' . $budgetSubhead;
        $headerRows[3][11]  = '   (क)  अन्तरिक : ';
        $headerRows[4][0]   = '३. मन्त्रालय : ' . $ministry;
        $headerRows[4][11]  = '      १)  नेपाल सरकार : ';
        $headerRows[5][0]   = '४. आयोजनाको नाम : ' . $this->projectTitle;
        $headerRows[5][11]  = '      २)  ने. वि. प्रा : ';
        $headerRows[6][0]   = '५. आयोजना प्रमुखको नाम : ' . $managerName;
        $headerRows[6][11]  = '   ख)  वैदेशिक : ';
        $headerRows[7][0]   = '६. यस अवधिको बजेट : ' . $this->formatAmount($totalBudget / 1000, 2);
        $headerRows[7][11]  = '      १)  ऋण रु : ';
        $headerRows[8][0]   = '   (क)  अन्तरिक : ' . $this->formatAmount($totalInternalBudget / 1000, 2);
        $headerRows[8][11]  = '      २)  अनुदान रु : ';
        $headerRows[9][0]   = '      १)  नेपाल सरकार : ' . $this->formatAmount($govBudget / 1000, 2);
        $headerRows[9][11]  = '८. त्रैमासिक लक्ष्यको तुलनामा खर्च प्रतिशत : ';
        $headerRows[10][0]  = '      २)  ने. वि. प्रा : ' . $this->formatAmount($internalBudget / 1000, 2);
        $headerRows[10][11] = '९. शुरु देखि यस अवधिसम्मको कुल खर्च प्रतिशत (कुल लागतको तुलनामा) : ';
        $headerRows[11][0]  = '   ख)  वैदेशिक : ' . $this->formatAmount($totalForeignBudget / 1000, 2);
        $headerRows[11][11] = '१०. बितेको समय प्रतिशतमा (कुल अवधिको तुलनामा) : ';
        $headerRows[12][0]  = '      १)  ऋण रु : ' . $this->formatAmount($foreignLoanBudget / 1000, 2);
        $headerRows[12][11] = '११. सुरुदेखि यस अवधि सम्मको भौतिक प्रगति प्रतिशत : ';
        $headerRows[13][0]  = '      २)  अनुदान रु : ' . $this->formatAmount($foreignSubsidyBudget / 1000, 2);

        // index 14 stays null until physical-progress % is computed below
        $headerRows[]       = array_fill(0, 23, null);
        $headerRows[15][21] = '(रकम रु. हजारमा)';

        // ── Column-group labels (row 17) ──────────────────────────────────────
        $headerRows[]       = array_fill(0, 23, null);
        $headerRows[16][0]  = 'क्रम संख्या';
        $headerRows[16][1]  = 'कार्यक्रम/क्रियाकलापहरु';
        $headerRows[16][2]  = 'एकाइ';
        $headerRows[16][6]  = 'वार्षिक लक्ष्य';
        $headerRows[16][9]  = 'त्रैमशिक अवधिसम्मको लक्ष्य';
        $headerRows[16][12] = 'त्रैमासिक / वार्षिक प्रगति (भौतिक)';
        $headerRows[16][15] = 'त्रैमासिक / वार्षिक प्रगति (वित्तिय)';
        $headerRows[16][17] = 'त्रैमशिक अवधिसम्मको भौतिक प्रगति (बहुवर्षीय आयोजनाको हकमा)';
        $headerRows[16][20] = 'त्रैमशिक अवधिसम्मको खर्च';
        $headerRows[16][22] = 'कैफियत';

        // ── Sub-column labels (row 18) ────────────────────────────────────────
        $headerRows[]       = array_fill(0, 23, null);
        $headerRows[17][0]  = 'सङ्ख्या';
        $headerRows[17][1]  = 'क्रियाकलापहरु';
        $headerRows[17][3]  = 'परिमाण';
        $headerRows[17][4]  = 'भार';
        $headerRows[17][5]  = 'बजेट';
        $headerRows[17][6]  = 'परिमाण';
        $headerRows[17][7]  = 'भार';
        $headerRows[17][8]  = 'बजेट';
        $headerRows[17][9]  = 'परिमाण';
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

        // ── Column-number row (row 19) ────────────────────────────────────────
        $numberRow = array_fill(0, 23, null);
        for ($col = 0; $col < 23; $col++) {
            $numberRow[$col] = $this->convertToNepaliDigits((string) ($col + 1));
        }
        $headerRows[] = $numberRow;

        // ── Data rows ─────────────────────────────────────────────────────────
        $dataRows  = [];
        $grandSums = [];

        $emptyGrandSums = array_fill_keys([
            'total_qty',
            'total_budget',
            'annual_qty',
            'annual_amt',
            'period_qty_planned',
            'period_amt_planned',
            'period_qty_actual',
            'period_amt_actual',
            'quarter_qty_actual',
            'quarter_amt_actual',
            'quarter_amt_planned',
            'weighted_annual_qty',
            'weighted_quarter_physical',
            'weighted_period_physical',
        ], 0.0);

        // ── Period-spent amounts ──────────────────────────────────────────────
        $allocations = ProjectExpenseFundingAllocation::where('project_id', $this->projectId)
            ->where('fiscal_year_id', $this->fiscalYearId)
            ->where('quarter', '<=', $this->quarter)
            ->get();

        $spent_internal        = (float) $allocations->sum('internal_budget');
        $spent_gov_share       = (float) $allocations->sum('government_share');
        $spent_gov_loan        = (float) $allocations->sum('government_loan');
        $spent_foreign_loan    = (float) $allocations->sum('foreign_loan_budget');
        $spent_foreign_subsidy = (float) $allocations->sum('foreign_subsidy_budget');
        $spent_gov             = $spent_gov_share + $spent_gov_loan;
        $spent_internal_total  = $spent_internal + $spent_gov;
        $spent_foreign_total   = $spent_foreign_loan + $spent_foreign_subsidy;
        $total_period_spent    = $spent_internal_total + $spent_foreign_total;

        // Fallback: derive from expense quarter records if allocation table is empty
        if ($total_period_spent == 0) {
            $allExpenses = $this->project->expenses()
                ->with('quarters')
                ->where('fiscal_year_id', $this->fiscalYearId)
                ->get();

            $periodSpent = [
                'internal'         => 0.0,
                'government_share' => 0.0,
                'government_loan'  => 0.0,
                'foreign_loan'     => 0.0,
                'foreign_subsidy'  => 0.0,
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

            $spent_internal        = $periodSpent['internal'];
            $spent_gov_share       = $periodSpent['government_share'];
            $spent_gov_loan        = $periodSpent['government_loan'];
            $spent_gov             = $spent_gov_share + $spent_gov_loan;
            $spent_internal_total  = $spent_internal + $spent_gov;
            $spent_foreign_loan    = $periodSpent['foreign_loan'];
            $spent_foreign_subsidy = $periodSpent['foreign_subsidy'];
            $spent_foreign_total   = $spent_foreign_loan + $spent_foreign_subsidy;
            $total_period_spent    = $spent_internal_total + $spent_foreign_total;
        }

        // Fill right-hand header column with spent amounts
        $headerRows[2][11]  .= $this->formatAmount($total_period_spent / 1000, 2);
        $headerRows[3][11]  .= $this->formatAmount($spent_internal_total / 1000, 2);
        $headerRows[4][11]  .= $this->formatAmount($spent_gov / 1000, 2);
        $headerRows[5][11]  .= $this->formatAmount($spent_internal / 1000, 2);
        $headerRows[6][11]  .= $this->formatAmount($spent_foreign_total / 1000, 2);
        $headerRows[7][11]  .= $this->formatAmount($spent_foreign_loan / 1000, 2);
        $headerRows[8][11]  .= $this->formatAmount($spent_foreign_subsidy / 1000, 2);
        $headerRows[9][11]  .= $this->formatPercent(
            $totalBudget > 0 ? ($total_period_spent / $totalBudget) * 100 : 0.0
        );

        // ── Load activity definitions (natural sort by sort_index) ────────────
        $definitions = ProjectActivityDefinition::forProject($this->projectId)
            ->current()
            ->get(['id', 'parent_id', 'program', 'expenditure_id', 'total_budget', 'total_quantity', 'sort_index'])
            ->sort(fn($a, $b) => strnatcmp((string) $a->sort_index, (string) $b->sort_index))
            ->values();

        $activities = ProjectActivityPlan::whereIn('activity_definition_version_id', $definitions->pluck('id'))
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
            $headerRows[11][11] .= $this->formatPercent(($this->quarter / 4) * 100);
            $headerRows[12][11]  = ($headerRows[12][11] ?? '') . '०.००';
            $grandSums = $emptyGrandSums;
        } else {
            // ── Attach expense records to each plan ───────────────────────────
            $expensesCollection = ProjectExpense::with(['quarters'])
                ->whereIn('project_activity_plan_id', $activities->pluck('id'))
                ->get()
                ->groupBy('project_activity_plan_id');

            $activities = $activities->map(function ($act) use ($expensesCollection) {
                $act->setRelation('expenses', $expensesCollection->get($act->id, collect()));
                return $act;
            });

            $activities = $activities->sort(
                fn($a, $b) => strnatcmp(
                    (string) $a->definitionVersion->sort_index,
                    (string) $b->definitionVersion->sort_index
                )
            )->values();

            // ── Group by parent; identify roots and parents-with-children ─────
            $groupedActivities = $activities
                ->groupBy(fn($p) => $p->definitionVersion->parent_id ?? 'null')
                ->map(fn($group) => $group->sort(
                    fn($a, $b) => strnatcmp(
                        (string) $a->definitionVersion->sort_index,
                        (string) $b->definitionVersion->sort_index
                    )
                )->values());
            $hasChildrenMap    = $groupedActivities->keys()->filter(fn($k) => $k !== 'null')->values()->toArray();

            $capital_roots   = $activities->filter(fn($p) => is_null($p->definitionVersion->parent_id) && $p->definitionVersion->expenditure_id == 1);
            $recurrent_roots = $activities->filter(fn($p) => is_null($p->definitionVersion->parent_id) && $p->definitionVersion->expenditure_id == 2);

            // ── Compute leaf-level values ─────────────────────────────────────
            $leafValues = [];
            foreach ($activities as $act) {
                $defId = $act->definitionVersion->id;

                $period_qty_planned = 0.0;
                $period_amt_planned = 0.0;
                for ($i = 1; $i <= $this->quarter; $i++) {
                    $period_qty_planned += (float) ($act->{"q{$i}_quantity"} ?? 0);
                    $period_amt_planned += (float) ($act->{"q{$i}_amount"}   ?? 0);
                }

                $period_qty_actual  = 0.0;
                $period_amt_actual  = 0.0;
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

                $total_qty           = (float) ($act->definitionVersion->total_quantity ?? 0);
                $total_budget        = (float) ($act->definitionVersion->total_budget   ?? 0);
                $annual_qty          = (float) ($act->planned_quantity ?? 0);
                $annual_amt          = (float) ($act->planned_budget   ?? 0);
                $quarter_amt_planned = (float) ($act->{"q{$this->quarter}_amount"} ?? 0);

                // Parent nodes: zero out plan values — children carry them
                if (in_array($defId, $hasChildrenMap)) {
                    $annual_qty = $annual_amt = $period_qty_planned = $period_amt_planned = $quarter_amt_planned = 0.0;
                }

                $leafValues[$defId] = [
                    'total_qty'                 => $total_qty,
                    'total_budget'              => $total_budget,
                    'annual_qty'                => $annual_qty,
                    'annual_amt'                => $annual_amt,
                    'period_qty_planned'        => $period_qty_planned,
                    'period_amt_planned'        => $period_amt_planned,
                    'period_qty_actual'         => $period_qty_actual,
                    'period_amt_actual'         => $period_amt_actual,
                    'quarter_qty_actual'        => $quarter_qty_actual,
                    'quarter_amt_actual'        => $quarter_amt_actual,
                    'quarter_amt_planned'       => $quarter_amt_planned,
                    'weighted_annual_qty'       => $total_qty > 0 ? ($annual_qty        / $total_qty * $total_budget) : 0.0,
                    'weighted_quarter_physical' => $total_qty > 0 ? ($quarter_qty_actual / $total_qty * $total_budget) : 0.0,
                    'weighted_period_physical'  => $total_qty > 0 ? ($period_qty_actual  / $total_qty * $total_budget) : 0.0,
                ];
            }

            $defaultSums = array_fill_keys(array_keys(reset($leafValues)), 0.0);

            // ── Memoised recursive subtree aggregation ────────────────────────
            $subtreeSums        = [];
            $computeSubtreeSums = function (int $defId) use (
                $leafValues,
                $groupedActivities,
                $defaultSums,
                &$subtreeSums,
                &$computeSubtreeSums
            ): array {
                if (isset($subtreeSums[$defId])) {
                    return $subtreeSums[$defId];
                }

                $sums = $leafValues[$defId] ?? $defaultSums;

                foreach ($groupedActivities[$defId] ?? collect() as $child) {
                    foreach ($computeSubtreeSums($child->definitionVersion->id) as $key => $val) {
                        $sums[$key] += $val;
                    }
                }

                return $subtreeSums[$defId] = $sums;
            };

            foreach ($capital_roots->concat($recurrent_roots) as $root) {
                $computeSubtreeSums($root->definitionVersion->id);
            }

            // ── Section totals ────────────────────────────────────────────────
            $this->globalXCapital      = $capital_roots->sum(fn($r)  => $subtreeSums[$r->definitionVersion->id]['total_budget']);
            $this->globalXRecurrent    = $recurrent_roots->sum(fn($r) => $subtreeSums[$r->definitionVersion->id]['total_budget']);
            $this->totalProjectCost    = $this->globalXCapital + $this->globalXRecurrent;
            $this->capitalPeriodTotal  = $capital_roots->sum(fn($r)  => $subtreeSums[$r->definitionVersion->id]['period_amt_planned']);
            $this->recurrentPeriodTotal = $recurrent_roots->sum(fn($r) => $subtreeSums[$r->definitionVersion->id]['period_amt_planned']);

            // ── Cumulative spent across all past fiscal years ─────────────────
            $cumulativeSpent = $total_period_spent;
            FiscalYear::where('start_date', '<', $this->fiscalYear->start_date)
                ->get()
                ->each(function ($pastFy) use (&$cumulativeSpent) {
                    $cumulativeSpent += (float) ProjectExpenseFundingAllocation
                        ::where('project_id', $this->projectId)
                        ->where('fiscal_year_id', $pastFy->id)
                        ->get()
                        ->sum(fn($a) => $a->internal_budget + $a->government_share + $a->government_loan
                            + $a->foreign_loan_budget + $a->foreign_subsidy_budget);
                });

            $headerRows[10][11] .= $this->formatPercent(
                $this->totalProjectCost > 0 ? ($cumulativeSpent / $this->totalProjectCost * 100) : 0.0
            );
            $headerRows[11][11] .= $this->formatPercent(($this->quarter / 4) * 100);

            // ── Row-traversal closure ─────────────────────────────────────────
            $traverse = function ($acts, int $level = 0) use (
                &$dataRows,
                &$traverse,
                $groupedActivities,
                $subtreeSums
            ): void {
                foreach ($acts as $act) {
                    $def              = $act->definitionVersion;
                    $defId            = $def->id;
                    $indent           = str_repeat('    ', $level);
                    $effectiveProgram = $act->program_override ?? $def->program ?? '';
                    $children         = $groupedActivities[$defId] ?? collect();
                    $hasChildren      = $children->isNotEmpty();
                    $subtree          = $subtreeSums[$defId];
                    $globalX          = ($def->expenditure_id == 1) ? $this->globalXCapital     : $this->globalXRecurrent;
                    $periodTotal      = ($def->expenditure_id == 1) ? $this->capitalPeriodTotal  : $this->recurrentPeriodTotal;

                    if ($hasChildren) {
                        // Parent row — serial + name only
                        $parentRow    = array_fill(0, 23, '');
                        $parentRow[0] = $this->convertToNepaliDigits((string) ($def->sort_index ?? ''));
                        $parentRow[1] = $indent . $effectiveProgram;
                        $dataRows[]   = $parentRow;

                        $traverse($children, $level + 1);

                        // Subtotal row
                        $dataRows[] = $this->buildDataRow(
                            $indent . 'जम्मा ' . ($def->sort_index ?? ''),
                            null,
                            $subtree,
                            $globalX,
                            $periodTotal
                        );
                    } else {
                        // Leaf row
                        $dataRows[] = $this->buildDataRow(
                            $indent . $effectiveProgram,
                            $this->convertToNepaliDigits((string) ($def->sort_index ?? '')),
                            $subtree,
                            $globalX,
                            $periodTotal
                        );
                    }
                }
            };

            // ── Capital section ───────────────────────────────────────────────
            $capitalHeader    = array_fill(0, 23, null);
            $capitalHeader[0] = 'पुँजीगत कार्यक्रमहरु :';
            $dataRows[]       = $capitalHeader;

            $capitalSectionSums = $defaultSums;
            if ($capital_roots->isNotEmpty()) {
                $traverse($capital_roots);
                foreach ($capital_roots as $root) {
                    foreach ($subtreeSums[$root->definitionVersion->id] as $key => $val) {
                        $capitalSectionSums[$key] += $val;
                    }
                }
                $dataRows[] = $this->makeTotalRow('(क) जम्मा', $capitalSectionSums, $this->globalXCapital, $this->capitalPeriodTotal);
            }

            // ── Recurrent section ─────────────────────────────────────────────
            $recurrentHeader    = array_fill(0, 23, null);
            $recurrentHeader[0] = '(ख) चालु कार्यक्रमहरु';
            $dataRows[]         = $recurrentHeader;

            $recurrentSectionSums = $defaultSums;
            if ($recurrent_roots->isNotEmpty()) {
                $traverse($recurrent_roots);
                foreach ($recurrent_roots as $root) {
                    foreach ($subtreeSums[$root->definitionVersion->id] as $key => $val) {
                        $recurrentSectionSums[$key] += $val;
                    }
                }
                $dataRows[] = $this->makeTotalRow('(ख) जम्मा', $recurrentSectionSums, $this->globalXRecurrent, $this->recurrentPeriodTotal);
            }

            // ── Grand total ───────────────────────────────────────────────────
            $grandSums = $defaultSums;
            foreach ($capitalSectionSums   as $key => $val) $grandSums[$key] += $val;
            foreach ($recurrentSectionSums as $key => $val) $grandSums[$key] += $val;

            $dataRows[] = $this->makeTotalRow(
                'जम्मा',
                $grandSums,
                $this->totalProjectCost,
                $this->capitalPeriodTotal + $this->recurrentPeriodTotal
            );

            // Physical progress %
            $total_annual_qty        = $capitalSectionSums['annual_qty']       + $recurrentSectionSums['annual_qty'];
            $total_period_qty_actual = $capitalSectionSums['period_qty_actual'] + $recurrentSectionSums['period_qty_actual'];
            $headerRows[12][11] = ($headerRows[12][11] ?? '') . $this->formatPercent(
                $total_annual_qty > 0 ? ($total_period_qty_actual / $total_annual_qty * 100) : 0.0
            );
        }

        // ── Quarterly financial progress ──────────────────────────────────────
        $quarterly_financial_progress = ($grandSums['period_amt_planned'] ?? 0.0) > 0
            ? (($grandSums['period_amt_actual'] ?? 0.0) / $grandSums['period_amt_planned'] * 100)
            : 0.0;

        // ── Footer / signature rows ───────────────────────────────────────────
        $dataRows[] = array_fill(0, 23, ''); // spacer

        $progressRow    = array_fill(0, 23, '');
        $progressRow[0] = $this->reportingPeriod
            . ' प्रगति प्रतिशत : '
            . $this->formatPercent($quarterly_financial_progress);
        $dataRows[] = $progressRow;

        // Signature header row — labels at col 2 (A:H), col 10 (I:P), col 18 (Q:W)
        $signatureHeader     = array_fill(0, 23, '');
        $signatureHeader[0]  = 'कार्यालय वा आयोजना प्रमुख:';
        $signatureHeader[8]  = 'विभागीय वा निकाय प्रमुख:';
        $signatureHeader[16] = 'प्रमाणित गर्ने:';
        $dataRows[] = $signatureHeader;

        // Date row
        $dateRow     = array_fill(0, 23, '');
        $dateRow[0]  = 'मिति :-';
        $dateRow[8]  = 'मिति :-';
        $dateRow[16] = 'मिति :-';
        $dataRows[] = $dateRow;

        return collect(array_merge($headerRows, $dataRows));
    }

    // -------------------------------------------------------------------------
    // Row builders
    // -------------------------------------------------------------------------

    private function buildDataRow(
        string  $label,
        ?string $serialNumber,
        array   $subtree,
        float   $globalX,
        float   $periodTotal
    ): array {
        $row    = array_fill(0, 23, '');
        $row[0] = $serialNumber ?? '';
        $row[1] = $label;

        $row[3]  = $this->formatQuantity($subtree['total_qty'], 2);
        $row[4]  = $this->formatPercent($globalX > 0 ? ($subtree['total_budget'] / $globalX * 100) : 0);
        $row[5]  = $this->formatAmount($subtree['total_budget'] / 1000, 2);
        $row[6]  = $this->formatQuantity($subtree['annual_qty'], 2);
        $row[7]  = $this->formatPercent($globalX > 0 ? ($subtree['weighted_annual_qty'] / $globalX * 100) : 0);
        $row[8]  = $this->formatAmount($subtree['annual_amt'] / 1000, 2);
        $row[9]  = $this->formatQuantity($subtree['period_qty_planned'], 2);
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

        return $row;
    }

    private function makeTotalRow(string $label, array $sums, float $globalX, float $periodTotal): array
    {
        return $this->buildDataRow($label, null, $sums, $globalX, $periodTotal);
    }

    // -------------------------------------------------------------------------
    // Export metadata
    // -------------------------------------------------------------------------

    public function title(): string
    {
        return 'Progress Report ' . str_replace(['/', '\\'], '_', $this->fiscalYearTitle);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 30,
            'C' => 6,
            'D' => 8,
            'E' => 6,
            'F' => 8,
            'G' => 8,
            'H' => 6,
            'I' => 8,
            'J' => 8,
            'K' => 6,
            'L' => 8,
            'M' => 8,
            'N' => 6,
            'O' => 8,
            'P' => 8,
            'Q' => 6,
            'R' => 8,
            'S' => 6,
            'T' => 8,
            'U' => 8,
            'V' => 6,
            'W' => 15,
        ];
    }

    // -------------------------------------------------------------------------
    // Styles
    // -------------------------------------------------------------------------

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:W1');
        $sheet->mergeCells('A2:W2');

        for ($i = 3; $i <= 15; $i++) {
            $sheet->mergeCells("A{$i}:K{$i}");
            $sheet->mergeCells("L{$i}:W{$i}");
        }

        $sheet->mergeCells('V16:W16');
        $sheet->getStyle('V16')
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('V16')->getFont()->setSize(10);

        // Vertical header merges
        foreach (['A17:A18', 'B17:B18', 'C17:C18', 'W17:W18'] as $m) {
            $sheet->mergeCells($m);
        }
        // Horizontal header merges
        foreach (['D17:F17', 'G17:I17', 'J17:L17', 'M17:O17', 'P17:Q17', 'R17:T17', 'U17:V17'] as $m) {
            $sheet->mergeCells($m);
        }

        $sheet->getStyle('A17:W18')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);

        $sheet->getStyle('A19:W19')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
        $sheet->getRowDimension(19)->setRowHeight(20);

        $lastRow = $sheet->getHighestRow();
        for ($row = 20; $row <= $lastRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(20);
            $sheet->getStyle("A{$row}:W{$row}")->applyFromArray([
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
                'font'      => ['size' => 8],
            ]);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}:V{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("W{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        $sheet->getStyle("A1:W{$lastRow}")->getFont()->setName('Nirmala UI');

        $sheet->getRowDimension(16)->setRowHeight(18);
        $sheet->getRowDimension(17)->setRowHeight(35);
        $sheet->getRowDimension(18)->setRowHeight(35);

        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        for ($i = 3; $i <= 15; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(25);
            foreach (['A', 'L'] as $col) {
                $sheet->getStyle("{$col}{$i}")->applyFromArray([
                    'font'      => ['size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
            }
        }

        //$sheet->getStyle('A6:A9')->getFont()->setBold(true);
        //$sheet->getStyle('L7:L11')->getFont()->setBold(true);

        return [];
    }

    // -------------------------------------------------------------------------
    // Events
    // -------------------------------------------------------------------------

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach (['D', 'E', 'F'] as $col) {
                    $sheet->getColumnDimension($col)->setVisible(false);
                }

                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageSetup()->setHorizontalCentered(true);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(16, 19);

                $sheet->getPageMargins()
                    ->setTop(0.5)->setRight(0.5)->setLeft(0.5)->setBottom(0.5)
                    ->setHeader(0.3)->setFooter(0.3);

                $lastRowBeforeSignatures = $sheet->getHighestRow();

                for ($row = 20; $row <= $lastRowBeforeSignatures; $row++) {
                    $cellA = (string) $sheet->getCell("A{$row}")->getValue();
                    $cellB = (string) $sheet->getCell("B{$row}")->getValue();

                    // Section header rows
                    if (preg_match('/(पुँजीगत|चालु)\s*कार्यक्रमहरु/', $cellA)) {
                        $sheet->mergeCells("A{$row}:W{$row}");
                        $sheet->getStyle("A{$row}")->applyFromArray([
                            'font'      => ['bold' => true, 'size' => 12],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        ]);
                        $sheet->getRowDimension($row)->setRowHeight(25);
                    }

                    // Total rows — yellow highlight
                    if (
                        str_contains($cellA, 'जम्मा') ||
                        str_contains($cellB, 'जम्मा') ||
                        str_contains($cellB, 'Total of ')
                    ) {
                        $sheet->getStyle("A{$row}:W{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                            'font' => ['bold' => true],
                        ]);
                    }
                }

                // Merge C:V for pure parent rows (serial + name, no numeric data)
                $signatureStartRow = $sheet->getHighestRow() - 3;
                for ($row = 20; $row < $signatureStartRow; $row++) {
                    $valA = (string) $sheet->getCell("A{$row}")->getValue();
                    $valB = (string) $sheet->getCell("B{$row}")->getValue();
                    $valD = $sheet->getCell("D{$row}")->getValue();

                    if (
                        $valA !== '' && $valB !== '' && empty($valD) &&
                        !str_contains($valB, 'जम्मा') &&
                        !str_contains($valA, 'पुँजीगत') &&
                        !str_contains($valA, 'चालु')
                    ) {
                        $sheet->mergeCells("C{$row}:V{$row}");
                        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    }
                }

                // ── Footer rows ───────────────────────────────────────────────
                $lastRow      = $sheet->getHighestRow();
                $progressRow  = $lastRow - 2;
                $sigHeaderRow = $lastRow - 1;
                $dateRowNum   = $lastRow;

                // Progress percentage row — full width, bold, left-aligned
                $sheet->mergeCells("A{$progressRow}:W{$progressRow}");
                $sheet->getStyle("A{$progressRow}:W{$progressRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                ]);
                $sheet->getRowDimension($progressRow)->setRowHeight(30);

                // Signature titles row — three equal thirds, LEFT aligned
                foreach (
                    [
                        "A{$sigHeaderRow}:H{$sigHeaderRow}",
                        "I{$sigHeaderRow}:P{$sigHeaderRow}",
                        "Q{$sigHeaderRow}:W{$sigHeaderRow}",
                    ] as $m
                ) {
                    $sheet->mergeCells($m);
                }
                $sheet->getStyle("A{$sigHeaderRow}:W{$sigHeaderRow}")->applyFromArray([
                    'font'      => ['size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                ]);
                $sheet->getRowDimension($sigHeaderRow)->setRowHeight(40);

                // Date row — three equal thirds, LEFT aligned
                foreach (
                    [
                        "A{$dateRowNum}:H{$dateRowNum}",
                        "I{$dateRowNum}:P{$dateRowNum}",
                        "Q{$dateRowNum}:W{$dateRowNum}",
                    ] as $m
                ) {
                    $sheet->mergeCells($m);
                }
                $sheet->getStyle("A{$dateRowNum}:W{$dateRowNum}")->applyFromArray([
                    'font'      => ['size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical'   => Alignment::VERTICAL_TOP,
                    ],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                ]);
                $sheet->getRowDimension($dateRowNum)->setRowHeight(50);
            },
        ];
    }
}
