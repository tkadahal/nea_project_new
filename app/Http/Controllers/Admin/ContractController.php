<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contract\StoreContractRequest;
use App\Http\Requests\Contract\UpdateContractRequest;
use App\Models\Contract;
use App\Models\Directorate;
use App\Models\Role;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ContractController extends Controller
{
    public function index(): View
    {
        abort_if(Gate::denies('contract_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $contractQuery = Contract::with(['directorate', 'status', 'priority', 'project'])->latest();

        $roleIds = $user->roles->pluck('id')->toArray();

        $contractQuery->when(!(in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds)), function ($query) use ($user, $roleIds) {
            if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                if ($user->directorate_id) {
                    $query->where('directorate_id', $user->directorate_id);
                } else {
                    $query->where('id', 0);
                }
            } else {
                $query->whereHas('project.users', function ($subQuery) use ($user) {
                    $subQuery->where('users.id', $user->id);
                });
            }
        });

        try {
            $contracts = $contractQuery->get();
        } catch (\Exception $e) {
            return view('admin.contracts.index', [
                'data' => [],
                'tableData' => [],
                'contracts' => collect([]),
                'routePrefix' => 'admin.contract',
                'actions' => ['view', 'edit', 'delete'],
                'deleteConfirmationMessage' => 'Are you sure you want to delete this contract?',
                'arrayColumnColor' => [],
                'headers' => [],
                'error' => 'Unable to load contracts due to an unexpected error. Please try again later.'
            ]);
        }

        $directorateColors = config('colors.directorate');
        $priorityColors = config('colors.priority');

        $allDirectorates = Directorate::pluck('id')->toArray();
        foreach ($allDirectorates as $id) {
            if (!isset($directorateColors[$id])) {
                $directorateColors[$id] = 'gray';
            }
        }

        $headers = [
            trans('global.contract.fields.id'),
            trans('global.contract.fields.title'),
            trans('global.details'),
        ];

        $tableData = $contracts->map(function ($contract) use ($priorityColors) {
            $priorityValue = $contract->priority?->title ?? 'N/A';
            $priorityDisplayColor = $priorityColors[$priorityValue] ?? '#6B7280';

            return [
                'id' => $contract->id,
                'title' => $contract->title ?? 'Untitled',
                'fields' => [
                    ['title' => trans('global.contract.fields.contract_agreement_date') . ': ' . ($contract->contract_agreement_date?->format('Y-m-d') ?? 'N/A'), 'color' => 'gray'],
                    ['title' => trans('global.contract.fields.agreement_completion_date') . ': ' . ($contract->agreement_completion_date?->format('Y-m-d') ?? 'N/A'), 'color' => 'gray'],
                    ['title' => trans('global.contract.fields.contract_amount') . ': ' . (is_numeric($contract->contract_amount) ? number_format((float) $contract->contract_amount, 2) : 'N/A'), 'color' => 'blue'],
                    ['title' => trans('global.contract.fields.progress') . ': ' . (is_numeric($contract->progress) ? $contract->progress . '%' : 'N/A'), 'color' => 'green'],
                    ['title' => trans('global.contract.fields.priority_id') . ': ' . $priorityValue, 'color' => $priorityDisplayColor],
                ],
            ];
        })->all();

        $cardData = $contracts->map(function ($contract) use ($directorateColors, $priorityColors) {
            $directorateTitle = $contract->directorate?->title ?? 'N/A';
            $directorateId = $contract->directorate?->id ?? null;
            $priorityValue = $contract->priority?->title ?? 'N/A';
            $priorityColor = $priorityColors[$priorityValue] ?? '#6B7280';
            $projectTitle = $contract->project?->title ?? 'N/A';

            return [
                'id' => $contract->id,
                'title' => $contract->title ?? 'Untitled',
                'description' => $contract->description ?? 'No description available',
                'directorate' => ['title' => $directorateTitle, 'id' => $directorateId],
                'fields' => [
                    ['label' => trans('global.contract.fields.contract_agreement_date'), 'key' => 'contract_agreement_date', 'value' => $contract->contract_agreement_date?->format('Y-m-d') ?? 'N/A', 'color' => 'yellow'],
                    ['label' => trans('global.contract.fields.agreement_effective_date'), 'key' => 'agreement_effective_date', 'value' => $contract->agreement_effective_date?->format('Y-m-d') ?? 'N/A', 'color' => 'green'],
                    ['label' => trans('global.contract.fields.agreement_completion_date'), 'key' => 'agreement_completion_date', 'value' => $contract->agreement_completion_date?->format('Y-m-d') ?? 'N/A', 'color' => 'red'],
                    ['label' => trans('global.contract.fields.contract_amount'), 'key' => 'contract_amount', 'value' => is_numeric($contract->contract_amount) ? number_format((float) $contract->contract_amount, 2) : 'N/A', 'color' => 'orange'],
                    ['label' => trans('global.contract.fields.progress'), 'key' => 'progress', 'value' => is_numeric($contract->progress) ? $contract->progress . '%' : 'N/A', 'color' => 'yellow'],
                    ['label' => trans('global.contract.fields.status_id'), 'key' => 'status', 'value' => $contract->status?->title ?? 'N/A'],
                    ['label' => trans('global.contract.fields.priority_id'), 'key' => 'priority', 'value' => $priorityValue, 'color' => $priorityColor],
                    ['label' => trans('global.contract.fields.directorate_id'), 'key' => 'directorate', 'value' => $directorateTitle, 'color' => $directorateColors[$directorateId] ?? 'gray'],
                    ['label' => trans('global.contract.fields.project_id'), 'key' => 'project', 'value' => $projectTitle, 'color' => 'yellow'],
                ],
            ];
        })->all();

        return view('admin.contracts.index', [
            'data' => $cardData,
            'tableData' => $tableData,
            'contracts' => $contracts,
            'routePrefix' => 'admin.contract',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this contract?',
            'arrayColumnColor' => [
                'title' => '#9333EA',
                'contract_amount' => 'blue',
                'progress' => 'green',
                'directorate' => $directorateColors,
                'priority' => $priorityColors,
                'project' => 'blue',
            ],
            'headers' => $headers,
        ]);
    }

    public function create(): View
    {
        abort_if(Gate::denies('contract_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();
        $projectId = request()->query('project_id');
        $project = null;
        $selectedDirectorate = null;

        if (in_array(Role::SUPERADMIN, $roleIds)) {
            $directorates = Directorate::pluck('title', 'id');
            $projects = collect();
            if ($projectId) {
                $project = Project::find($projectId);
                if ($project) {
                    $selectedDirectorate = Directorate::find($project->directorate_id);
                    $projects = collect([
                        [
                            'id' => $project->id,
                            'title' => $project->title,
                            'total_budget' => number_format($project->total_budget, 2),
                            'remaining_budget' => number_format(max(0, $project->total_budget - Contract::where('project_id', $project->id)->whereNull('deleted_at')->sum('contract_amount')), 2),
                        ]
                    ]);
                }
            }
        } elseif (in_array(Role::DIRECTORATE_USER, $roleIds) && $user->directorate_id) {
            $directorates = collect([$user->directorate_id => Directorate::find($user->directorate_id)?->title ?? 'N/A']);
            $projectsQuery = Project::without(['tasks', 'expenses', 'contracts'])
                ->where('directorate_id', $user->directorate_id)
                ->whereNull('deleted_at');
            if ($projectId) {
                $project = Project::find($projectId);
                if ($project && $project->directorate_id == $user->directorate_id) {
                    $selectedDirectorate = Directorate::find($project->directorate_id);
                    $projectsQuery->where('id', $projectId);
                }
            }
            $projects = $projectsQuery->get()->map(function ($project) {
                $existingContractsSum = Contract::where('project_id', $project->id)
                    ->whereNull('deleted_at')
                    ->sum('contract_amount');
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'total_budget' => number_format($project->total_budget, 2),
                    'remaining_budget' => number_format(max(0, $project->total_budget - $existingContractsSum), 2),
                ];
            });
        } elseif (in_array(Role::PROJECT_USER, $roleIds) && $user->directorate_id) {
            $directorates = collect([$user->directorate_id => Directorate::find($user->directorate_id)?->title ?? 'N/A']);
            $projectsQuery = $user->projects()->whereNull('deleted_at');
            if ($projectId) {
                $project = Project::find($projectId);
                if ($project && $user->projects()->where('projects.id', $projectId)->exists()) {
                    $selectedDirectorate = Directorate::find($project->directorate_id);
                    $projectsQuery->where('projects.id', $projectId);
                }
            }
            $projects = $projectsQuery->get()->map(function ($project) {
                $existingContractsSum = Contract::where('project_id', $project->id)
                    ->whereNull('deleted_at')
                    ->sum('contract_amount');
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'total_budget' => number_format($project->total_budget, 2),
                    'remaining_budget' => number_format(max(0, $project->total_budget - $existingContractsSum), 2),
                ];
            });
        } else {
            $directorates = collect();
            $projects = collect();
            if ($projectId) {
                $project = Project::find($projectId);
                if ($project) {
                    $selectedDirectorate = Directorate::find($project->directorate_id);
                    $projects = collect([
                        [
                            'id' => $project->id,
                            'title' => $project->title,
                            'total_budget' => number_format($project->total_budget, 2),
                            'remaining_budget' => number_format(max(0, $project->total_budget - Contract::where('project_id', $project->id)->whereNull('deleted_at')->sum('contract_amount')), 2),
                        ]
                    ]);
                }
            }
        }

        $statuses = Status::pluck('title', 'id');
        $priorities = Priority::pluck('title', 'id');

        return view('admin.contracts.create', compact('directorates', 'projects', 'statuses', 'priorities', 'project', 'selectedDirectorate'));
    }

    public function store(StoreContractRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('contract_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        Contract::create($request->validated());

        return redirect()->route('admin.contract.index');
    }

    public function show(Contract $contract): View
    {
        abort_if(Gate::denies('contract_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contract->load(['directorate', 'project', 'status', 'priority']);

        return view('admin.contracts.show', compact('contract'));
    }

    public function edit(Contract $contract): View
    {
        abort_if(Gate::denies('contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();
        $directorates = collect();
        $projects = collect();

        if (in_array(Role::SUPERADMIN, $roleIds)) {
            $directorates = Directorate::pluck('title', 'id');
        } elseif (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            if ($user->directorate_id) {
                $directorates = collect([$user->directorate_id => Directorate::find($user->directorate_id)?->title ?? 'N/A']);
                $projects = Project::without(['tasks', 'expenses', 'contracts'])
                    ->where('directorate_id', $user->directorate_id)
                    ->whereNull('deleted_at')
                    ->get()
                    ->map(function ($project) use ($contract) {
                        $existingContractsSum = Contract::where('project_id', $project->id)
                            ->whereNull('deleted_at')
                            ->where('id', '!=', $contract->id)
                            ->sum('contract_amount');
                        return [
                            'id' => $project->id,
                            'title' => $project->title,
                            'total_budget' => number_format($project->total_budget, 2),
                            'remaining_budget' => number_format(max(0, $project->total_budget - $existingContractsSum), 2),
                        ];
                    });
                if ($projects->isEmpty() || $projects->every(fn($project) => $project['remaining_budget'] === '0.00')) {
                    Log::warning('No valid projects with remaining budget for directorate user (edit)', [
                        'user_id' => $user->id,
                        'directorate_id' => $user->directorate_id,
                        'contract_id' => $contract->id,
                    ]);
                }
            } else {
                Log::warning('Directorate user has no directorate_id (edit)', ['user_id' => $user->id, 'contract_id' => $contract->id]);
            }
        } elseif (in_array(Role::PROJECT_USER, $roleIds)) {
            if ($user->directorate_id) {
                $directorates = collect([$user->directorate_id => Directorate::find($user->directorate_id)?->title ?? 'N/A']);
                $projects = $user->projects()
                    ->whereNull('deleted_at')
                    ->get()
                    ->map(function ($project) use ($contract) {
                        $existingContractsSum = Contract::where('project_id', $project->id)
                            ->whereNull('deleted_at')
                            ->where('id', '!=', $contract->id)
                            ->sum('contract_amount');
                        return [
                            'id' => $project->id,
                            'title' => $project->title,
                            'total_budget' => number_format($project->total_budget, 2),
                            'remaining_budget' => number_format(max(0, $project->total_budget - $existingContractsSum), 2),
                        ];
                    });

                if ($projects->isEmpty() || $projects->every(fn($project) => $project['remaining_budget'] === '0.00')) {
                    Log::warning('No valid projects with remaining budget for project user (edit)', [
                        'user_id' => $user->id,
                        'directorate_id' => $user->directorate_id,
                        'contract_id' => $contract->id,
                    ]);
                }
            } else {
                Log::warning('Project user has no directorate_id (edit)', ['user_id' => $user->id, 'contract_id' => $contract->id]);
            }
        } else {
            Log::warning('No valid role for contract editing', ['user_id' => $user->id, 'contract_id' => $contract->id, 'roles' => $roleIds]);
        }

        $statuses = Status::pluck('title', 'id');
        $priorities = Priority::pluck('title', 'id');

        $contract->load('directorate', 'project', 'status', 'priority');

        return view('admin.contracts.edit', compact('contract', 'directorates', 'projects', 'statuses', 'priorities'));
    }

    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        abort_if(Gate::denies('contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contract->update($request->validated());

        return redirect()->route('admin.contract.index');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        abort_if(Gate::denies('contract_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contract->delete();

        return back();
    }

    public function getProjects(int $directorateId): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user->hasRole(Role::SUPERADMIN)) {
                Log::warning('Unauthorized attempt to fetch projects', [
                    'user_id' => $user->id,
                    'directorate_id' => $directorateId,
                ]);
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $projects = Project::without(['tasks', 'expenses', 'contracts'])
                ->where('directorate_id', $directorateId)
                ->whereNull('deleted_at')
                ->get()
                ->map(function ($project) {
                    $existingContractsSum = Contract::where('project_id', $project->id)
                        ->whereNull('deleted_at')
                        ->sum('contract_amount');
                    return [
                        'value' => (string) $project->id,
                        'label' => $project->title,
                        'total_budget' => number_format($project->total_budget, 2),
                        'remaining_budget' => number_format(max(0, $project->total_budget - $existingContractsSum), 2),
                    ];
                })
                ->toArray();

            Log::info('Projects fetched for superadmin', [
                'user_id' => $user->id,
                'directorate_id' => $directorateId,
                'project_count' => count($projects),
                'projects' => array_slice($projects, 0, 3),
            ]);

            return response()->json($projects);
        } catch (\Exception $e) {
            Log::error('Failed to fetch projects', [
                'directorate_id' => $directorateId,
                'user_id' => Auth::user()->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to fetch projects.'], 500);
        }
    }

    public function getProjectBudget(int $projectId): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user->hasRole(Role::SUPERADMIN)) {
                Log::warning('Unauthorized attempt to fetch project budget', [
                    'user_id' => $user->id,
                    'project_id' => $projectId,
                ]);
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $project = Project::findOrFail($projectId);
            $existingContractsSum = Contract::where('project_id', $project->id)
                ->whereNull('deleted_at')
                ->sum('contract_amount');
            $budgetData = [
                'total_budget' => number_format($project->total_budget, 2),
                'remaining_budget' => number_format(max(0, $project->total_budget - $existingContractsSum), 2),
            ];

            Log::info('Project budget fetched for superadmin', [
                'user_id' => $user->id,
                'project_id' => $projectId,
                'budget_data' => $budgetData,
            ]);

            return response()->json($budgetData);
        } catch (\Exception $e) {
            Log::error('Failed to fetch project budget', [
                'project_id' => $projectId,
                'user_id' => Auth::user()->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to fetch project budget.'], 500);
        }
    }
}
