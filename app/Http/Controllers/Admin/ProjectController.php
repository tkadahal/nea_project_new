<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use App\Models\Status;
use App\Models\Project;
use App\Models\Priority;
use Illuminate\View\View;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\Directorate;
use App\Models\BudgetHeading;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Project\StoreProjectRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Http\Requests\Project\UpdateProjectRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ProjectController extends Controller
{
    public function index(): View
    {
        abort_if(Gate::denies('project_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $projectQuery = Project::with(
            [
                'directorate',
                'priority',
                'projectManager',
                'status',
                'budgets',
                'comments',
                'budgetHeading'
            ]
        )
            ->withCount('comments')
            ->orderBy('id', 'desc');

        try {
            $roleIds = $user->roles->pluck('id')->toArray();

            if (!in_array(Role::SUPERADMIN, $roleIds) && !in_array(Role::ADMIN, $roleIds)) {
                if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                    if ($user->directorate_id) {
                        $projectQuery->where('directorate_id', $user->directorate_id);
                    } else {
                        $projectQuery->where('id', 0);
                    }
                } else {
                    $projectQuery->whereHas('users', function ($query) use ($user) {
                        $query->where('users.id', $user->id);
                    });
                }
            }
        } catch (\Exception $e) {
            $data['error'] = 'Unable to load projects due to an error.';
        }

        $projects = $projectQuery->get();

        $directorateColors = config('colors.directorate');
        $priorityColors = config('colors.priority');
        $progressColor = config('colors.progress');
        $budgetColor = config('colors.budget');
        $budgetHeadingColors = config('colors.budget_heading');

        $tableData = $projects->map(function ($project) use ($directorateColors, $priorityColors, $progressColor, $budgetColor) {
            $directorateTitle = $project->directorate?->title ?? 'N/A';
            $directorateId = $project->directorate?->id ?? null;
            $directorateDisplayColor = isset($directorateColors[$directorateId]) ? $directorateColors[$directorateId] : 'gray';

            $priorityValue = $project->priority?->title ?? 'N/A';
            $priorityDisplayColor = isset($priorityColors[$priorityValue]) ? $priorityColors[$priorityValue] : '#6B7280';

            $fieldsForTable = [];
            $fieldsForTable[] = ['title' => trans('global.project.fields.start_date') . ': ' . ($project->start_date?->format('Y-m-d') ?? 'N/A'), 'color' => 'gray'];
            $fieldsForTable[] = ['title' => trans('global.project.fields.end_date') . ': ' . ($project->end_date?->format('Y-m-d') ?? 'N/A'), 'color' => 'gray'];
            $fieldsForTable[] = ['title' => trans('global.project.fields.latest_budget') . ': ' . (is_numeric($project->budget) ? number_format((float) $project->budget, 2) : 'N/A'), 'color' => $budgetColor];
            $fieldsForTable[] = ['title' => trans('global.project.fields.priority_id') . ': ' . $priorityValue, 'color' => $priorityDisplayColor];
            $fieldsForTable[] = ['title' => trans('global.project.fields.physical_progress') . ': ' . (is_numeric($project->progress) ? $project->progress . '%' : 'N/A'), 'color' => $progressColor];
            $fieldsForTable[] = ['title' => trans('global.project.fields.project_manager') . ': ' . ($project->projectManager->name ?? 'N/A'), 'color' => 'gray'];

            return [
                'id' => $project->id,
                'title' => $project->title,
                'directorate' => [['title' => $directorateTitle, 'color' => $directorateDisplayColor]],
                'fields' => $fieldsForTable,
            ];
        })->all();

        $cardData = $projects->map(function ($project) use ($priorityColors, $budgetHeadingColors) {
            $directorateTitle = $project->directorate?->title ?? 'N/A';
            $directorateId = $project->directorate?->id ?? null;

            $budgetHeadingTitle = $project->budgetHeading?->title ?? 'N/A';
            $budgetHeadingId = $project->budgetHeading?->id ?? null;
            $budgetHeadingColor = $budgetHeadingId && isset($budgetHeadingColors[$budgetHeadingId])
                ? $budgetHeadingColors[$budgetHeadingId]
                : '#6B7280';

            $priorityValue = $project->priority?->title ?? 'N/A';
            $priorityColor = isset($priorityColors[$priorityValue]) ? $priorityColors[$priorityValue] : '#6B7280';

            $fields = [
                ['label' => trans('global.project.fields.start_date'), 'key' => 'start_date', 'value' => $project->start_date?->format('Y-m-d') ?? 'N/A'],
                ['label' => trans('global.project.fields.end_date'), 'key' => 'end_date', 'value' => $project->end_date?->format('Y-m-d') ?? 'N/A'],
                ['label' => trans('global.project.fields.latest_budget'), 'key' => 'budget', 'value' => is_numeric($project->total_budget) ? number_format((float) $project->total_budget, 2) : 'N/A'],
                ['label' => trans('global.project.fields.priority_id'), 'key' => 'priority', 'value' => $priorityValue, 'color' => $priorityColor],
                ['label' => trans('global.project.fields.physical_progress'), 'key' => 'progress', 'value' => is_numeric($project->progress) ? $project->progress . '%' : 'N/A'],
                ['label' => trans('global.project.fields.project_manager'), 'key' => 'project_manager', 'value' => $project->projectManager->name ?? 'N/A'],
            ];

            return [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description ?? trans('global.noRecords'),
                'directorate' => ['title' => $directorateTitle, 'id' => $directorateId],
                'budget_heading' => [
                    'title' => $budgetHeadingTitle,
                    'id'    => $budgetHeadingId,
                ],
                'budget_heading_color' => $budgetHeadingColor,
                'fields' => $fields,
                'comment_count' => $project->comments_count ?? 0,
            ];
        })->all();

        $tableHeaders = [
            trans('global.project.fields.id'),
            trans('global.project.fields.title'),
            trans('global.project.fields.directorate_id'),
            trans('global.details'),
        ];

        $directorates = Directorate::pluck('title', 'id');

        return view('admin.projects.index', [
            'data' => $cardData,
            'tableData' => $tableData,
            'projects' => $projects,
            'routePrefix' => 'admin.project',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this project?',
            'arrayColumnColor' => [
                'title' => '#9333EA',
                'progress' => 'green',
                'budget' => 'blue',
                'directorate' => $directorateColors,
                'priority' => $priorityColors,
                'budget_heading' => $budgetHeadingColors,
            ],
            'tableHeaders' => $tableHeaders,
            'directorates' => $directorates,
        ]);
    }

    public function create(): View
    {
        abort_if(Gate::denies('project_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();

        $roleIds = $user->roles->pluck('id')->toArray();

        $directorates = collect();
        $departments = collect();
        $users = collect();
        $statuses = Status::pluck('title', 'id');
        $priorities = Priority::pluck('title', 'id');
        $fiscalYears = FiscalYear::pluck('title', 'id');
        $budgetHeadings = BudgetHeading::pluck('title', 'id');

        if (in_array(Role::SUPERADMIN, $roleIds)) {
            $directorates = Directorate::pluck('title', 'id');
        } elseif (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            $fixedDirectorateId = $user->directorate_id;
            $directorates = Directorate::where('id', $fixedDirectorateId)->pluck('title', 'id');
        }

        return view('admin.projects.create', compact('directorates', 'departments', 'budgetHeadings', 'statuses', 'priorities', 'fiscalYears', 'users'));
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('project_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        try {
            /** @var \Illuminate\Http\Request $request */
            $data = $request->validated();

            $project = Project::create(\Illuminate\Support\Arr::except($data, ['budgets']));

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('projects', 'public');
                    $project->files()->create([
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'file_type' => $file->extension(),
                        'file_size' => $file->getSize(),
                        'user_id' => Auth::id(),
                    ]);
                }
            }

            if (Session::has('temp_files')) {
                foreach (Session::get('temp_files', []) as $tempPath) {
                    Storage::disk('public')->delete($tempPath);
                }
                Session::forget('temp_files');
            }

            return redirect()->route('admin.project.index')->with('message', 'Project created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to create project. Please try again.']);
        }
    }

    public function show(Project $project): View
    {
        abort_if(Gate::denies('project_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $project->load([
            'directorate',
            'department',
            'status',
            'priority',
            'projectManager',
            'budgets',
            'comments.user',
            'comments.replies.user',
        ]);

        $user = Auth::user();
        $commentIds = $user->comments()
            ->where('commentable_type', 'App\Models\Project')
            ->where('commentable_id', $project->id)
            ->whereNull('comment_user.read_at')
            ->pluck('comments.id');

        foreach ($commentIds as $commentId) {
            $user->comments()->updateExistingPivot($commentId, ['read_at' => now()]);
        }

        $latestBudget = $project->budgets->sortByDesc('id')->first();
        $totalBudget = $latestBudget ? (float) $latestBudget->total_budget : 0.0;
        $latestBudgetId = $latestBudget ? $latestBudget->id : null;

        return view('admin.projects.show', compact('project', 'totalBudget', 'latestBudgetId'));
    }

    public function edit(Project $project): View
    {
        abort_if(Gate::denies('project_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();

        $directorates = collect();
        $departments = collect();
        $users = collect();
        $statuses = Status::pluck('title', 'id');
        $priorities = Priority::pluck('title', 'id');
        $fiscalYears = FiscalYear::pluck('title', 'id');
        $budgetHeadings = BudgetHeading::pluck('title', 'id');

        $project->load(['budgets', 'files']);

        if (in_array(Role::SUPERADMIN, $roleIds)) {
            $directorates = Directorate::pluck('title', 'id');
        } elseif (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            $fixedDirectorateId = $user->directorate_id;
            $directorates = Directorate::where('id', $fixedDirectorateId)->pluck('title', 'id');
        } elseif (in_array(Role::PROJECT_USER, $roleIds)) {
            $fixedDirectorateId = $user->directorate_id;
            $directorates = Directorate::where('id', $fixedDirectorateId)->pluck('title', 'id');
            $users = User::whereIn('id', function ($query) use ($project) {
                $query->select('user_id')
                    ->from('project_user')
                    ->where('project_id', $project->id);
            })->pluck('name', 'id');
        }

        if ($project->directorate_id) {
            $departments = Department::whereHas('directorates', function ($query) use ($project) {
                $query->where('directorate_id', $project->directorate_id);
            })->pluck('title', 'id');
            if (in_array(Role::SUPERADMIN, $roleIds) && $users->isEmpty()) {
                $users = User::where('directorate_id', $project->directorate_id)->pluck('name', 'id');
            }
        }

        return view('admin.projects.edit', compact(
            'project',
            'directorates',
            'departments',
            'budgetHeadings',
            'users',
            'statuses',
            'priorities',
            'fiscalYears'
        ));
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        abort_if(Gate::denies('project_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        try {
            /** @var \Illuminate\Http\Request $request */
            $data = $request->validated();

            $project->update(\Illuminate\Support\Arr::except($data, ['budgets', 'files']));

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('projects', 'public');
                    $project->files()->create([
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'file_type' => $file->extension(),
                        'file_size' => $file->getSize(),
                        'user_id' => Auth::id(),
                    ]);
                }
            }

            return redirect()->route('admin.project.index')->with('message', 'Project updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to update project. Please try again.']);
        }
    }

    public function destroy(Project $project)
    {
        //
    }

    public function getDepartments($directorate_id): JsonResponse
    {
        try {
            $directorate = Directorate::find($directorate_id);
            if (! $directorate) {
                return response()->json([]);
            }
            $departments = $directorate->departments->map(function ($department) {
                return [
                    'value' => (string) $department->id,
                    'label' => $department->title,
                ];
            })->toArray();

            return response()->json($departments);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch departments.'], 500);
        }
    }

    public function getUsers($directorate_id): JsonResponse
    {
        try {
            $users = User::where('directorate_id', $directorate_id)
                ->select('id', 'name')
                ->get()
                ->map(function ($user) {
                    return [
                        'value' => (string) $user->id,
                        'label' => $user->name,
                    ];
                })->toArray();

            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch users.'], 500);
        }
    }

    public function progressChart(Project $project)
    {
        $project->load(['tasks', 'contracts', 'expenses']);
        return view('admin.expenses.progress_chart', compact('project'));
    }
}
