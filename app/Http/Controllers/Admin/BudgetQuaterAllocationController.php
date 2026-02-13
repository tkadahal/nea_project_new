<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Budget;
use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
use App\Models\Directorate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\RedirectResponse;
use App\Models\BudgetQuaterAllocation;
use App\Imports\AdminQuarterBudgetTemplateImport;
use App\Http\Requests\BudgetQuaterAllocation\StoreBudgetQuaterAllocationRequest;

class BudgetQuaterAllocationController extends Controller
{
    public function index()
    {
        //
    }

    public function create(): View | RedirectResponse
    {
        $user = Auth::user();
        $projects = $user->projects;

        $budgetId = request()->get('budget_id');

        $budget = Budget::with(['project', 'fiscalYear'])
            ->where('id', $budgetId)
            ->first();

        if (!$budgetId) {
            return redirect()->back()->with('error', 'Budget ID is required.');
        }

        $projectName   = $budget->project->title ?? 'N/A';
        $fiscalYear    = $budget->fiscalYear->title ?? 'N/A';

        $allocations = BudgetQuaterAllocation::where('budget_id', $budget->id)->get();

        $allocationMap = $allocations->groupBy('budget_id')
            ->map(fn($group) => $group->keyBy('quarter'));

        $budgetData = $this->prepareBudgetData(collect([$budget]), $allocationMap);

        return view('admin.budgetQuaterAllocations.create', compact(
            'budgetData',
            'projectName',
            'fiscalYear',
        ));
    }

    public function loadBudgets(Request $request): \Illuminate\Http\JsonResponse
    {
        $projectId = $request->input('project_id');
        $fiscalYearId = $request->input('fiscal_year_id');

        if (!$projectId || !$fiscalYearId) {
            return response()->json(['budgetData' => [], 'projectName' => '', 'fiscalYearTitle' => ''], 400);
        }

        $project = Project::find($projectId);
        $fiscalYear = FiscalYear::find($fiscalYearId);

        if (!$project || !$fiscalYear) {
            return response()->json(['budgetData' => [], 'projectName' => '', 'fiscalYearTitle' => ''], 404);
        }

        $budgets = Budget::where('project_id', $projectId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->get();

        $allocations = BudgetQuaterAllocation::whereIn('budget_id', $budgets->pluck('id'))->get();
        $allocationMap = $allocations->groupBy('budget_id')->map(fn($group) => $group->keyBy('quarter'));

        $budgetData = $this->prepareBudgetData($budgets, $allocationMap);

        return response()->json([
            'budgetData' => $budgetData,
            'projectName' => $project->title,
            'fiscalYearTitle' => $fiscalYear->title,
        ]);
    }

    private function prepareBudgetData($budgets, $allocationMap = [])
    {
        $budgetData = [];
        $counter = 1;

        $fieldMap = [
            'internal_budget' => 'Internal Budget',
            'government_share' => 'Government Share',
            'government_loan' => 'Government Loan',
            'foreign_loan_budget' => 'Foreign Loan',
            'foreign_subsidy_budget' => 'Foreign Subsidy',
            'total_budget' => 'Total Budget',
        ];

        $storageFieldMap = [
            'total_budget' => 'total_budget',
            'internal_budget' => 'internal_budget',
            'government_share' => 'government_share',
            'government_loan' => 'government_loan',
            'foreign_loan_budget' => 'foreign_loan',
            'foreign_subsidy_budget' => 'foreign_subsidy',
        ];

        foreach ($budgets as $budget) {
            foreach ($fieldMap as $budgetField => $title) {
                $amount = $budget->{$budgetField} ?? 0;
                $storageField = $storageFieldMap[$budgetField];

                $q1 = 0;
                $q2 = 0;
                $q3 = 0;
                $q4 = 0;

                if (isset($allocationMap[$budget->id])) {
                    foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $quarter) {
                        $qIndex = substr($quarter, 1);
                        if (isset($allocationMap[$budget->id][$quarter])) {
                            $allocation = $allocationMap[$budget->id][$quarter];
                            ${'q' . $qIndex} = $allocation->{$storageField} ?? 0;
                        }
                    }
                }

                $budgetData[] = [
                    'sn' => $counter++,
                    'title' => $title,
                    'amount' => $amount,
                    'budget_id' => $budget->id,
                    'field' => $storageField,
                    'q1' => $q1,
                    'q2' => $q2,
                    'q3' => $q3,
                    'q4' => $q4,
                ];
            }
        }

        return $budgetData;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBudgetQuaterAllocationRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            DB::beginTransaction();

            $budgetIds = $validated['budget_ids'];
            $fields = $validated['fields'];
            $q1Allocations = $validated['q1_allocations'];
            $q2Allocations = $validated['q2_allocations'];
            $q3Allocations = $validated['q3_allocations'];
            $q4Allocations = $validated['q4_allocations'];

            $allocationData = [];
            $index = 0;
            foreach ($budgetIds as $budgetId) {
                $field = $fields[$index];
                $q1 = (float) ($q1Allocations[$index] ?? 0);
                $q2 = (float) ($q2Allocations[$index] ?? 0);
                $q3 = (float) ($q3Allocations[$index] ?? 0);
                $q4 = (float) ($q4Allocations[$index] ?? 0);

                $quartersData = [
                    'Q1' => $q1,
                    'Q2' => $q2,
                    'Q3' => $q3,
                    'Q4' => $q4,
                ];

                foreach ($quartersData as $quarter => $alloc) {
                    if (!isset($allocationData[$budgetId][$quarter])) {
                        $allocationData[$budgetId][$quarter] = [
                            'budget_id' => $budgetId,
                            'quarter' => $quarter,
                            'internal_budget' => 0.00,
                            'government_share' => 0.00,
                            'government_loan' => 0.00,
                            'foreign_loan' => 0.00,
                            'foreign_subsidy' => 0.00,
                            'total_budget' => 0.00,
                        ];
                    }

                    if ($field === 'total_budget') {
                        $allocationData[$budgetId][$quarter]['total_budget'] = $alloc;
                    } else {
                        $allocationData[$budgetId][$quarter][$field] = $alloc;
                    }
                }

                $index++;
            }

            foreach ($allocationData as $budgetId => $quarters) {
                foreach ($quarters as $quarter => $data) {
                    BudgetQuaterAllocation::updateOrCreate(
                        ['budget_id' => $budgetId, 'quarter' => $quarter],
                        $data
                    );
                }
            }

            DB::commit();

            return redirect()->route('admin.budgetQuaterAllocation.index')
                ->with('success', 'Quaterly budget allocations created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing budget quarter allocation: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to create quarterly budget allocations. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(BudgetQuaterAllocation $budgetQuaterAllocation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BudgetQuaterAllocation $budgetQuaterAllocation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BudgetQuaterAllocation $budgetQuaterAllocation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BudgetQuaterAllocation $budgetQuaterAllocation)
    {
        //
    }

    public function downloadTemplate(Request $request)
    {
        if (!Auth::user()->hasRole(['Super_Admin', 'admin'])) {
            abort(403);
        }

        $fiscalYearId = $request->query('fiscal_year_id');
        $directorateId = $request->query('directorate_id');

        if (!$fiscalYearId) {
            abort(400, 'Fiscal year is required');
        }

        $query = \App\Models\Project::query();

        if ($directorateId && $directorateId !== '') {
            $query->where('directorate_id', $directorateId);
        }
        // Optional: if you want to respect user project access, uncomment below
        // $query->whereIn('id', Auth::user()->projects()->pluck('projects.id'));

        $projects = $query->orderBy('id')->get();

        $directorateTitle = 'All Directorates';
        if ($directorateId && $directorateId !== '') {
            $directorate = \App\Models\Directorate::find($directorateId);
            $directorateTitle = $directorate?->title ?? 'Selected Directorate';
        }

        $fiscalYear = \App\Models\FiscalYear::find($fiscalYearId);
        $fiscalYearTitle = $fiscalYear?->title ?? 'Fiscal Year ' . $fiscalYearId;

        $safeTitle = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $directorateTitle);
        $filename = 'quarterly_allocation_template';
        if ($directorateTitle !== 'All Directorates') {
            $filename .= '_' . \Illuminate\Support\Str::slug($safeTitle, '_');
        }
        $filename .= '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new \App\Exports\Templates\AdminQuarterBudgetTemplateExport($projects, $directorateTitle, $fiscalYearTitle),
            $filename
        );
    }

    /**
     * Show the upload template form
     */
    public function uploadIndex(): View
    {

        $fiscalYears = FiscalYear::getFiscalYearOptions();
        $directorates = Directorate::orderBy('title')->get();

        return view('admin.budgetQuaterAllocations.upload-template', compact(
            'fiscalYears',
            'directorates'
        ));
    }

    public function uploadTemplate(Request $request): RedirectResponse
    {
        $request->validate([
            'template' => 'required|mimes:xlsx,xls|file|max:10240',
        ]);

        try {
            DB::beginTransaction();

            Excel::import(
                new AdminQuarterBudgetTemplateImport($request->file('template')),
                $request->file('template')
            );

            DB::commit();

            return redirect()->back()
                ->with('success', 'Quarterly budget allocations have been successfully imported and updated.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            DB::rollBack();

            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = "Row {$failure->row()}: {$failure->attribute()} - " . implode(', ', $failure->errors());
            }

            return redirect()->back()
                ->with('error', 'Import failed due to validation errors:')
                ->with('import_errors', $errors)
                ->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Quarterly template import failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace'   => $e->getTraceAsString()
            ]);

            $message = $e->getMessage();

            if (
                str_contains($message, 'skipped because they already have allocations') ||
                str_contains($message, 'Quarterly budget imported successfully')
            ) {
                return redirect()->back()
                    ->with('success', 'Quarterly budget import completed.')
                    ->with('warning', $message);
            }

            return redirect()->back()
                ->with('error', $e->getMessage() ?: 'Failed to import template. Please ensure the file matches the downloaded template format.')
                ->withInput();
        }
    }
}
