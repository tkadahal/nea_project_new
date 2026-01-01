<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Normalizer;
use App\Models\Role;
use App\Models\Budget;
use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
use App\Models\Directorate;
use Illuminate\Http\Request;
use App\Imports\BudgetImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BudgetTemplateExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Budget\StoreBudgetRequest;

class BudgetController extends Controller
{
    public function index(): View
    {
        abort_if(Gate::denies('budget_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $budgetQuery = Budget::with(['fiscalYear', 'project'])->latest();

        try {
            $roleIds = $user->roles->pluck('id')->toArray();

            if (!in_array(Role::SUPERADMIN, $roleIds) && !in_array(Role::ADMIN, $roleIds)) {
                if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                    $directorateId = $user->directorate ? [$user->directorate->id] : [];
                    $budgetQuery->whereHas('project', function ($query) use ($directorateId) {
                        $query->whereIn('directorate_id', $directorateId);
                    });
                } elseif (in_array(Role::PROJECT_USER, $roleIds)) {
                    $projectIds = $user->projects()->pluck('projects.id')->toArray();
                    $budgetQuery->whereHas('project', function ($query) use ($projectIds) {
                        $query->whereIn('projects.id', $projectIds);
                    });
                } else {
                    $budgetQuery->where('id', $user->id);
                }
            }
        } catch (\Exception $e) {
            $data['error'] = 'Unable to load users due to an error.';
        }

        $budgets = $budgetQuery->get();

        $headers = [
            trans('global.budget.fields.id'),
            trans('global.budget.fields.fiscal_year_id'),
            trans('global.budget.fields.project_id'),
            trans('global.budget.fields.government_share'),
            trans('global.budget.fields.government_loan'),
            trans('global.budget.fields.foreign_loan_budget'),
            trans('global.budget.fields.foreign_subsidy_budget'),
            trans('global.budget.fields.internal_budget'),
            trans('global.budget.fields.total_budget'),
            trans('global.budget.fields.budget_revision'),
        ];

        $data = $budgets->map(function ($budget) {
            return [
                'id' => $budget->id,
                'project_id' => $budget->project_id,
                'fiscal_year' => $budget->fiscalYear->title,
                'project' => $budget->project->title,
                'government_share' => $budget->government_share,
                'government_loan' => $budget->government_loan,
                'foreign_loan' => $budget->foreign_loan_budget,
                'foreign_subsidy' => $budget->foreign_subsidy_budget,
                'internal_budget' => $budget->internal_budget,
                'total_budget' => $budget->total_budget,
                'budget_revision' => $budget->budget_revision,
            ];
        })->all();

        return view('admin.budgets.index', [
            'headers' => $headers,
            'data' => $data,
            'budgets' => $budgets,
            'routePrefix' => 'admin.budget',
            'actions' => ['view', 'edit', 'delete', 'quarterly'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this project budget?',
        ]);
    }

    private function getUserProjects($directorateId = null)
    {
        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();

        $query = Project::query()->select('id', 'title', 'directorate_id');

        if (in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds)) {
            // Can see all + filter freely
        } elseif (in_array(Role::DIRECTORATE_USER, $roleIds) && $user->directorate_id) {
            $query->where('directorate_id', $user->directorate_id);
            // Cannot override filter
        } elseif (in_array(Role::PROJECT_USER, $roleIds)) {
            $projectIds = $user->projects()->pluck('projects.id')->toArray();
            if (empty($projectIds)) {
                return collect();
            }
            $query->whereIn('id', $projectIds);
        } else {
            return collect();
        }

        // Apply directorate filter ONLY for users who can see multiple directorates
        if (
            (in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds)) &&
            $directorateId && $directorateId !== '' && $directorateId !== '0'
        ) {
            $query->where('directorate_id', $directorateId);
        }

        // Changed from orderBy('title') to orderBy('id', 'asc')
        return $query->orderBy('id', 'asc')->get();
    }

    public function create(Request $request): View
    {
        abort_if(Gate::denies('budget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();

        $projects = $this->getUserProjects();

        $directorateTitle = 'All Directorates';
        if (in_array(Role::DIRECTORATE_USER, $roleIds) && $user->directorate_id) {
            $directorateTitle = $user->directorate->title ?? 'Unknown Directorate';
        }

        $projectId = $request->query('project_id');
        if ($projectId) {
            Session::put('project_id', $projectId);
        }

        $fiscalYears = FiscalYear::getFiscalYearOptions();

        $directorates = [];
        if (!in_array(Role::DIRECTORATE_USER, $roleIds) || !$user->directorate_id) {
            $directorates = Directorate::orderBy('title')->get()->map(function ($d) use ($request) {
                return [
                    'value' => $d->id,
                    'label' => $d->title,
                    'selected' => $request->input('directorate_id') == $d->id,
                ];
            })->prepend([
                'value' => '',
                'label' => trans('global.all_directorates') ?? 'All Directorates',
                'selected' => !$request->input('directorate_id'),
            ])->toArray();
        }

        return view('admin.budgets.create', compact(
            'projects',
            'fiscalYears',
            'projectId',
            'directorateTitle',
            'directorates'
        ));
    }

    public function filterProjects(Request $request)
    {
        $directorateId = $request->input('directorate_id');
        $fiscalYearId = $request->input('fiscal_year_id');
        $user = Auth::user();

        Log::info('=== filterProjects called ===', [
            'user_id' => $user->id,
            'user_roles' => $user->roles->pluck('title')->toArray(),
            'directorate_id_requested' => $directorateId,
            'fiscal_year_id' => $fiscalYearId,
        ]);

        $projects = $this->getUserProjects($directorateId);

        Log::info('Projects query result', [
            'count' => $projects->count(),
            'project_ids' => $projects->pluck('id')->toArray(),
            'project_titles' => $projects->pluck('title')->toArray(),
        ]);

        return view('admin.budgets.partials.project-table', compact('projects'));
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('budget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validatedData = $request->validated();
        $fiscalYearId = $validatedData['fiscal_year_id'];
        $projectIds = $validatedData['project_id'] ?? [];

        $createdBudgets = 0;
        $updatedBudgets = 0;
        $errors = [];

        foreach ($projectIds as $projectId) {
            $budgetData = [
                'fiscal_year_id' => $fiscalYearId,
                'internal_budget' => $validatedData['internal_budget'][$projectId] ?? 0,
                'government_share' => $validatedData['government_share'][$projectId] ?? 0,
                'government_loan' => $validatedData['government_loan'][$projectId] ?? 0,
                'foreign_loan_budget' => $validatedData['foreign_loan_budget'][$projectId] ?? 0,
                'foreign_loan_source' => $validatedData['foreign_loan_source'][$projectId] ?? 0,
                'foreign_subsidy_budget' => $validatedData['foreign_subsidy_budget'][$projectId] ?? 0,
                'foreign_subsidy_source' => $validatedData['foreign_subsidy_source'][$projectId] ?? 0,
                'total_budget' => $validatedData['total_budget'][$projectId] ?? 0,
                'decision_date' => $validatedData['decision_date'] ?? null,
                'remarks' => $validatedData['remarks'] ?? null,
            ];

            if (array_sum(array_slice($budgetData, 1, 5)) == 0) {
                continue;
            }

            $project = Project::find($projectId);
            if (!$project) {
                $errors[] = "Project ID {$projectId} not found.";
                continue;
            }

            $existingBudget = Budget::where('project_id', $projectId)
                ->where('fiscal_year_id', $fiscalYearId)
                ->first();

            if ($existingBudget) {
                $existingBudget->update([
                    'budget_revision' => $existingBudget->budget_revision + 1,
                    'internal_budget' => $existingBudget->internal_budget + $budgetData['internal_budget'],
                    'foreign_loan_budget' => $existingBudget->foreign_loan_budget + $budgetData['foreign_loan_budget'],
                    'foreign_loan_source' => $budgetData['foreign_loan_source'],
                    'foreign_subsidy_budget' => $existingBudget->foreign_subsidy_budget + $budgetData['foreign_subsidy_budget'],
                    'foreign_subsidy_source' => $budgetData['foreign_subsidy_source'],
                    'government_loan' => $existingBudget->government_loan + $budgetData['government_loan'],
                    'government_share' => $existingBudget->government_share + $budgetData['government_share'],
                    'total_budget' => $existingBudget->total_budget + $budgetData['total_budget'],
                ]);

                $revision = $existingBudget->revisions()->create($budgetData);
                $updatedBudgets++;
            } else {
                $budget = $project->budgets()->create(array_merge([
                    'budget_revision' => 1,
                ], $budgetData));

                $revision = $budget->revisions()->create($budgetData);
                $createdBudgets++;
            }
        }

        Session::forget('project_id');

        $message = '';
        if ($createdBudgets > 0) {
            $message .= "Created budgets for {$createdBudgets} project(s). ";
        }
        if ($updatedBudgets > 0) {
            $message .= "Updated budgets for {$updatedBudgets} project(s). ";
        }
        if (empty($message)) {
            $message = 'No budgets were created or updated. Ensure at least one project has non-zero budget values.';
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        return redirect()->route('admin.budget.index')->with('success', $message);
    }

    public function downloadTemplate(Request $request)
    {
        abort_if(Gate::denies('budget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $directorateId = $request->query('directorate_id');

        $projects = $this->getUserProjects($directorateId);

        $directorateTitle = 'All Directorates';
        if ($directorateId) {
            $directorate = Directorate::find($directorateId);
            $directorateTitle = $directorate?->title ?? 'Selected Directorate';
        } elseif (Auth::user()->directorate) {
            $directorateTitle = Auth::user()->directorate->title ?? 'My Directorate';
        }

        $filename = 'budget_template';
        if ($directorateTitle !== 'All Directorates') {
            $filename .= '_' . \Illuminate\Support\Str::slug($directorateTitle);
        }
        $filename .= '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new BudgetTemplateExport($projects, $directorateTitle),
            $filename
        );
    }

    public function uploadIndex(): View
    {
        abort_if(Gate::denies('budget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.budgets.upload');
    }

    public function upload(Request $request): RedirectResponse
    {
        Log::info('Upload method started', [
            'hasFile' => $request->hasFile('excel_file'),
            'files' => $request->hasFile('excel_file') ? [
                'name' => $request->file('excel_file')->getClientOriginalName(),
                'size' => $request->file('excel_file')->getSize(),
                'mime' => $request->file('excel_file')->getMimeType(),
            ] : 'No file uploaded',
        ]);

        abort_if(Gate::denies('budget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (!$request->hasFile('excel_file')) {
            return redirect()->back()->withErrors(['excel_file' => 'No file was uploaded.'])->withInput();
        }

        $file = $request->file('excel_file');
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);

        try {
            if (!$file->isValid()) {
                throw new \Exception('File upload failed: ' . $file->getErrorMessage());
            }

            // Extract fiscal year from row 2 of the Excel file
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $fiscalYearRow = $worksheet->getCell('A2')->getValue();

            // Extract fiscal year from "Fiscal Year: 2082/83" format
            preg_match('/Fiscal Year:\s*(.+)/', $fiscalYearRow, $matches);
            $fiscalYearTitle = isset($matches[1]) ? trim($matches[1]) : null;

            if (!$fiscalYearTitle) {
                return redirect()->back()->withErrors([
                    'excel_file' => 'Could not extract fiscal year from the template. Expected format: "Fiscal Year: YYYY/YY" in row 2.'
                ])->withInput();
            }

            Log::info('Extracted fiscal year from template', ['fiscal_year' => $fiscalYearTitle]);

            // Now import the data
            $import = new BudgetImport();
            $data = $import->import($file);

            if ($data->isEmpty()) {
                return redirect()->back()->withErrors([
                    'excel_file' => 'No valid data found in the Excel file.'
                ])->withInput();
            }

            // Get fiscal year ID
            $fiscalYear = FiscalYear::where('title', $fiscalYearTitle)->first();

            if (!$fiscalYear) {
                return redirect()->back()->withErrors([
                    'excel_file' => "Fiscal year '{$fiscalYearTitle}' not found in the system. Please ensure it exists before uploading."
                ])->withInput();
            }

            $fiscalYearId = $fiscalYear->id;

            // Get all projects with normalized titles
            $projects = Project::all()->mapWithKeys(function ($project) {
                $title = normalizer_normalize(trim($project->title), Normalizer::FORM_C);
                $title = preg_replace('/\s+/', ' ', $title);
                return [$title => $project->id];
            })->toArray();

            $budgetData = [];
            $errors = [];

            foreach ($data as $index => $row) {
                $projectTitle = trim($row['project_title'] ?? '');

                if (empty($projectTitle)) {
                    $errors[] = "Missing project title at row " . ($index + 4); // +4 because data starts at row 4
                    continue;
                }

                $normalizedProjectTitle = normalizer_normalize($projectTitle, Normalizer::FORM_C);
                $normalizedProjectTitle = preg_replace('/\s+/', ' ', $normalizedProjectTitle);

                $projectId = $projects[$normalizedProjectTitle] ?? null;

                if (!$projectId) {
                    $errors[] = "Invalid project '{$projectTitle}' at row " . ($index + 4);
                    continue;
                }

                $budgetData[] = [
                    'fiscal_year_id' => $fiscalYearId, // Use the fiscal year from row 2
                    'project_id' => $projectId,
                    'government_loan' => floatval($row['government_loan'] ?? 0),
                    'government_share' => floatval($row['government_share'] ?? 0),
                    'foreign_loan_budget' => floatval($row['foreign_loan_budget'] ?? 0),
                    'foreign_loan_source' => trim($row['foreign_loan_source'] ?? ''),
                    'foreign_subsidy_budget' => floatval($row['foreign_subsidy_budget'] ?? 0),
                    'foreign_subsidy_source' => trim($row['foreign_subsidy_source'] ?? ''),
                    'internal_budget' => floatval($row['internal_budget'] ?? 0),
                    'total_budget' => floatval($row['total_budget'] ?? 0),
                ];
            }

            if (!empty($errors)) {
                return redirect()->back()->withErrors($errors)->withInput();
            }

            if (empty($budgetData)) {
                return redirect()->back()->withErrors([
                    'excel_file' => 'No valid budget data found after validation.'
                ])->withInput();
            }

            $createdBudgets = 0;
            $updatedBudgets = 0;
            $skippedBudgets = 0;

            foreach ($budgetData as $data) {
                // Check if any numeric budget field is > 0
                $numericSum = $data['government_loan'] +
                    $data['government_share'] +
                    $data['foreign_loan_budget'] +
                    $data['foreign_subsidy_budget'] +
                    $data['internal_budget'];

                if ($numericSum == 0) {
                    $skippedBudgets++;
                    continue;
                }

                $existingBudget = Budget::where('project_id', $data['project_id'])
                    ->where('fiscal_year_id', $data['fiscal_year_id'])
                    ->first();

                if ($existingBudget) {
                    $existingBudget->update([
                        'budget_revision' => $existingBudget->budget_revision + 1,
                        'internal_budget' => $existingBudget->internal_budget + $data['internal_budget'],
                        'foreign_loan_budget' => $existingBudget->foreign_loan_budget + $data['foreign_loan_budget'],
                        'foreign_subsidy_budget' => $existingBudget->foreign_subsidy_budget + $data['foreign_subsidy_budget'],
                        'foreign_loan_source' => $data['foreign_loan_source'],
                        'foreign_subsidy_source' => $data['foreign_subsidy_source'],
                        'government_loan' => $existingBudget->government_loan + $data['government_loan'],
                        'government_share' => $existingBudget->government_share + $data['government_share'],
                        'total_budget' => $existingBudget->total_budget + $data['total_budget'],
                    ]);
                    $existingBudget->revisions()->create($data);
                    $updatedBudgets++;
                } else {
                    $budget = Budget::create(array_merge([
                        'budget_revision' => 1,
                    ], $data));
                    $budget->revisions()->create($data);
                    $createdBudgets++;
                }
            }

            $message = "Import completed for fiscal year '{$fiscalYearTitle}'. ";
            if ($createdBudgets > 0) $message .= "Created budgets for {$createdBudgets} project(s). ";
            if ($updatedBudgets > 0) $message .= "Updated budgets for {$updatedBudgets} project(s). ";
            if ($skippedBudgets > 0) $message .= "Skipped {$skippedBudgets} project(s) with zero budget. ";

            return redirect()->route('admin.budget.index')->with('success', $message);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            return redirect()->back()->withErrors(['excel_file' => 'Validation failed: ' . $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            Log::error('Budget upload error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors(['excel_file' => 'Error: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Budget $budget): View
    {
        abort_if(Gate::denies('budget_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $budget->load(['project', 'fiscalYear', 'revisions']);

        return view('admin.budgets.show', [
            'budget' => $budget,
            'revisions' => $budget->revisions()->latest()->get(),
        ]);
    }

    public function edit(Budget $budget)
    {
        //
    }

    public function update(Request $request, Budget $budget)
    {
        //
    }

    public function destroy(Budget $budget)
    {
        //
    }

    public function remaining(Budget $budget)
    {
        abort_if(Gate::denies('budget_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $budget->load('project', 'fiscalYear');
        return view('admin.budgets.remaining', compact('budget'));
    }

    // 1️⃣ List duplicates
    public function listDuplicates()
    {
        $budgets = Budget::withCount('revisions')
            ->has('revisions', '>', 1)
            ->with('project:id,title')
            ->get();

        return view('admin.budgets.duplicates', compact('budgets'));
    }

    // 2️⃣ Clean duplicates (keep latest revision)
    public function cleanDuplicates()
    {
        DB::beginTransaction();
        $deletedCount = 0;

        try {
            $budgets = Budget::has('revisions', '>', 1)->get();

            foreach ($budgets as $budget) {
                $revisions = $budget->revisions()->orderBy('created_at')->get();

                if ($revisions->count() > 1) {
                    // Keep only the last revision (latest one)
                    $toKeep = $revisions->last();
                    $toDelete = $revisions->slice(0, -1);

                    foreach ($toDelete as $rev) {
                        $rev->delete();
                        $deletedCount++;
                    }

                    // 🧩 Optional: Update main budget values with latest revision data
                    $budget->update([
                        'total_budget'           => $toKeep->total_budget ?? $budget->total_budget,
                        'internal_budget'        => $toKeep->internal_budget ?? $budget->internal_budget,
                        'government_share'       => $toKeep->government_share ?? $budget->government_share,
                        'government_loan'        => $toKeep->government_loan ?? $budget->government_loan,
                        'foreign_loan_budget'    => $toKeep->foreign_loan_budget ?? $budget->foreign_loan_budget,
                        'foreign_subsidy_budget' => $toKeep->foreign_subsidy_budget ?? $budget->foreign_subsidy_budget,
                        'budget_revision'        => 1,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.budget.duplicates')
                ->with('success', "✅ Cleaned $deletedCount duplicate revision(s) and synced latest data.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->route('admin.budget.duplicates')
                ->with('error', '❌ Cleanup failed: ' . $e->getMessage());
        }
    }
}
