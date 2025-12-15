<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use App\Models\Directorate;
use App\Models\FiscalYear;
use App\Models\ProjectExpenseFundingAllocation;
use App\Models\BudgetQuaterAllocation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\Reports\Consolidated\AnnualProgramReportExport;

class ReportController extends Controller
{
    /**
     * Show the report generation view
     */
    public function showConsolidatedAnnualReport(): View
    {
        $fiscalYears = FiscalYear::orderBy('title', 'desc')->get();
        $directorates = Directorate::orderBy('title')->get();

        return view('admin.reports.consolidated-annual', compact('fiscalYears', 'directorates'));
    }

    /**
     * Get project count for preview
     */
    public function getProjectCount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fiscal_year_id' => 'required|integer',
            'directorate_ids' => 'nullable|array',
        ]);

        $query = Project::query();

        if (!empty($validated['directorate_ids'])) {
            $query->whereIn('directorate_id', $validated['directorate_ids']);
        }

        $projectCount = $query->count();

        return response()->json([
            'success' => true,
            'project_count' => $projectCount,
        ]);
    }

    /**
     * Generate and download the consolidated annual report
     */
    public function consolidatedAnnualReport(Request $request): BinaryFileResponse
    {
        // Validate request
        $validated = $request->validate([
            'fiscal_year' => 'nullable|string|max:20',
            'fiscal_year_id' => 'required|integer',
            'quarter' => 'required|string',
            'row_count' => 'nullable|integer|min:1|max:100',
            'include_data' => 'nullable|boolean',
            'directorate_ids' => 'nullable|array',
        ]);

        // Map quarter text to quarter number
        $quarterMap = [
            'प्रथम' => 1,
            'दोस्रो' => 2,
            'तेस्रो' => 3,
            'चौथो' => 4,
        ];
        $quarterNumber = $quarterMap[$validated['quarter']] ?? 1;

        // Prepare parameters
        $parameters = [
            'fiscal_year' => $validated['fiscal_year'] ?? '०८२/८३',
            'quarter' => $validated['quarter'] ?? 'प्रथम',
            'row_count' => $validated['row_count'] ?? 10,
            'include_data' => $validated['include_data'] ?? true,
            'projects' => [],
        ];

        // Fetch projects if include_data is true
        if ($parameters['include_data']) {
            $fiscalYearId = $validated['fiscal_year_id'];

            $query = Project::with(['directorate', 'budgets'])
                ->orderBy('directorate_id')
                ->orderBy('title');

            // Optional: Filter by specific directorates
            if (!empty($validated['directorate_ids'])) {
                $query->whereIn('directorate_id', $validated['directorate_ids']);
            }

            $projects = $query->get()->map(function ($project) use ($fiscalYearId, $quarterNumber) {
                // Get the first budget for this project
                $budget = $project->budgets->first();

                // Calculate values from budget
                $governmentShare = $budget->government_share ?? 0;
                $governmentLoan = $budget->government_loan ?? 0;
                $foreignLoan = $budget->foreign_loan_budget ?? 0;
                $foreignSubsidy = $budget->foreign_subsidy_budget ?? 0;
                $internalBudget = $budget->internal_budget ?? 0;
                $totalBudget = $budget->total_budget ?? 0;

                // Calculate total Nepal government (government share + government loan)
                $totalNepalGov = $governmentShare + $governmentLoan;

                // Get expense funding allocation for this quarter (ACTUAL EXPENSES)
                $expenseFunding = ProjectExpenseFundingAllocation::where('project_id', $project->id)
                    ->where('fiscal_year_id', $fiscalYearId)
                    ->where('quarter', $quarterNumber)
                    ->first();

                // Calculate actual expense values
                $expenseGovernmentShare = $expenseFunding->government_share ?? 0;
                $expenseGovernmentLoan = $expenseFunding->government_loan ?? 0;
                $expenseForeignLoan = $expenseFunding->foreign_loan_budget ?? 0;
                $expenseForeignSubsidy = $expenseFunding->foreign_subsidy_budget ?? 0;
                $expenseInternalBudget = $expenseFunding->internal_budget ?? 0;

                // Calculate expense totals
                $expenseNepalGov = $expenseGovernmentShare + $expenseGovernmentLoan;
                $expenseTotal = $expenseNepalGov + $expenseForeignLoan + $expenseForeignSubsidy + $expenseInternalBudget;

                // Get planned budget allocation for this quarter
                $plannedBudget = null;
                if ($budget) {
                    $plannedBudget = BudgetQuaterAllocation::where('budget_id', $budget->id)
                        ->where('quarter', $quarterNumber)
                        ->first();
                }

                // Calculate planned budget values for the quarter
                $plannedGovernmentShare = $plannedBudget->government_share ?? 0;
                $plannedGovernmentLoan = $plannedBudget->government_loan ?? 0;
                $plannedInternalBudget = $plannedBudget->internal_budget ?? 0;
                $plannedForeignLoan = $plannedBudget->foreign_loan ?? 0;
                $plannedForeignSubsidy = $plannedBudget->foreign_subsidy ?? 0;

                // Calculate planned totals
                $plannedNepalGov = $plannedGovernmentShare + $plannedGovernmentLoan;
                $plannedTotal = $plannedBudget->total_budget ?? 0;

                // Calculate percentages (Actual vs Planned for this quarter)
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
                    'directorate_id' => $project->directorate_id,
                    'directorate_title' => $project->directorate->title ?? 'Unknown',
                    'title' => $project->title,
                    'budget_heading' => $project->budget_heading ?? '',
                    'progress_percent' => $project->progress_percent ?? 0,

                    // Budget fields from Budget model (ANNUAL BUDGET)
                    'budget_nepal_gov_contribution' => $governmentShare,
                    'budget_nepal_gov_loan' => $governmentLoan,
                    'budget_foreign_loan' => $foreignLoan,
                    'budget_foreign_grant' => $foreignSubsidy,
                    'budget_total_nepal_gov' => $totalNepalGov,
                    'budget_nea' => $internalBudget,
                    'budget_total' => $totalBudget,

                    // Expense fields from ProjectExpenseFundingAllocation (ACTUAL QUARTERLY EXPENSES)
                    'expense_nepal_gov' => $expenseNepalGov,
                    'expense_foreign_loan' => $expenseForeignLoan,
                    'expense_foreign_grant' => $expenseForeignSubsidy,
                    'expense_total_nepal_gov' => $expenseNepalGov,
                    'expense_nea' => $expenseInternalBudget,
                    'expense_total' => $expenseTotal,

                    // Target percentages (ACTUAL vs PLANNED for this quarter)
                    // Column R: नेपाल सरकार - Expense % against planned budget (government)
                    'target_nepal_gov_percent' => $targetNepalGovPercent,
                    // Column S: ने.वि.प्रा. - Expense % against planned budget (NEA)
                    'target_nea_percent' => $targetNeaPercent,
                    // Column T: जम्मा - Total expense % against planned budget
                    'target_total_percent' => $targetTotalPercent,

                    // Additional fields
                    'semi_annual_total_expense' => $project->semi_annual_total_expense ?? 0,
                    'semi_annual_progress_percent' => $project->semi_annual_progress_percent ?? 0,
                    'remarks' => $project->remarks ?? '',
                    'dhar' => $project->dhar ?? '',
                    'weighted_progress_1' => $project->weighted_progress_1 ?? 0,
                    'weighted_progress_2' => $project->weighted_progress_2 ?? 0,
                ];
            })->toArray();

            $parameters['projects'] = $projects;
        }

        // Generate filename
        $filename = 'Progress_Report_Q' .
            $this->getQuarterNumber($parameters['quarter']) . '_' .
            str_replace('/', '_', $parameters['fiscal_year']) . '_' .
            date('Ymd') . '.xlsx';

        return Excel::download(
            new AnnualProgramReportExport($parameters),
            $filename
        );
    }

    private function getQuarterNumber($quarter): string
    {
        $quarters = [
            'प्रथम' => '1',
            'दोस्रो' => '2',
            'तेस्रो' => '3',
            'चौथो' => '4',
            'first' => '1',
            'second' => '2',
            'third' => '3',
            'fourth' => '4',
        ];

        return $quarters[$quarter] ?? '1';
    }
}
