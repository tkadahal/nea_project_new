<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\Reports\PreBudgetMultiSheetExport;
use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use App\Models\PreBudget;
use App\Models\PreBudgetQuarterAllocation;
use App\Models\Project;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class PreBudgetController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        abort_if(Gate::denies('preBudget_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();

        $isSuperAdmin     = $user->hasRole(Role::SUPERADMIN);
        $isAdmin          = $user->hasRole(Role::ADMIN);
        $isDirectorateUser = $user->hasRole(Role::DIRECTORATE_USER);
        $isProjectUser    = !$isSuperAdmin && !$isAdmin && !$isDirectorateUser;

        // ====================== PROJECTS FILTERING ======================
        $projectsQuery = Project::with('directorate');

        if ($isSuperAdmin || $isAdmin) {
            // Can see all projects
        } elseif ($isDirectorateUser && $user->directorate_id) {
            $projectsQuery->where('directorate_id', $user->directorate_id);
        } elseif ($isProjectUser) {
            // Project user can only see projects they are assigned to
            $projectsQuery->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $projects = $projectsQuery->get();

        $projectsForJs = $projects->map(function ($p) {
            return [
                'id'            => $p->id,
                'title'         => $p->title,
                'directorate_id' => $p->directorate_id,
            ];
        })->values();

        // ====================== DIRECTORATES FILTERING ======================
        $directorates = collect();
        if (!$isProjectUser) {
            if ($isSuperAdmin || $isAdmin) {
                $directorates = \App\Models\Directorate::all();
            } elseif ($isDirectorateUser && $user->directorate_id) {
                $directorates = \App\Models\Directorate::where('id', $user->directorate_id)->get();
            }
        }

        // ====================== REQUEST FILTERS ======================
        $directorateId = $request->input('directorate_filter');
        $projectId     = $request->input('project_filter');
        $fiscalYearId  = $request->input('fiscal_year_filter');
        $search        = $request->input('search');
        $perPage       = (int) $request->input('per_page', 20);

        // Force project user to only their project
        if ($isProjectUser) {
            if ($projects->count() === 1) {
                $projectId = $projects->first()->id;   // Auto-select single project
            } else {
                // If multiple projects, but still restrict to their projects
                $allowedProjectIds = $projects->pluck('id');
                if ($projectId && !$allowedProjectIds->contains($projectId)) {
                    $projectId = null; // Invalid project selected → show none or first
                }
            }
        }

        // Default fiscal year
        if (empty($fiscalYearId)) {
            $currentFY = FiscalYear::currentFiscalYear();
            if ($currentFY) $fiscalYearId = $currentFY->id;
        }

        // ====================== MAIN QUERY ======================
        $query = PreBudget::with(['project.directorate', 'fiscalYear']);

        // Apply security filters
        if ($isProjectUser) {
            $query->whereHas('project.users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif ($isDirectorateUser && $user->directorate_id) {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('directorate_id', $user->directorate_id);
            });
        }
        // SuperAdmin & Admin have no extra restriction

        // Apply user-selected filters
        if ($directorateId && !$isProjectUser) {
            $query->whereHas('project', fn($q) => $q->where('directorate_id', $directorateId));
        }

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        if ($fiscalYearId) {
            $query->where('fiscal_year_id', $fiscalYearId);
        }

        if ($search) {
            $query->whereHas('project', fn($q) => $q->where('title', 'like', "%{$search}%"));
        }

        $preBudgets = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();

        // ====================== AJAX RESPONSE ======================
        if ($request->wantsJson() || $request->ajax()) {
            $data = $preBudgets->getCollection()->transform(function ($item) {
                $total = (float)($item->internal_budget ?? 0) +
                    (float)($item->government_share ?? 0) +
                    (float)($item->government_loan ?? 0) +
                    (float)($item->foreign_loan_budget ?? 0) +
                    (float)($item->foreign_subsidy_budget ?? 0) +
                    (float)($item->company_budget ?? 0);

                return [
                    'id'                => $item->id,
                    'project'           => $item->project?->title ?? 'N/A',
                    'directorate'       => $item->project?->directorate?->title ?? 'Unknown',
                    'directorate_id'    => $item->project?->directorate?->id,
                    'internal_budget'   => (float)($item->internal_budget ?? 0),
                    'government_share'  => (float)($item->government_share ?? 0),
                    'government_loan'   => (float)($item->government_loan ?? 0),
                    'foreign_loan'      => (float)($item->foreign_loan_budget ?? 0),
                    'foreign_subsidy'   => (float)($item->foreign_subsidy_budget ?? 0),
                    'company_budget'    => (float)($item->company_budget ?? 0),
                    'total_budget'      => $total,
                ];
            });

            return response()->json([
                'data'         => $data,
                'current_page' => $preBudgets->currentPage(),
                'last_page'    => $preBudgets->lastPage(),
                'total'        => $preBudgets->total(),
            ]);
        }

        // ====================== BLADE VIEW DATA ======================
        $filters = [
            'directorates' => $directorates,
            'projects'     => $projects,
            'fiscalYears'  => FiscalYear::orderByDesc('id')->get(),
        ];

        return view('admin.preBudgets.index', [
            'filters'       => $filters,
            'isProjectUser' => $isProjectUser,
            'projectId'     => $projectId,
            'fiscalYearId'  => $fiscalYearId,
            'projectsForJs' => $projectsForJs,
            'headers'       => [
                'Project',
                'Internal',
                'Gov Share',
                'Gov Loan',
                'Foreign Loan',
                'Foreign Subsidy',
                'Company',
                'Total'
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        abort_if(Gate::denies('preBudget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();

        $projects = $user->projects->map(fn($project) => [
            'id' => $project->id,
            'title' => $project->title,
        ]);

        $fiscalYears = FiscalYear::orderBy('title', 'desc')->pluck('title', 'id');

        $currentFiscalYear = FiscalYear::currentFiscalYear();

        $fields = [
            'government_share' => 'नेपाल सरकार सेयर',
            'government_loan' => 'नेपाल सरकार ऋण',
            'foreign_subsidy_budget' => 'वैदेशिक अनुदान',
            'foreign_loan_budget' => 'वैदेशिक ऋण',
            'internal_budget' => 'ने. वि. प्रा',
            'company_budget' => 'अन्य श्रोत',
        ];

        return view('admin.preBudgets.create', compact('projects', 'fiscalYears', 'currentFiscalYear', 'fields'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        abort_if(Gate::denies('preBudget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();

        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],

            'sources' => ['nullable', 'array'],
            'sources.foreign_loan_budget' => ['nullable', 'string', 'max:1000'],
            'sources.foreign_subsidy_budget' => ['nullable', 'string', 'max:1000'],
            'sources.company_budget' => ['nullable', 'string', 'max:1000'],

            'quarters' => ['required', 'array', 'size:4'],

            'quarters.*.internal_budget' => ['nullable', 'numeric', 'min:0'],
            'quarters.*.government_share' => ['nullable', 'numeric', 'min:0'],
            'quarters.*.government_loan' => ['nullable', 'numeric', 'min:0'],
            'quarters.*.foreign_loan_budget' => ['nullable', 'numeric', 'min:0'],
            'quarters.*.foreign_subsidy_budget' => ['nullable', 'numeric', 'min:0'],
            'quarters.*.company_budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! $user->projects()->where('projects.id', $validated['project_id'])->exists()) {
            abort(403, 'Unauthorized project access.');
        }

        $fiscalYear = FiscalYear::currentFiscalYear();

        DB::transaction(function () use ($validated, $fiscalYear) {

            $preBudget = PreBudget::create([
                'project_id' => $validated['project_id'],
                'fiscal_year_id' => $fiscalYear->id,

                // Yearly sources
                'foreign_loan_source' => $validated['sources']['foreign_loan_budget'] ?? null,
                'foreign_subsidy_source' => $validated['sources']['foreign_subsidy_budget'] ?? null,
                'company_source' => $validated['sources']['company_budget'] ?? null,
            ]);

            foreach ($validated['quarters'] as $quarterNumber => $values) {

                PreBudgetQuarterAllocation::create([
                    'pre_budget_id' => $preBudget->id,
                    'quarter' => (int) $quarterNumber,

                    'internal_budget' => $values['internal_budget'] ?? 0,
                    'government_share' => $values['government_share'] ?? 0,
                    'government_loan' => $values['government_loan'] ?? 0,
                    'foreign_loan_budget' => $values['foreign_loan_budget'] ?? 0,
                    'foreign_subsidy_budget' => $values['foreign_subsidy_budget'] ?? 0,
                    'company_budget' => $values['company_budget'] ?? 0,
                ]);
            }
        });

        return redirect()
            ->route('admin.preBudget.index')
            ->with('success', 'Pre Budget created successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */
    public function show(PreBudget $preBudget)
    {
        abort_if(Gate::denies('preBudget_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $preBudget->load('quarterAllocations');

        return view('admin.preBudgets.show', compact('preBudget'));
    }

    /*
|--------------------------------------------------------------------------
| Edit
|--------------------------------------------------------------------------
*/
    public function edit(PreBudget $preBudget)
    {
        abort_if(Gate::denies('preBudget_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $preBudget->load('quarterAllocations');

        // Fetch fiscal years for the dropdown
        $fiscalYears = FiscalYear::orderBy('title', 'desc')->pluck('title', 'id');

        $fields = [
            'government_share' => 'नेपाल सरकार सेयर',
            'government_loan' => 'नेपाल सरकार ऋण',
            'foreign_subsidy_budget' => 'वैदेशिक अनुदान',
            'foreign_loan_budget' => 'वैदेशिक ऋण',
            'internal_budget' => 'ने. वि. प्रा',
            'company_budget' => 'अन्य श्रोत',
        ];

        return view('admin.preBudgets.edit', compact('preBudget', 'fields', 'fiscalYears'));
    }

    /*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/
    public function update(Request $request, PreBudget $preBudget)
    {
        abort_if(Gate::denies('preBudget_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            // Validate Fiscal Year
            'fiscal_year_id' => [
                'required',
                'exists:fiscal_years,id',
                Rule::unique('pre_budgets')->where(function ($query) use ($preBudget) {
                    return $query->where('project_id', $preBudget->project_id);
                })->ignore($preBudget->id), // Ignore current record so we can save without error if year hasn't changed
            ],

            'sources' => ['nullable', 'array'],
            'sources.foreign_loan_budget' => ['nullable', 'string', 'max:255'],
            'sources.foreign_subsidy_budget' => ['nullable', 'string', 'max:255'],
            'sources.company_budget' => ['nullable', 'string', 'max:255'],

            'quarters' => ['required', 'array', 'size:4'],

            'quarters.*.internal_budget' => ['nullable', 'numeric', 'min:0'],
            'quarters.*.government_share' => ['nullable', 'numeric', 'min:0'],
            'quarters.*.government_loan' => ['nullable', 'numeric', 'min:0'],
            'quarters.*.foreign_loan_budget' => ['nullable', 'numeric', 'min:0'],
            'quarters.*.foreign_subsidy_budget' => ['nullable', 'numeric', 'min:0'],
            'quarters.*.company_budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $preBudget) {

            // Update Fiscal Year and Sources
            $preBudget->update([
                'fiscal_year_id' => $validated['fiscal_year_id'],
                'foreign_loan_source' => $validated['sources']['foreign_loan_budget'] ?? null,
                'foreign_subsidy_source' => $validated['sources']['foreign_subsidy_budget'] ?? null,
                'company_source' => $validated['sources']['company_budget'] ?? null,
            ]);

            foreach ($validated['quarters'] as $quarterNumber => $values) {

                PreBudgetQuarterAllocation::updateOrCreate(
                    [
                        'pre_budget_id' => $preBudget->id,
                        'quarter' => (int) $quarterNumber,
                    ],
                    [
                        'internal_budget' => $values['internal_budget'] ?? 0,
                        'government_share' => $values['government_share'] ?? 0,
                        'government_loan' => $values['government_loan'] ?? 0,
                        'foreign_loan_budget' => $values['foreign_loan_budget'] ?? 0,
                        'foreign_subsidy_budget' => $values['foreign_subsidy_budget'] ?? 0,
                        'company_budget' => $values['company_budget'] ?? 0,
                    ]
                );
            }
        });

        return redirect()
            ->route('admin.preBudget.index')
            ->with('success', 'Pre Budget updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */
    public function destroy(PreBudget $preBudget)
    {
        abort_if(Gate::denies('preBudget_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $preBudget->delete();

        return redirect()
            ->route('admin.preBudget.index')
            ->with('success', 'Pre Budget deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Excel Export
    |--------------------------------------------------------------------------
    */
    public function export()
    {
        return Excel::download(
            new PreBudgetMultiSheetExport,
            'pre_budget_multisheet_report.xlsx'
        );
    }
}
