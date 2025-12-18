<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
use App\Models\Directorate;
use Illuminate\Http\Request;
use App\Models\BudgetHeading;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\BudgetQuaterAllocation;
use App\Models\ProjectExpenseFundingAllocation;
use App\Exports\Reports\Consolidated\BudgetReportExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\Reports\Consolidated\AnnualProgramReportExport;

class ReportController extends Controller
{
    /**
     * Shared data for report views
     */
    private function reportViewData(Request $request): array
    {
        $fiscalYearOptions = FiscalYear::getFiscalYearOptions();

        $selectedFiscalYearId = $request->input('fiscal_year_id')
            ?? collect($fiscalYearOptions)->firstWhere('selected', true)['value'] ?? null
            ?? collect($fiscalYearOptions)->first()['value'] ?? null;

        return [
            'fiscalYearOptions'    => $fiscalYearOptions,
            'selectedFiscalYearId' => $selectedFiscalYearId,
            'directorates'         => Directorate::orderBy('title')->get(),
            'budgetHeadings'       => BudgetHeading::orderBy('title')->pluck('title', 'id'),
        ];
    }

    /**
     * Show the consolidated annual report generation view
     */
    public function showConsolidatedAnnualReport(Request $request): View
    {
        return view(
            'admin.reports.consolidated-annual',
            $this->reportViewData($request)
        );
    }

    /**
     * Get project count for preview summary
     */
    public function getProjectCount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fiscal_year_id'        => 'required|integer|exists:fiscal_years,id',
            'directorate_ids'       => 'nullable|array',
            'directorate_ids.*'     => 'integer|exists:directorates,id',
            'budget_heading_ids'    => 'nullable|array',
            'budget_heading_ids.*'  => 'integer|exists:budget_headings,id',
        ]);

        $query = Project::query();

        // Filter by directorates
        if (!empty($validated['directorate_ids'] ?? [])) {
            $query->whereIn('directorate_id', $validated['directorate_ids']);
        }

        // Filter by budget headings
        if (!empty($validated['budget_heading_ids'] ?? [])) {
            $query->whereIn('budget_heading_id', $validated['budget_heading_ids']);
        }

        $projectCount = $query->count();

        return response()->json([
            'success'       => true,
            'project_count' => $projectCount,
        ]);
    }

    /**
     * Generate and download the annual program progress report
     */
    public function consolidatedAnnualReport(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'fiscal_year'           => 'nullable|string|max:20',
            'fiscal_year_id'        => 'required|integer|exists:fiscal_years,id',
            'quarter'               => 'required|string|in:प्रथम,दोस्रो,तेस्रो,चौथो',
            'row_count'             => 'nullable|integer|min:1|max:200',
            'include_data'          => 'nullable|boolean',
            'directorate_ids'       => 'nullable|array',
            'directorate_ids.*'     => 'integer|exists:directorates,id',
            'budget_heading_ids'    => 'nullable|array',
            'budget_heading_ids.*'  => 'integer|exists:budget_headings,id',
            'consolidated'          => 'nullable|in:0,1',
        ]);

        $isConsolidated = (bool) ($validated['consolidated'] ?? false);

        // Quarter mappings
        $quarterMapForBudget = [
            'प्रथम'  => 'Q1',
            'दोस्रो' => 'Q2',
            'तेस्रो' => 'Q3',
            'चौथो' => 'Q4',
        ];

        $quarterMapForExpense = [
            'प्रथम'  => 1,
            'दोस्रो' => 2,
            'तेस्रो' => 3,
            'चौथो' => 4,
        ];

        $currentQuarterBudget  = $quarterMapForBudget[$validated['quarter']];
        $currentQuarterExpense = $quarterMapForExpense[$validated['quarter']];

        // Determine quarters to sum (cumulative if consolidated)
        if ($isConsolidated) {
            $quartersToSumBudget  = ['Q1', 'Q2', 'Q3', 'Q4'];
            $quartersToSumExpense = [1, 2, 3, 4];

            $index = array_search($currentQuarterBudget, ['Q1', 'Q2', 'Q3', 'Q4']);
            if ($index !== false) {
                $quartersToSumBudget  = array_slice(['Q1', 'Q2', 'Q3', 'Q4'], 0, $index + 1);
                $quartersToSumExpense = array_slice([1, 2, 3, 4], 0, $index + 1);
            }
        } else {
            $quartersToSumBudget  = [$currentQuarterBudget];
            $quartersToSumExpense = [$currentQuarterExpense];
        }

        // Prepare export parameters
        $parameters = [
            'fiscal_year'  => $validated['fiscal_year'] ?? '०८२/८३',
            'quarter'      => $validated['quarter'],
            'row_count'    => $validated['row_count'] ?? 10,
            'include_data' => $validated['include_data'] ?? true,
            'consolidated' => $isConsolidated,
            'projects'     => [],
        ];

        if ($parameters['include_data']) {
            $fiscalYearId = $validated['fiscal_year_id'];
            $directorateIds = $validated['directorate_ids'] ?? [];
            $budgetHeadingIds = $validated['budget_heading_ids'] ?? [];

            $query = Project::with(['directorate', 'budgets', 'budgetHeading'])
                ->orderBy('directorate_id')
                ->orderBy('title');

            // Apply filters
            if (!empty($directorateIds)) {
                $query->whereIn('directorate_id', $directorateIds);
            }

            if (!empty($budgetHeadingIds)) {
                $query->whereIn('budget_heading_id', $budgetHeadingIds);
            }

            $projects = $query->get()->map(function ($project) use (
                $fiscalYearId,
                $quartersToSumBudget,
                $quartersToSumExpense
            ) {
                $budget = $project->budgets()
                    ->where('fiscal_year_id', $fiscalYearId)
                    ->first();

                // Cumulative Planned Budget
                $plannedGovernmentShare = 0;
                $plannedGovernmentLoan  = 0;
                $plannedForeignLoan     = 0;
                $plannedForeignSubsidy  = 0;
                $plannedInternalBudget  = 0;
                $plannedTotal           = 0;

                if ($budget) {
                    $plannedAllocations = BudgetQuaterAllocation::where('budget_id', $budget->id)
                        ->whereIn('quarter', $quartersToSumBudget)
                        ->get();

                    $plannedGovernmentShare = $plannedAllocations->sum('government_share');
                    $plannedGovernmentLoan  = $plannedAllocations->sum('government_loan');
                    $plannedForeignLoan     = $plannedAllocations->sum('foreign_loan');
                    $plannedForeignSubsidy  = $plannedAllocations->sum('foreign_subsidy');
                    $plannedInternalBudget  = $plannedAllocations->sum('internal_budget');
                    $plannedTotal           = $plannedAllocations->sum('total_budget');
                }

                $plannedNepalGov = $plannedGovernmentShare + $plannedGovernmentLoan;

                // Cumulative Actual Expense
                $expenseAllocations = ProjectExpenseFundingAllocation::where('project_id', $project->id)
                    ->where('fiscal_year_id', $fiscalYearId)
                    ->whereIn('quarter', $quartersToSumExpense)
                    ->get();

                $expenseGovernmentShare = $expenseAllocations->sum('government_share');
                $expenseGovernmentLoan  = $expenseAllocations->sum('government_loan');
                $expenseForeignLoan     = $expenseAllocations->sum('foreign_loan_budget');
                $expenseForeignSubsidy  = $expenseAllocations->sum('foreign_subsidy_budget');
                $expenseInternalBudget  = $expenseAllocations->sum('internal_budget');

                $expenseNepalGov = $expenseGovernmentShare + $expenseGovernmentLoan;
                $expenseTotal = $expenseNepalGov + $expenseForeignLoan + $expenseForeignSubsidy + $expenseInternalBudget;

                // Percentages
                $targetNepalGovPercent = $plannedNepalGov > 0
                    ? round(($expenseNepalGov / $plannedNepalGov) * 100, 2)
                    : 0;

                $targetNeaPercent = $plannedInternalBudget > 0
                    ? round(($expenseInternalBudget / $plannedInternalBudget) * 100, 2)
                    : 0;

                $targetTotalPercent = $plannedTotal > 0
                    ? round(($expenseTotal / $plannedTotal) * 100, 2)
                    : 0;

                return [
                    'directorate_id'     => $project->directorate_id,
                    'directorate_title'  => $project->directorate->title ?? 'Unknown',
                    'title'              => $project->title,
                    'budget_heading'     => $project->budgetHeading->title ?? '',
                    'progress_percent'   => $project->progress_percent ?? 0,

                    // Planned
                    'budget_nepal_gov_contribution' => $plannedGovernmentShare,
                    'budget_nepal_gov_loan'         => $plannedGovernmentLoan,
                    'budget_foreign_loan'           => $plannedForeignLoan,
                    'budget_foreign_grant'          => $plannedForeignSubsidy,
                    'budget_total_nepal_gov'        => $plannedNepalGov,
                    'budget_nea'                    => $plannedInternalBudget,
                    'budget_total'                  => $plannedTotal,

                    // Actual
                    'expense_nepal_gov'             => $expenseNepalGov,
                    'expense_foreign_loan'          => $expenseForeignLoan,
                    'expense_foreign_grant'         => $expenseForeignSubsidy,
                    'expense_total_nepal_gov'       => $expenseNepalGov,
                    'expense_nea'                   => $expenseInternalBudget,
                    'expense_total'                 => $expenseTotal,

                    // Percentages
                    'target_nepal_gov_percent' => $targetNepalGovPercent,
                    'target_nea_percent'       => $targetNeaPercent,
                    'target_total_percent'     => $targetTotalPercent,

                    // Additional
                    'semi_annual_total_expense'     => $project->semi_annual_total_expense ?? 0,
                    'semi_annual_progress_percent'  => $project->semi_annual_progress_percent ?? 0,
                    'remarks'                       => $project->remarks ?? '',
                    'dhar'                          => $project->dhar ?? '',
                    'weighted_progress_1'           => $project->weighted_progress_1 ?? 0,
                    'weighted_progress_2'           => $project->weighted_progress_2 ?? 0,
                ];
            })->toArray();

            $parameters['projects'] = $projects;
        }

        // Filename
        $consolidatedSuffix = $isConsolidated ? '_Consolidated' : '';
        $quarterCode = $this->getQuarterCode($parameters['quarter']);
        $filename = "Progress_Report_Q{$quarterCode}{$consolidatedSuffix}_"
            . str_replace('/', '_', $parameters['fiscal_year']) . '_'
            . date('Ymd') . '.xlsx';

        return Excel::download(
            new AnnualProgramReportExport($parameters),
            $filename
        );
    }

    /**
     * Helper: Get quarter code for filename
     */
    private function getQuarterCode(string $quarter): string
    {
        return match ($quarter) {
            'प्रथम'  => 'Q1',
            'दोस्रो' => 'Q2',
            'तेस्रो' => 'Q3',
            'चौथो' => 'Q4',
            default => 'Q1',
        };
    }

    /**
     * Show the budget report view
     */
    public function showBudgetReportView(Request $request): View
    {
        return view(
            'admin.reports.budgets',
            $this->reportViewData($request)
        );
    }

    /**
     * Generate simple budget report
     */
    public function budgetReport(): BinaryFileResponse
    {
        return Excel::download(new BudgetReportExport, 'budget_report_' . date('Ymd') . '.xlsx');
    }
}
