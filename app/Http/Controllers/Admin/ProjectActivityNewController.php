<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Role;
use App\Models\Budget;
use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectActivityPlan;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProjectActivityExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\ProjectActivityDefinition;
use App\Exports\ProjectActivityTemplateExport;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\ProjectActivity\StoreProjectActivityRequest;
use App\Http\Requests\ProjectActivity\UpdateProjectActivityRequest;

class ProjectActivityController extends Controller
{
    public function index(): View
    {
        abort_if(Gate::denies('projectActivity_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();

        $activityQuery = ProjectActivityPlan::query()
            ->with(['fiscalYear', 'activityDefinition.project'])
            ->join('project_activity_definitions', 'project_activity_plans.activity_definition_id', '=', 'project_activity_definitions.id')
            ->selectRaw('project_activity_definitions.project_id as project_id')
            ->addSelect('project_activity_plans.fiscal_year_id')
            ->selectRaw('SUM(CASE WHEN project_activity_definitions.parent_id IS NULL AND project_activity_definitions.expenditure_id = 1 THEN project_activity_plans.total_budget ELSE 0 END) as capital_budget')
            ->selectRaw('SUM(CASE WHEN project_activity_definitions.parent_id IS NULL AND project_activity_definitions.expenditure_id = 2 THEN project_activity_plans.total_budget ELSE 0 END) as recurrent_budget')
            ->selectRaw('SUM(CASE WHEN project_activity_definitions.parent_id IS NULL THEN project_activity_plans.total_budget ELSE 0 END) as total_budget')
            ->selectRaw('MAX(project_activity_plans.created_at) as latest_created_at')
            ->groupBy('project_id', 'project_activity_plans.fiscal_year_id')
            ->havingRaw('SUM(CASE WHEN project_activity_definitions.parent_id IS NULL THEN project_activity_plans.total_budget ELSE 0 END) > 0')
            ->orderByDesc('latest_created_at');

        $data = [];
        try {
            $roleIds = $user->roles->pluck('id')->toArray();

            if (!in_array(Role::SUPERADMIN, $roleIds) && !in_array(Role::ADMIN, $roleIds)) {
                if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                    $directorateId = $user->directorate ? [$user->directorate->id] : [];
                    $activityQuery->whereHas('activityDefinition.project', function ($query) use ($directorateId) {
                        $query->whereIn('id', $directorateId);
                    });
                } elseif (in_array(Role::PROJECT_USER, $roleIds)) {
                    $projectIds = $user->projects->pluck('id')->toArray();
                    $activityQuery->whereHas('activityDefinition.project', function ($query) use ($projectIds) {
                        $query->whereIn('id', $projectIds);
                    });
                } else {
                    $activityQuery->where('project_activity_plans.id', $user->id);
                }
            }
        } catch (\Exception $e) {
            $data['error'] = 'Unable to load activities due to an error.';
        }

        $activities = $activityQuery->get();

        $headers = [
            'Id',
            'Fiscal Year',
            'Project',
            'Total Budget',
            'Capital Budget',
            'Recurrent Budget',
            'Actions',
        ];

        $data = $activities->map(function ($activity) {
            return [
                'project_id' => $activity->project_id,
                'fiscal_year_id' => $activity->fiscal_year_id,
                'project' => $activity->activityDefinition->project->title ?? 'N/A',
                'fiscal_year' => $activity->fiscalYear->title ?? 'N/A',
                'capital_budget' => $activity->capital_budget,
                'recurrent_budget' => $activity->recurrent_budget,
                'total_budget' => $activity->total_budget,
            ];
        })->all();

        return view('admin.projectActivities.index', [
            'headers' => $headers,
            'data' => $data,
            'activities' => $activities,
            'routePrefix' => 'admin.projectActivity',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this project activity?',
        ]);
    }

    public function create(Request $request): View
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $projects = $user->projects;
        $fiscalYears = FiscalYear::getFiscalYearOptions();

        $selectedProjectId = $request->input('project_id') ?? $projects->first()?->id;
        $selectedProject = $projects->find($selectedProjectId) ?? $projects->first();

        $selectedFiscalYearId = $request->input('fiscal_year_id') ?? array_key_last($fiscalYears);

        $projectOptions = $projects->map(fn(Project $project) => [
            'value' => $project->id,
            'label' => $project->title,
            'selected' => $project->id === $selectedProjectId,
        ])->toArray();


        $capitalDefinitions = collect();
        $recurrentDefinitions = collect();
        $capitalRows = [];
        $recurrentRows = [];

        if ($selectedProject) {
            $capitalDefinitions = $selectedProject->activityDefinitions()
                ->with(['children' => function ($query) {
                    $query->with('children');
                }])
                ->whereNull('parent_id')
                ->where('expenditure_id', 1)
                ->where('status', 'active')
                ->get() ?? collect();

            $recurrentDefinitions = $selectedProject->activityDefinitions()
                ->with(['children' => function ($query) {
                    $query->with('children');
                }])
                ->whereNull('parent_id')
                ->where('expenditure_id', 2)
                ->where('status', 'active')
                ->get() ?? collect();

            $capitalRows = $this->buildRowsFromDefinitions($capitalDefinitions, 'capital', $selectedFiscalYearId);
            $recurrentRows = $this->buildRowsFromDefinitions($recurrentDefinitions, 'recurrent', $selectedFiscalYearId);
        }

        return view('admin.projectActivities.create', compact(
            'projects',
            'projectOptions',
            'fiscalYears',
            'selectedProject',
            'selectedProjectId',
            'selectedFiscalYearId',
            'capitalRows',
            'recurrentRows'
        ));
    }

    // /**
    //  * AJAX endpoint to load activity definitions and rows for a project.
    //  */
    // public function getDefinitions(Request $request)
    // {
    //     abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

    //     $user = Auth::user();

    //     $projectId = $request->input('project_id');
    //     $fiscalYearId = $request->input('fiscal_year_id');

    //     $project = $user->projects->find($projectId);

    //     if (!$project) {
    //         return response()->json(['error' => 'Project not found'], 404);
    //     }

    //     $capitalDefinitions = $project->activityDefinitions()
    //         ->with(['children' => function ($query) {
    //             $query->with('children');
    //         }])
    //         ->whereNull('parent_id')
    //         ->where('expenditure_id', 1)
    //         ->where('status', 'active')
    //         ->get() ?? collect();

    //     $recurrentDefinitions = $project->activityDefinitions()
    //         ->with(['children' => function ($query) {
    //             $query->with('children');
    //         }])
    //         ->whereNull('parent_id')
    //         ->where('expenditure_id', 2)
    //         ->where('status', 'active')
    //         ->get() ?? collect();

    //     $capitalRows = $this->buildRowsFromDefinitions($capitalDefinitions, 'capital', $fiscalYearId);
    //     $recurrentRows = $this->buildRowsFromDefinitions($recurrentDefinitions, 'recurrent', $fiscalYearId);

    //     // Render partial views for the table bodies
    //     $capitalHtml = view('admin.project-activities.tables.capital-rows', compact('capitalRows'))->render();
    //     $recurrentHtml = view('admin.project-activities.tables.recurrent-rows', compact('recurrentRows'))->render();

    //     return response()->json([
    //         'success' => true,
    //         'capital_rows' => $capitalHtml,
    //         'recurrent_rows' => $recurrentHtml,
    //         'capital_index_next' => count($capitalRows) + 1,  // For resuming addRow indexing
    //         'recurrent_index_next' => count($recurrentRows) + 1,
    //     ]);
    // }

    private function buildRowsFromDefinitions($definitions, string $type, ?int $fiscalYearId): array
    {
        $rows = [];
        $index = 1;

        // Collect all definition IDs in the tree (roots + children + grandchildren)
        $allDefinitionIds = collect();
        foreach ($definitions as $def) {
            $allDefinitionIds->push($def->id);
            if ($def->children) {
                foreach ($def->children as $child) {
                    $allDefinitionIds->push($child->id);
                    if ($child->children) {
                        foreach ($child->children as $grandchild) {
                            $allDefinitionIds->push($grandchild->id);
                        }
                    }
                }
            }
        }

        // Load existing plans if fiscal year is selected
        $plans = collect();
        if ($fiscalYearId && $allDefinitionIds->isNotEmpty()) {
            $plans = ProjectActivityPlan::whereIn('activity_definition_id', $allDefinitionIds->unique())
                ->where('fiscal_year_id', $fiscalYearId)
                ->get()
                ->keyBy('activity_definition_id');
        }

        $this->buildRowsRecursive($definitions, $rows, $index, 0, null, $plans);

        // Set row numbers after building the structure
        $this->setRowNumbers($rows);

        return $rows;
    }

    private function buildRowsRecursive($nodes, array &$rows, int &$index, int $depth, ?int $parentIndex, $plans): void
    {
        foreach ($nodes as $node) {
            $plan = $plans->get($node->id);

            $row = [
                'depth' => $depth,
                'index' => $index,
                'parent_index' => $parentIndex,
                'program' => $plan?->program_override ?? $node->program,
                'total_budget_quantity' => $plan?->total_quantity ?? '',
                'total_budget' => $plan?->total_budget ?? '',
                'total_expense_quantity' => $plan?->completed_quantity ?? '',
                'total_expense' => $plan?->total_expense ?? '',
                'planned_budget_quantity' => $plan?->planned_quantity ?? '',
                'planned_budget' => $plan?->planned_budget ?? '',
                'q1_quantity' => $plan?->q1_quantity ?? '',
                'q1' => $plan?->q1_amount ?? '',
                'q2_quantity' => $plan?->q2_quantity ?? '',
                'q2' => $plan?->q2_amount ?? '',
                'q3_quantity' => $plan?->q3_quantity ?? '',
                'q3' => $plan?->q3_amount ?? '',
                'q4_quantity' => $plan?->q4_quantity ?? '',
                'q4' => $plan?->q4_amount ?? '',
            ];

            $rows[] = $row;

            if ($node->children && $node->children->isNotEmpty()) {
                $childParentIndex = $index;
                $index++;
                $this->buildRowsRecursive($node->children, $rows, $index, $depth + 1, $childParentIndex, $plans);
            } else {
                $index++;
            }
        }
    }

    private function setRowNumbers(array &$rows): void
    {
        $topLevelCount = 0;
        $levelOneCounts = [];
        $levelTwoCounts = [];

        foreach ($rows as &$row) {
            $depth = $row['depth'];
            $parentIndex = $row['parent_index'];

            if ($depth === 0) {
                $topLevelCount++;
                $row['number'] = (string) $topLevelCount;
                $levelOneCounts[$topLevelCount] = 0;
            } elseif ($depth === 1) {
                // Find parent's number
                $parentNumber = '';
                foreach ($rows as $parentRow) {
                    if ($parentRow['index'] === $parentIndex) {
                        $parentNumber = $parentRow['number'];
                        break;
                    }
                }
                $levelOneCounts[$parentNumber] = ($levelOneCounts[$parentNumber] ?? 0) + 1;
                $row['number'] = $parentNumber . '.' . $levelOneCounts[$parentNumber];
                $levelTwoCounts[$row['number']] = 0;
            } elseif ($depth === 2) {
                // Find parent's number
                $parentNumber = '';
                foreach ($rows as $parentRow) {
                    if ($parentRow['index'] === $parentIndex) {
                        $parentNumber = $parentRow['number'];
                        break;
                    }
                }
                $levelTwoCounts[$parentNumber] = ($levelTwoCounts[$parentNumber] ?? 0) + 1;
                $row['number'] = $parentNumber . '.' . $levelTwoCounts[$parentNumber];
            }
        }
    }

    public function store(StoreProjectActivityRequest $request)
    {
        $validated = $request->validated();

        $projectId = $validated['project_id'];
        $fiscalYearId = $validated['fiscal_year_id'];
        $totalPlannedBudget = (float) ($validated['total_planned_budget'] ?? 0);

        $budget = Budget::where('project_id', $projectId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->first();

        $remainingBudget = $budget ? (float) $budget->total_budget : 0.0;

        throw_if(
            $totalPlannedBudget > $remainingBudget,
            ValidationException::withMessages([
                'total_planned_budget' => 'Planned budget is greater than the remaining budget for this fiscal year.'
            ])
        );

        DB::beginTransaction();

        try {
            $project = Project::findOrFail($validated['project_id']);

            // Process capital (expenditure_id = 1)
            $this->processSection($request, 'capital', $projectId, $fiscalYearId, 1);

            // Process recurrent (expenditure_id = 2)
            $this->processSection($request, 'recurrent', $projectId, $fiscalYearId, 2);

            DB::commit();

            return redirect()->route('admin.projectActivity.index')->with('success', 'Project activities saved successfully!');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e; // Re-throw for request handling
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to save project activities: ' . $e->getMessage()]);
        }
    }

    /**
     * Process a section (capital or recurrent): Build hierarchy, create definitions, then plans.
     */
    private function processSection(Request $request, string $section, int $projectId, int $fiscalYearId, int $expenditureId): void
    {
        $rows = $request->input($section, []);
        if (empty($rows)) {
            return;
        }

        // Step 1: Collect all rows and map indices to flat list
        $flatRows = [];
        $indexMap = []; // Original index -> new internal ID
        $parentMap = []; // For resolving parents
        $rowCounter = 0;

        foreach ($rows as $originalIndex => $rowData) {
            $flatRows[$rowCounter] = $rowData;
            $flatRows[$rowCounter]['original_index'] = $originalIndex;
            $flatRows[$rowCounter]['internal_id'] = $rowCounter;
            $indexMap[$originalIndex] = $rowCounter;
            $rowCounter++;
        }

        // Step 2: Resolve parent relationships (map submitted parent_id to internal_id)
        foreach ($flatRows as $internalId => &$rowData) {
            $submittedParentId = $rowData['parent_id'] ?? null;
            if ($submittedParentId !== null) {
                $parentInternalId = $indexMap[$submittedParentId] ?? null;
                if ($parentInternalId === null) {
                    throw new \Exception("Invalid parent_id {$submittedParentId} for row {$rowData['original_index']} in {$section}.");
                }
                $rowData['resolved_parent_internal_id'] = $parentInternalId;
                $parentMap[$internalId] = $parentInternalId;
            }
        }

        // Step 3: Topological sort to create in order (parents before children)
        $processed = [];
        foreach ($flatRows as $internalId => $rowData) {
            $this->createInOrder($flatRows, $processed, $internalId, $parentMap);
        }

        // Step 4: Create definitions first (fixed structure, hierarchy)
        $definitionIdMap = []; // internal_id -> created definition ID
        foreach ($processed as $internalId) {
            $rowData = $flatRows[$internalId];
            $parentDefId = null;
            if (isset($rowData['resolved_parent_internal_id'])) {
                $parentDefId = $definitionIdMap[$rowData['resolved_parent_internal_id']] ?? null;
                if ($parentDefId === null) {
                    throw new \Exception("Parent definition not created yet for row {$rowData['original_index']} in {$section}.");
                }
            }

            // Ensure unique program per project (but allow overrides in plans)
            $program = trim($rowData['program']);
            $existingDef = ProjectActivityDefinition::where('project_id', $projectId)
                ->where('program', $program)
                ->where('status', 'active')
                ->first();

            $definitionData = [
                'project_id' => $projectId,
                'program' => $program,
                'expenditure_id' => $expenditureId,
                'description' => $rowData['description'] ?? null, // Optional, if added to form
                'status' => 'active',
                'parent_id' => $parentDefId,
            ];

            if ($existingDef) {
                // Reuse existing if exact match (but check parent/expenditure)
                if ($existingDef->expenditure_id !== $expenditureId || $existingDef->parent_id !== $parentDefId) {
                    throw new \Exception("Conflicting definition for program '{$program}' in {$section}.");
                }
                $definition = $existingDef;
            } else {
                $definition = ProjectActivityDefinition::create($definitionData);
            }

            $definitionIdMap[$internalId] = $definition->id;
        }

        // Step 5: Create plans (variables per fiscal year)
        foreach ($processed as $internalId) {
            $rowData = $flatRows[$internalId];
            $defId = $definitionIdMap[$internalId];
            $activityDef = ProjectActivityDefinition::findOrFail($defId);

            // Override program if needed (rare case)
            $programOverride = null;
            $overrideModifiedAt = null;
            if (isset($rowData['program_override']) && $rowData['program_override'] !== $activityDef->program) {
                $programOverride = $rowData['program_override'];
                $overrideModifiedAt = now();
            }

            // Validate sums (planned == sum quarters; optional server-side)
            $this->validateSums($rowData);

            // Child sum <= parent (optional; JS handles, but re-check)
            $this->validateChildSums($flatRows, $internalId, $parentMap, $definitionIdMap, $fiscalYearId, $projectId, $section);

            $planData = [
                'activity_definition_id' => $defId,
                'fiscal_year_id' => $fiscalYearId,
                'program_override' => $programOverride,
                'override_modified_at' => $overrideModifiedAt,
                'total_budget' => (float) ($rowData['total_budget'] ?? 0),
                'planned_budget' => (float) ($rowData['planned_budget'] ?? 0),
                'q1_amount' => (float) ($rowData['q1'] ?? 0),
                'q2_amount' => (float) ($rowData['q2'] ?? 0),
                'q3_amount' => (float) ($rowData['q3'] ?? 0),
                'q4_amount' => (float) ($rowData['q4'] ?? 0),
                'total_quantity' => (float) ($rowData['total_budget_quantity'] ?? 0),
                'planned_quantity' => (float) ($rowData['planned_budget_quantity'] ?? 0),
                'q1_quantity' => (float) ($rowData['q1_quantity'] ?? 0),
                'q2_quantity' => (float) ($rowData['q2_quantity'] ?? 0),
                'q3_quantity' => (float) ($rowData['q3_quantity'] ?? 0),
                'q4_quantity' => (float) ($rowData['q4_quantity'] ?? 0),
                'total_expense' => (float) ($rowData['total_expense'] ?? 0),
                'completed_quantity' => (float) ($rowData['total_expense_quantity'] ?? 0),
            ];

            // Ensure uniqueness per definition + fiscal year
            $existingPlan = ProjectActivityPlan::where('activity_definition_id', $defId)
                ->where('fiscal_year_id', $fiscalYearId)
                ->first();

            if ($existingPlan) {
                $existingPlan->update($planData);
            } else {
                ProjectActivityPlan::create($planData);
            }
        }
    }

    /**
     * Recursive helper to build processing order (parents first).
     */
    private function createInOrder(array &$flatRows, array &$processed, int $internalId, array $parentMap): void
    {
        if (in_array($internalId, $processed)) {
            return;
        }

        $parentInternalId = $parentMap[$internalId] ?? null;
        if ($parentInternalId !== null) {
            $this->createInOrder($flatRows, $processed, $parentInternalId, $parentMap);
        }

        $processed[] = $internalId;
    }

    /**
     * Validate planned == sum of quarters for amounts and quantities.
     */
    private function validateSums(array $rowData): void
    {
        $quarterAmountSum = ($rowData['q1'] ?? 0) + ($rowData['q2'] ?? 0) + ($rowData['q3'] ?? 0) + ($rowData['q4'] ?? 0);
        $plannedBudget = $rowData['planned_budget'] ?? 0;
        if (abs($quarterAmountSum - $plannedBudget) > 0.01) {
            throw ValidationException::withMessages([
                "{$rowData['original_index']}.planned_budget" => "Planned budget ({$plannedBudget}) must equal sum of quarters ({$quarterAmountSum}).",
            ]);
        }

        $quarterQuantitySum = ($rowData['q1_quantity'] ?? 0) + ($rowData['q2_quantity'] ?? 0) + ($rowData['q3_quantity'] ?? 0) + ($rowData['q4_quantity'] ?? 0);
        $plannedQuantity = $rowData['planned_budget_quantity'] ?? 0;
        if (abs($quarterQuantitySum - $plannedQuantity) > 0.01) {
            throw ValidationException::withMessages([
                "{$rowData['original_index']}.planned_budget_quantity" => "Planned quantity ({$plannedQuantity}) must equal sum of quarter quantities ({$quarterQuantitySum}).",
            ]);
        }
    }

    /**
     * Validate child sums <= parent for each field.
     */
    private function validateChildSums(array $flatRows, int $internalId, array $parentMap, array $definitionIdMap, int $fiscalYearId, int $projectId, string $section): void
    {
        $rowData = $flatRows[$internalId];
        $parentInternalId = $parentMap[$internalId] ?? null;
        if ($parentInternalId === null) {
            return; // Only for children
        }

        $parentDefId = $definitionIdMap[$parentInternalId] ?? null;
        $parentPlan = ProjectActivityPlan::where('activity_definition_id', $parentDefId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->first();

        if (!$parentPlan) {
            return; // Parent plan not yet created? (Shouldn't happen with order)
        }

        // Fields to check (map row keys to plan fields)
        $fields = [
            'total_budget' => 'total_budget',
            'total_expense' => 'total_expense',
            'planned_budget' => 'planned_budget',
            'q1' => 'q1_amount',
            'q2' => 'q2_amount',
            'q3' => 'q3_amount',
            'q4' => 'q4_amount',
            'total_budget_quantity' => 'total_quantity',
            'total_expense_quantity' => 'completed_quantity',
            'planned_budget_quantity' => 'planned_quantity',
            'q1_quantity' => 'q1_quantity',
            'q2_quantity' => 'q2_quantity',
            'q3_quantity' => 'q3_quantity',
            'q4_quantity' => 'q4_quantity',
        ];

        foreach ($fields as $rowKey => $planField) {
            $childValue = (float) ($rowData[$rowKey] ?? 0);
            $parentValue = $parentPlan->{$planField} ?? 0;

            // Sum all siblings including this child
            $siblingSum = $childValue;
            foreach ($flatRows as $sibId => $sibData) {
                if ($sibId !== $internalId && ($sibData['resolved_parent_internal_id'] ?? null) === $parentInternalId) {
                    $siblingSum += (float) ($sibData[$rowKey] ?? 0);
                }
            }

            if ($siblingSum > $parentValue + 0.01) {
                throw ValidationException::withMessages([
                    "{$rowData['original_index']}.{$rowKey}" => "Children sum for {$planField} ({$siblingSum}) exceeds parent ({$parentValue}).",
                ]);
            }
        }
    }

    public function show(int $projectId, int $fiscalYearId): View
    {
        abort_if(Gate::denies('projectActivity_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $project = Project::findOrFail($projectId);

        // Assuming user-project relation check
        if (!$project->users->contains($user->id)) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }

        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        // Load root definitions with full hierarchy (children + their plans) for weighted calcs and display
        $capitalPlans = ProjectActivityDefinition::forProject($projectId)
            ->active()
            ->whereNull('parent_id')
            ->where('expenditure_id', 1)
            ->with([
                'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId)->active(),
                'children' => fn($childQ) => $childQ->active()->with([
                    'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId)->active(),
                    'children' => fn($grandQ) => $grandQ->active()->with([
                        'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId)->active()
                    ])
                ])
            ])
            ->get();

        $recurrentPlans = ProjectActivityDefinition::forProject($projectId)
            ->active()
            ->whereNull('parent_id')
            ->where('expenditure_id', 2)
            ->with([
                'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId)->active(),
                'children' => fn($childQ) => $childQ->active()->with([
                    'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId)->active(),
                    'children' => fn($grandQ) => $grandQ->active()->with([
                        'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId)->active()
                    ])
                ])
            ])
            ->get();

        // Sums for all plans in hierarchy (remove whereNull('parent_id') to include children)
        $capitalSums = ProjectActivityPlan::forProject($projectId)
            ->join('project_activity_definitions', 'project_activity_plans.activity_definition_id', '=', 'project_activity_definitions.id')
            ->where('project_activity_plans.fiscal_year_id', $fiscalYearId)
            ->where('project_activity_definitions.expenditure_id', 1)
            // Removed: ->whereNull('project_activity_definitions.parent_id')
            ->selectRaw('
            SUM(project_activity_plans.total_budget) as total_budget,
            SUM(project_activity_plans.total_expense) as total_expense,
            SUM(project_activity_plans.planned_budget) as planned_budget,
            SUM(project_activity_plans.q1_amount) as q1,
            SUM(project_activity_plans.q2_amount) as q2,
            SUM(project_activity_plans.q3_amount) as q3,
            SUM(project_activity_plans.q4_amount) as q4
        ')
            ->first()
            ->toArray() ?? [];  // Fallback empty array if no data

        $recurrentSums = ProjectActivityPlan::forProject($projectId)
            ->join('project_activity_definitions', 'project_activity_plans.activity_definition_id', '=', 'project_activity_definitions.id')
            ->where('project_activity_plans.fiscal_year_id', $fiscalYearId)
            ->where('project_activity_definitions.expenditure_id', 2)
            // Removed: ->whereNull('project_activity_definitions.parent_id')
            ->selectRaw('
            SUM(project_activity_plans.total_budget) as total_budget,
            SUM(project_activity_plans.total_expense) as total_expense,
            SUM(project_activity_plans.planned_budget) as planned_budget,
            SUM(project_activity_plans.q1_amount) as q1,
            SUM(project_activity_plans.q2_amount) as q2,
            SUM(project_activity_plans.q3_amount) as q3,
            SUM(project_activity_plans.q4_amount) as q4
        ')
            ->first()
            ->toArray() ?? [];

        return view('admin.project-activities.show', compact(
            'project',
            'fiscalYear',
            'capitalPlans',
            'recurrentPlans',
            'projectId',
            'fiscalYearId',
            'capitalSums',
            'recurrentSums'
        ));
    }

    public function edit(int $projectId, int $fiscalYearId): View
    {
        abort_if(Gate::denies('projectActivity_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $project = Project::findOrFail($projectId);

        // Assuming user-project relation check
        if (!$project->users->contains($user->id)) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }

        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $projects = $user->projects;
        $fiscalYears = FiscalYear::getFiscalYearOptions();

        $projectOptions = $projects->map(fn(Project $project) => [
            'value' => $project->id,
            'label' => $project->title,
        ])->toArray();

        // Load root definitions with plans for edit
        $capitalDefinitions = $project->activityDefinitions()
            ->with(['children' => function ($query) use ($fiscalYearId) {
                $query->with(['children.plans' => function ($pQ) use ($fiscalYearId) {
                    $pQ->where('fiscal_year_id', $fiscalYearId);
                }, 'plans' => function ($pQ) use ($fiscalYearId) {
                    $pQ->where('fiscal_year_id', $fiscalYearId);
                }]);
            }, 'plans' => function ($pQ) use ($fiscalYearId) {
                $pQ->where('fiscal_year_id', $fiscalYearId);
            }])
            ->whereNull('parent_id')
            ->where('expenditure_id', 1)
            ->where('status', 'active')
            ->get();

        $recurrentDefinitions = $project->activityDefinitions()
            ->with(['children' => function ($query) use ($fiscalYearId) {
                $query->with(['children.plans' => function ($pQ) use ($fiscalYearId) {
                    $pQ->where('fiscal_year_id', $fiscalYearId);
                }, 'plans' => function ($pQ) use ($fiscalYearId) {
                    $pQ->where('fiscal_year_id', $fiscalYearId);
                }]);
            }, 'plans' => function ($pQ) use ($fiscalYearId) {
                $pQ->where('fiscal_year_id', $fiscalYearId);
            }])
            ->whereNull('parent_id')
            ->where('expenditure_id', 2)
            ->where('status', 'active')
            ->get();

        $capitalRows = $this->buildRowsFromDefinitions($capitalDefinitions, 'capital', $fiscalYearId);
        $recurrentRows = $this->buildRowsFromDefinitions($recurrentDefinitions, 'recurrent', $fiscalYearId);

        return view('admin.project-activities.edit', compact(
            'project',
            'fiscalYear',
            'projectOptions',
            'fiscalYears',
            'capitalRows',
            'recurrentRows',
            'projectId',
            'fiscalYearId'
        ));
    }

    public function update(UpdateProjectActivityRequest $request, int $projectId, int $fiscalYearId)
    {
        $validated = $request->validated();

        // Ensure project and fiscal year match
        $project = Project::findOrFail($projectId);
        if (($validated['project_id'] ?? null) != $projectId || ($validated['fiscal_year_id'] ?? null) != $fiscalYearId) {
            return back()->withErrors(['error' => 'Project or fiscal year mismatch.']);
        }

        DB::beginTransaction();

        try {
            // Process capital (expenditure_id = 1)
            $this->processSection($request, 'capital', $projectId, $fiscalYearId, 1);

            // Process recurrent (expenditure_id = 2)
            $this->processSection($request, 'recurrent', $projectId, $fiscalYearId, 2);

            DB::commit();

            return redirect()->route('admin.projectActivity.index')->with('success', 'Project activities updated successfully!');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e; // Re-throw for request handling
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update project activities: ' . $e->getMessage()]);
        }
    }

    public function destroy(ProjectActivityPlan $projectActivityPlan): Response
    {
        abort_if(Gate::denies('projectActivity_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $projectActivityPlan->delete();

        return response()->json(['message' => 'Project activity deleted successfully'], 200);
    }

    public function getBudgetData(Request $request): Response
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
        ]);

        $fiscalYearId = $request->integer('fiscal_year_id');
        if (!$fiscalYearId) {
            $fiscalYears = FiscalYear::getFiscalYearOptions();
            foreach ($fiscalYears as $option) {
                if (($option['selected'] ?? false) === true) {
                    $fiscalYearId = (int) $option['value'];
                    break;
                }
            }
            if (!$fiscalYearId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fiscal year selected or available.',
                    'data' => null,
                ]);
            }
        }

        if (!FiscalYear::where('id', $fiscalYearId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid fiscal year.',
                'data' => null,
            ]);
        }

        $budget = Budget::with(['project', 'fiscalYear'])
            ->where('project_id', $request->project_id)
            ->where('fiscal_year_id', $fiscalYearId)
            ->first();

        if (!$budget) {
            return response()->json([
                'success' => false,
                'message' => 'No budget found.',
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $budget->remaining_budget,
                'internal' => $budget->remaining_internal_budget,
                'government_share' => $budget->remaining_government_share,
                'government_loan' => $budget->remaining_government_loan,
                'foreign_loan' => $budget->remaining_foreign_loan_budget,
                'foreign_subsidy' => $budget->remaining_foreign_subsidy_budget,
                'cumulative' => Budget::getCumulativeBudget($budget->project, $budget->fiscalYear),
                'fiscal_year' => $budget->fiscalYear->name ?? '',
            ],
        ]);
    }

    public function getActivityData(Request $request): Response
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
        ]);

        $projectId = $request->project_id;
        $fiscalYearId = $request->fiscal_year_id;

        $user = Auth::user();
        $project = Project::findOrFail($projectId);
        if (!$project->users->contains($user->id)) {
            return response()->json(['success' => false, 'message' => 'Access denied to project.'], 403);
        }

        // Load root definitions (same as create)
        $capitalDefinitions = $project->activityDefinitions()
            ->whereNull('parent_id')
            ->where('expenditure_id', 1)
            ->with('children.children')
            ->active()
            ->get();
        $recurrentDefinitions = $project->activityDefinitions()
            ->whereNull('parent_id')
            ->where('expenditure_id', 2)
            ->with('children.children')
            ->active()
            ->get();

        // Check if edit mode (plans exist for FY)
        $capitalPlans = ProjectActivityPlan::forProject($projectId)
            ->active()
            ->where('fiscal_year_id', $fiscalYearId)
            ->whereHas('activityDefinition', fn($q) => $q->where('expenditure_id', 1))
            ->with(['activityDefinition' => fn($q) => $q->active()->with('children.plans')])
            ->get();
        $recurrentPlans = ProjectActivityPlan::forProject($projectId)
            ->active()
            ->where('fiscal_year_id', $fiscalYearId)
            ->whereHas('activityDefinition', fn($q) => $q->where('expenditure_id', 2))
            ->with(['activityDefinition' => fn($q) => $q->active()->with('children.plans')])
            ->get();

        $isEditMode = $capitalPlans->isNotEmpty() || $recurrentPlans->isNotEmpty();

        if ($isEditMode) {
            $capitalRows = $this->buildRowsFromPlans($capitalPlans, 'capital', $fiscalYearId);
            $recurrentRows = $this->buildRowsFromPlans($recurrentPlans, 'recurrent', $fiscalYearId);
        } else {
            $capitalRows = $this->buildRowsFromDefinitions($capitalDefinitions, 'capital', $fiscalYearId);
            $recurrentRows = $this->buildRowsFromDefinitions($recurrentDefinitions, 'recurrent', $fiscalYearId);
        }

        return response()->json([
            'success' => true,
            'capital_rows' => $capitalRows,
            'recurrent_rows' => $recurrentRows,
        ]);
    }

    private function buildRowsFromPlans($plans, $section, $fiscalYearId): array
    {
        $rows = [];
        $index = 1;
        foreach ($plans as $plan) {
            $def = $plan->activityDefinition;
            $rows[] = [
                'index' => $index++,
                'depth' => 0,
                'parent_index' => null,
                'number' => (string) $index,
                'program' => $def->program,
                'total_budget_quantity' => $plan->total_quantity ?? '',
                'total_budget' => $plan->total_budget ?? '',
                'total_expense_quantity' => $plan->completed_quantity ?? '',
                'total_expense' => $plan->total_expense ?? '',
                'planned_budget_quantity' => $plan->planned_quantity ?? '',
                'planned_budget' => $plan->planned_budget ?? '',
                'q1_quantity' => $plan->q1_quantity ?? '',
                'q1' => $plan->q1_amount ?? '',
                'q2_quantity' => $plan->q2_quantity ?? '',
                'q2' => $plan->q2_amount ?? '',
                'q3_quantity' => $plan->q3_quantity ?? '',
                'q3' => $plan->q3_amount ?? '',
                'q4_quantity' => $plan->q4_quantity ?? '',
                'q4' => $plan->q4_amount ?? '',
            ];
            // Recurse for children
            $this->addChildRowsFromPlans($def->children, $rows, end($rows)['index'], 1, $fiscalYearId);
        }
        return $rows;
    }

    private function addChildRowsFromPlans($children, &$rows, $parentIndex, $depth, $fiscalYearId): void
    {
        if ($children->isEmpty()) return;
        foreach ($children as $child) {
            $plan = $child->plans->where('fiscal_year_id', $fiscalYearId)->first();
            $rows[] = [
                'index' => count($rows) + 1,
                'depth' => $depth,
                'parent_index' => $parentIndex,
                'number' => '', // Will be set in setRowNumbers if needed
                'program' => $child->program,
                'total_budget_quantity' => $plan?->total_quantity ?? '',
                'total_budget' => $plan?->total_budget ?? '',
                'total_expense_quantity' => $plan?->completed_quantity ?? '',
                'total_expense' => $plan?->total_expense ?? '',
                'planned_budget_quantity' => $plan?->planned_quantity ?? '',
                'planned_budget' => $plan?->planned_budget ?? '',
                'q1_quantity' => $plan?->q1_quantity ?? '',
                'q1' => $plan?->q1_amount ?? '',
                'q2_quantity' => $plan?->q2_quantity ?? '',
                'q2' => $plan?->q2_amount ?? '',
                'q3_quantity' => $plan?->q3_quantity ?? '',
                'q3' => $plan?->q3_amount ?? '',
                'q4_quantity' => $plan?->q4_quantity ?? '',
                'q4' => $plan?->q4_amount ?? '',
            ];
            if ($depth < 2) {
                $this->addChildRowsFromPlans($child->children, $rows, end($rows)['index'], $depth + 1, $fiscalYearId);
            }
        }
        $this->setRowNumbers($rows);
    }

    public function downloadTemplate(Request $request): Response
    {
        $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
        ]);

        $projectId = $request->integer('project_id');
        $fiscalYearId = $request->integer('fiscal_year_id');

        $selectedProject = Project::where('id', $projectId)->first();
        if (!$selectedProject) {
            throw new Exception('Selected project not found.');
        }

        $selectedFiscalYear = FiscalYear::where('id', $fiscalYearId)->first();
        if (!$selectedFiscalYear) {
            throw new Exception('Selected fiscal year not found.');
        }

        // Pass selected values to export
        return Excel::download(
            new ProjectActivityTemplateExport($selectedProject->title, $selectedFiscalYear->title),
            'project_activity_' . $selectedProject->title . '_template.xlsx'
        );
    }

    public function showUploadForm(Request $request): View
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // Simplified form: just file upload, selections are in Excel
        return view('admin.project-activities.upload');
    }

    public function uploadExcel(Request $request): Response
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $spreadsheet = IOFactory::load($request->file('excel_file')->getRealPath());

            // Read project and FY from Capital sheet (A1 and H1)
            $capitalSheet = $spreadsheet->getSheetByName('पूँजीगत खर्च');
            if (!$capitalSheet) {
                throw new Exception('Capital sheet not found. Expected "पूँजीगत खर्च".');
            }

            $projectName = trim((string) $capitalSheet->getCell('A1')->getValue()); // Changed to getValue()
            $fiscalYearName = trim((string) $capitalSheet->getCell('H1')->getValue()); // Changed to H1 and getValue()

            if (empty($projectName)) {
                throw new Exception('Project title missing in Excel cell A1 on Capital sheet. Please select a project before downloading the template.');
            }
            if (empty($fiscalYearName)) {
                throw new Exception('Fiscal Year title missing in Excel cell H1 on Capital sheet. Please select a fiscal year before downloading the template.');
            }

            $project = Project::where('title', $projectName)->first();
            if (!$project) {
                throw new Exception("Project '{$projectName}' not found in database (check exact title match).");
            }

            $user = Auth::user();
            if (!$project->users->contains($user->id)) {
                throw new Exception('You do not have access to the selected project.');
            }

            $fiscalYear = FiscalYear::where('title', $fiscalYearName)->first(); // Assumes 'title' field for label
            if (!$fiscalYear) {
                throw new Exception("Fiscal Year '{$fiscalYearName}' not found in database (check exact title match).");
            }

            $projectId = $project->id;
            $fiscalYearId = $fiscalYear->id;

            // Optionally validate same selections in Recurrent sheet
            $recurrentSheet = $spreadsheet->getSheetByName('चालू खर्च');
            if ($recurrentSheet) {
                $recProjectName = trim((string) $recurrentSheet->getCell('A1')->getValue());
                $recFiscalYearName = trim((string) $recurrentSheet->getCell('H1')->getValue());
                if ($recProjectName !== $projectName || $recFiscalYearName !== $fiscalYearName) {
                    throw new Exception('Project or Fiscal Year selections must match across both sheets.');
                }
            }

            // Parse and process Capital (data starts at row 5, after two-row headers)
            $capitalData = $this->parseSheet($capitalSheet, 1, 5);
            $this->validateExcelData($capitalData);
            $this->insertHierarchicalData($capitalData, $projectId, $fiscalYearId);

            // Parse and process Recurrent
            if ($recurrentSheet) {
                $recurrentData = $this->parseSheet($recurrentSheet, 2, 5);
                $this->validateExcelData($recurrentData);
                $this->insertHierarchicalData($recurrentData, $projectId, $fiscalYearId);
            }

            DB::commit();

            return redirect()->route('admin.projectActivity.index')
                ->with('success', 'Excel uploaded and activities created successfully!');
        } catch (Exception $e) {
            DB::rollBack();

            return back()->withErrors(['excel_file' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    private function parseSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $expenditureId, int $startRow = 5): array
    {
        $data = [];
        $index = 0;

        for ($rowNum = $startRow; $rowNum <= 100; $rowNum++) {
            $cellA = $sheet->getCell('A' . $rowNum);
            $hash = trim((string) ($cellA->getCalculatedValue() ?? ''));

            // Skip empty or total row
            if (empty($hash) || trim($hash) === 'कुल जम्मा') {
                continue;
            }

            // Skip if not a valid numeric hierarchy
            $cleanHash = str_replace('.', '', $hash);
            if (!is_numeric($cleanHash)) {
                continue;
            }

            $parts = explode('.', $hash);
            $level = count($parts) - 1;
            $parentHash = $level > 0 ? implode('.', array_slice($parts, 0, -1)) : null;

            $program = trim((string) ($sheet->getCell('B' . $rowNum)->getCalculatedValue() ?? '')); // Cast for safety

            // Read quantities (odd columns: C,E,G,I,K,M,O) and amounts (even: D,F,H,J,L,N,P)
            $total_budget_qty = (float) ($sheet->getCell('C' . $rowNum)->getCalculatedValue() ?? 0);
            $total_budget = (float) ($sheet->getCell('D' . $rowNum)->getCalculatedValue() ?? 0);
            $total_expense_qty = (float) ($sheet->getCell('E' . $rowNum)->getCalculatedValue() ?? 0);
            $total_expense = (float) ($sheet->getCell('F' . $rowNum)->getCalculatedValue() ?? 0);
            $planned_budget_qty = (float) ($sheet->getCell('G' . $rowNum)->getCalculatedValue() ?? 0);
            $planned_budget = (float) ($sheet->getCell('H' . $rowNum)->getCalculatedValue() ?? 0);
            $q1_qty = (float) ($sheet->getCell('I' . $rowNum)->getCalculatedValue() ?? 0);
            $q1 = (float) ($sheet->getCell('J' . $rowNum)->getCalculatedValue() ?? 0);
            $q2_qty = (float) ($sheet->getCell('K' . $rowNum)->getCalculatedValue() ?? 0);
            $q2 = (float) ($sheet->getCell('L' . $rowNum)->getCalculatedValue() ?? 0);
            $q3_qty = (float) ($sheet->getCell('M' . $rowNum)->getCalculatedValue() ?? 0);
            $q3 = (float) ($sheet->getCell('N' . $rowNum)->getCalculatedValue() ?? 0);
            $q4_qty = (float) ($sheet->getCell('O' . $rowNum)->getCalculatedValue() ?? 0);
            $q4 = (float) ($sheet->getCell('P' . $rowNum)->getCalculatedValue() ?? 0);

            $data[] = [
                'index' => $index++,
                'hash' => $hash,
                'level' => $level,
                'parent_hash' => $parentHash,
                'program' => $program,
                'total_budget' => $total_budget,
                'total_quantity' => $total_budget_qty,
                'total_expense' => $total_expense,
                'completed_quantity' => $total_expense_qty,
                'planned_budget' => $planned_budget,
                'planned_quantity' => $planned_budget_qty,
                'q1' => $q1,
                'q1_quantity' => $q1_qty,
                'q2' => $q2,
                'q2_quantity' => $q2_qty,
                'q3' => $q3,
                'q3_quantity' => $q3_qty,
                'q4' => $q4,
                'q4_quantity' => $q4_qty,
                'expenditure_id' => $expenditureId,
            ];
        }

        // Sort for tree order
        usort($data, fn(array $a, array $b) => strcmp($a['hash'], $b['hash']));

        return $data;
    }

    private function validateExcelData(array $data): void
    {
        if (empty($data)) {
            return;
        }

        $errors = [];
        $hashToIndex = array_column($data, 'index', 'hash');
        $hashToChildren = []; // Group children by parent hash for efficient sum calc

        // Pre-build children map
        foreach ($data as $row) {
            if ($row['parent_hash']) {
                $hashToChildren[$row['parent_hash']][] = $row;
            }
        }

        foreach ($data as $row) {
            // Quarter sum for amounts
            $quarterAmountSum = $row['q1'] + $row['q2'] + $row['q3'] + $row['q4'];
            if (abs($row['planned_budget'] - $quarterAmountSum) > 0.01) {
                $errors[] = "Row #{$row['hash']}: Planned Budget ({$row['planned_budget']}) must equal Q1+Q2+Q3+Q4 ({$quarterAmountSum}).";
            }

            // Quarter sum for quantities
            $quarterQtySum = $row['q1_quantity'] + $row['q2_quantity'] + $row['q3_quantity'] + $row['q4_quantity'];
            if (abs($row['planned_quantity'] - $quarterQtySum) > 0.01) {
                $errors[] = "Row #{$row['hash']}: Planned Quantity ({$row['planned_quantity']}) must equal Q1+Q2+Q3+Q4 quantities ({$quarterQtySum}).";
            }

            // Non-negative for amounts
            $amountFields = ['total_budget', 'total_expense', 'planned_budget', 'q1', 'q2', 'q3', 'q4'];
            foreach ($amountFields as $field) {
                if ($row[$field] < 0) {
                    $errors[] = "Row #{$row['hash']}: {$field} cannot be negative ({$row[$field]}).";
                }
            }

            // Non-negative for quantities
            $qtyFields = ['total_quantity', 'completed_quantity', 'planned_quantity', 'q1_quantity', 'q2_quantity', 'q3_quantity', 'q4_quantity'];
            foreach ($qtyFields as $field) {
                if ($row[$field] < 0) {
                    $errors[] = "Row #{$row['hash']}: {$field} cannot be negative ({$row[$field]}).";
                }
            }

            // Required program
            if (empty(trim($row['program']))) {
                $errors[] = "Row #{$row['hash']}: Program name is required.";
            }

            // Parent existence (for non-top-level)
            if ($row['level'] > 0 && !isset($hashToIndex[$row['parent_hash']])) {
                $errors[] = "Row #{$row['hash']}: Invalid parent #{$row['parent_hash']} (not found).";
            }
        }


        // Parent-child sums for amounts (only for rows WITH children)
        foreach ($hashToChildren as $parentHash => $children) {
            $parentRow = current(array_filter($data, fn(array $r) => $r['hash'] === $parentHash));
            if (!$parentRow) {
                continue;
            }

            $childrenAmountSum = array_reduce($children, fn(array $carry, array $child) => [
                'total_budget' => $carry['total_budget'] + $child['total_budget'],
                'total_expense' => $carry['total_expense'] + $child['total_expense'],
                'planned_budget' => $carry['planned_budget'] + $child['planned_budget'],
                'q1' => $carry['q1'] + $child['q1'],
                'q2' => $carry['q2'] + $child['q2'],
                'q3' => $carry['q3'] + $child['q3'],
                'q4' => $carry['q4'] + $child['q4'],
            ], ['total_budget' => 0, 'total_expense' => 0, 'planned_budget' => 0, 'q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0]);

            $amountFields = ['total_budget', 'total_expense', 'planned_budget', 'q1', 'q2', 'q3', 'q4'];
            foreach ($amountFields as $field) {
                if (abs($parentRow[$field] - $childrenAmountSum[$field]) > 0.01) {
                    $errors[] = "Row #{$parentRow['hash']}: {$field} ({$parentRow[$field]}) must equal sum of children ({$childrenAmountSum[$field]}).";
                }
            }

            // Parent-child sums for quantities
            $childrenQtySum = array_reduce($children, fn(array $carry, array $child) => [
                'total_quantity' => $carry['total_quantity'] + $child['total_quantity'],
                'completed_quantity' => $carry['completed_quantity'] + $child['completed_quantity'],
                'planned_quantity' => $carry['planned_quantity'] + $child['planned_quantity'],
                'q1_quantity' => $carry['q1_quantity'] + $child['q1_quantity'],
                'q2_quantity' => $carry['q2_quantity'] + $child['q2_quantity'],
                'q3_quantity' => $carry['q3_quantity'] + $child['q3_quantity'],
                'q4_quantity' => $carry['q4_quantity'] + $child['q4_quantity'],
            ], ['total_quantity' => 0, 'completed_quantity' => 0, 'planned_quantity' => 0, 'q1_quantity' => 0, 'q2_quantity' => 0, 'q3_quantity' => 0, 'q4_quantity' => 0]);

            $qtyFields = ['total_quantity', 'completed_quantity', 'planned_quantity', 'q1_quantity', 'q2_quantity', 'q3_quantity', 'q4_quantity'];
            foreach ($qtyFields as $field) {
                if (abs($parentRow[$field] - $childrenQtySum[$field]) > 0.01) {
                    $errors[] = "Row #{$parentRow['hash']}: {$field} ({$parentRow[$field]}) must equal sum of children ({$childrenQtySum[$field]}).";
                }
            }
        }

        // Max depth
        $maxLevel = max(array_column($data, 'level'));
        if ($maxLevel > 2) {
            $errors[] = "Maximum hierarchy depth is 2 (found level {$maxLevel}).";
        }

        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors));
        }
    }

    private function insertHierarchicalData(array $data, int $projectId, int $fiscalYearId): void
    {
        if (empty($data)) {
            return;
        }

        $hashToId = [];
        $expenditureId = $data[0]['expenditure_id'];

        // Create definitions and plans
        foreach ($data as $row) {
            // Create/update definition (fixed)
            $parentDefId = null;
            if ($row['parent_hash']) {
                $parentDefId = $hashToId[$row['parent_hash']]['definition_id'] ?? null;
            }

            $program = trim($row['program']);
            $existingDef = ProjectActivityDefinition::where('project_id', $projectId)
                ->where('program', $program)
                ->where('status', 'active')
                ->first();

            $definitionData = [
                'project_id' => $projectId,
                'program' => $program,
                'expenditure_id' => $expenditureId,
                'description' => null, // From Excel if available
                'status' => 'active',
                'parent_id' => $parentDefId,
            ];

            if ($existingDef) {
                if ($existingDef->expenditure_id !== $expenditureId || $existingDef->parent_id !== $parentDefId) {
                    throw new \Exception("Conflicting definition for program '{$program}' in Excel.");
                }
                $definition = $existingDef;
            } else {
                $definition = ProjectActivityDefinition::create($definitionData);
            }

            // Create/update plan (variable)
            $defId = $definition->id;
            $planData = [
                'activity_definition_id' => $defId,
                'fiscal_year_id' => $fiscalYearId,
                'total_budget' => $row['total_budget'],
                'total_quantity' => $row['total_quantity'],
                'total_expense' => $row['total_expense'],
                'completed_quantity' => $row['completed_quantity'],
                'planned_budget' => $row['planned_budget'],
                'planned_quantity' => $row['planned_quantity'],
                'q1_amount' => $row['q1'],
                'q1_quantity' => $row['q1_quantity'],
                'q2_amount' => $row['q2'],
                'q2_quantity' => $row['q2_quantity'],
                'q3_amount' => $row['q3'],
                'q3_quantity' => $row['q3_quantity'],
                'q4_amount' => $row['q4'],
                'q4_quantity' => $row['q4_quantity'],
            ];

            $existingPlan = ProjectActivityPlan::where('activity_definition_id', $defId)
                ->where('fiscal_year_id', $fiscalYearId)
                ->first();

            if ($existingPlan) {
                $existingPlan->update($planData);
            } else {
                ProjectActivityPlan::create($planData);
            }

            $hashToId[$row['hash']] = ['definition_id' => $defId];
        }
    }

    public function downloadActivities(int $projectId, int $fiscalYearId): Response
    {
        abort_if(Gate::denies('projectActivity_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        // Check access (reuse your logic)
        if (!$project->users->contains(Auth::user()->id)) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }

        // Sanitize titles for safe filename (remove/replace invalid chars like / \ : * ? " < > |)
        $safeProjectTitle = preg_replace('/[\/\\\\:*?"<>|]/', '_', $project->title);
        $safeFiscalYearTitle = preg_replace('/[\/\\\\:*?"<>|]/', '_', $fiscalYear->title);

        // Optional: Further slugify for readability (replaces spaces with -, limits length)
        $slugProjectTitle = $project->title;
        $slugFiscalYearTitle = Str::slug($safeFiscalYearTitle);

        $filename = 'AnnualProgram_' . $slugProjectTitle . '_' . $slugFiscalYearTitle . '.xlsx';

        // Export as single combined sheet (update export to use new models if needed)
        return Excel::download(
            new ProjectActivityExport($projectId, $fiscalYearId, $project, $fiscalYear),
            $filename
        );
    }
}
