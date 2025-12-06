<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Normalizer;
use App\Models\Role;
use App\Models\Budget;
use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
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

    private function getUserProjects()
    {
        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();

        if (in_array(Role::SUPERADMIN, $roleIds)) {
            return Project::select('id', 'title')->get();
        } elseif (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            if ($user->directorate_id) {
                return Project::where('directorate_id', $user->directorate_id)
                    ->select('id', 'title')
                    ->get();
            }
        }

        return collect();
    }

    public function create(Request $request): View
    {
        abort_if(Gate::denies('budget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();

        $projects = $this->getUserProjects();
        $directorateTitle = 'All Directorates';

        if (in_array(Role::DIRECTORATE_USER, $roleIds) && Auth::user()->directorate_id) {
            $directorateTitle = Auth::user()->directorate->title ?? 'Unknown Directorate';
        }

        $projectId = $request->query('project_id');

        if (!$projectId) {
            Session::forget('project_id');
        } else {
            Session::put('project_id', $projectId);
        }

        $fiscalYears = FiscalYear::getFiscalYearOptions();

        return view('admin.budgets.create', compact('projects', 'fiscalYears', 'projectId', 'directorateTitle'));
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
                'foreign_subsidy_budget' => $validatedData['foreign_subsidy_budget'][$projectId] ?? 0,
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
                    'foreign_subsidy_budget' => $existingBudget->foreign_subsidy_budget + $budgetData['foreign_subsidy_budget'],
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

    public function downloadTemplate()
    {
        abort_if(Gate::denies('budget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $projects = $this->getUserProjects();

        return Excel::download(new BudgetTemplateExport($projects), 'budget_template.xlsx');
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
            Log::error('No file uploaded');
            return redirect()->back()->withErrors(['excel_file' => 'No file was uploaded.'])->withInput();
        }

        $file = $request->file('excel_file');
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);

        try {
            if (!$file->isValid()) {
                Log::error('Invalid file uploaded', ['error' => $file->getErrorMessage()]);
                throw new \Exception('File upload failed: ' . $file->getErrorMessage());
            }

            $import = new BudgetImport();
            $data = $import->import($file);
            Log::info('Excel data parsed', ['row_count' => count($data)]);

            if ($data->isEmpty()) {
                Log::warning('No valid data found in Excel file');
                return redirect()->back()->withErrors([
                    'excel_file' => 'No valid data found in the Excel file. Ensure the Fiscal Year and Project Title columns are filled with valid values.'
                ])->withInput();
            }

            $fiscalYears = FiscalYear::pluck('id', 'title')->toArray();
            $projects = Project::all()->mapWithKeys(function ($project) {
                $title = normalizer_normalize(trim($project->title), Normalizer::FORM_C);
                $title = preg_replace('/\s+/', ' ', $title);
                return [$title => $project->id];
            })->toArray();

            Log::info('Available fiscal years', ['titles' => array_keys($fiscalYears)]);
            Log::info('Available projects', ['titles' => array_keys($projects)]);

            $budgetData = [];
            $errors = [];

            foreach ($data as $index => $row) {
                $fiscalYearTitle = trim($row['fiscal_year'] ?? '');
                $projectTitle = trim($row['project_title'] ?? '');

                if (
                    strpos($fiscalYearTitle, 'Instructions:') === 0 ||
                    strpos($fiscalYearTitle, '- Fiscal Year is pre-filled') === 0 ||
                    strpos($fiscalYearTitle, '- Enter budget amounts') === 0 ||
                    strpos($fiscalYearTitle, '- Total Budget will auto-calculate') === 0
                ) {
                    Log::info('Skipping instruction row', ['row_number' => $index + 2, 'row' => $row]);
                    continue;
                }

                Log::info('Processing row', [
                    'row_number' => $index + 2,
                    'fiscal_year' => $fiscalYearTitle,
                    'project' => $projectTitle,
                    'total_budget' => $row['total_budget'],
                    'raw_row' => $row,
                ]);

                if (empty($fiscalYearTitle)) {
                    $errors[] = "Missing or invalid fiscal year at row " . ($index + 2);
                    continue;
                }

                if (empty($projectTitle)) {
                    $errors[] = "Missing project title at row " . ($index + 2);
                    continue;
                }

                $normalizedProjectTitle = normalizer_normalize($projectTitle, Normalizer::FORM_C);
                $normalizedProjectTitle = preg_replace('/\s+/', ' ', $normalizedProjectTitle);

                $fiscalYearId = $fiscalYears[$fiscalYearTitle] ?? null;
                $projectId = $projects[$normalizedProjectTitle] ?? null;

                if (!$fiscalYearId) {
                    $errors[] = "Invalid fiscal year: '{$fiscalYearTitle}' at row " . ($index + 2);
                    continue;
                }

                if (!$projectId) {
                    $errors[] = "Invalid project: '{$projectTitle}' at row " . ($index + 2);
                    continue;
                }

                $budgetData[] = [
                    'fiscal_year_id' => $fiscalYearId,
                    'project_id' => $projectId,
                    'government_loan' => floatval($row['government_loan'] ?? 0),
                    'government_share' => floatval($row['government_share'] ?? 0),
                    'foreign_loan_budget' => floatval($row['foreign_loan_budget'] ?? 0),
                    'foreign_subsidy_budget' => floatval($row['foreign_subsidy_budget'] ?? 0),
                    'internal_budget' => floatval($row['internal_budget'] ?? 0),
                    'total_budget' => floatval($row['total_budget'] ?? 0),
                ];
            }

            if (!empty($errors)) {
                Log::warning('Validation errors in Excel data', ['errors' => $errors]);
                return redirect()->back()->withErrors($errors)->withInput();
            }

            if (empty($budgetData)) {
                Log::warning('No valid budget data to process');
                return redirect()->back()->withErrors([
                    'excel_file' => 'No valid budget data found. Ensure at least one row has non-zero budget values and valid fiscal year and project title.'
                ])->withInput();
            }

            $createdBudgets = 0;
            $updatedBudgets = 0;

            foreach ($budgetData as $data) {
                if (array_sum(array_slice($data, 2, 5)) == 0) {
                    Log::info('Skipping row with zero budget values', ['data' => $data]);
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
                        'government_loan' => $existingBudget->government_loan + $data['government_loan'],
                        'government_share' => $existingBudget->government_share + $data['government_share'],
                        'total_budget' => $existingBudget->total_budget + $data['total_budget'],
                    ]);
                    $revision = $existingBudget->revisions()->create($data);
                    $updatedBudgets++;
                    Log::info('Updated budget', ['project_id' => $data['project_id'], 'fiscal_year_id' => $data['fiscal_year_id'], 'total_budget' => $data['total_budget']]);
                } else {
                    $budget = Budget::create(array_merge([
                        'budget_revision' => 1,
                        'project_id' => $data['project_id'],
                    ], $data));
                    $revision = $budget->revisions()->create($data);
                    $createdBudgets++;
                    Log::info('Created budget', ['project_id' => $data['project_id'], 'fiscal_year_id' => $data['fiscal_year_id'], 'total_budget' => $data['total_budget']]);
                }
            }

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

            Log::info('Upload completed', ['message' => $message]);
            return redirect()->route('admin.budget.index')->with('success', $message);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            Log::error('Excel validation error', ['errors' => $e->errors(), 'message' => $e->getMessage()]);
            return redirect()->back()->withErrors(['excel_file' => 'Excel file validation failed: ' . $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            Log::error('Upload exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withErrors(['excel_file' => 'Error processing Excel file: ' . $e->getMessage()])->withInput();
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
