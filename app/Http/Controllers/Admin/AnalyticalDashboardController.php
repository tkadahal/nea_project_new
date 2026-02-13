<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\Status;
use App\Models\Project;
use App\Models\Priority;
use Illuminate\View\View;
use App\Models\Department;
use App\Models\Directorate;
use App\Exports\TasksExport;
use Illuminate\Http\Request;
use App\Models\ProjectExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;

class AnalyticalDashboardController extends Controller
{
    /**
     * Main analytics dashboard with management insights
     */
    public function taskAnalytics(Request $request): View|JsonResponse
    {
        try {
            $user = Auth::user();
            $roles = $user->roles->pluck('id')->toArray();

            // Load lookup data once
            $lookupData = $this->getCachedLookupData();

            // Get base query with role-based filtering
            $baseQuery = $this->buildTaskQuery($user, $roles, $request);

            // ============================================
            // CRITICAL OPTIMIZATION: Fetch tasks ONCE
            // ============================================
            $tasks = $baseQuery->clone()
                ->with([
                    'priority' => fn($q) => $q->select('id', 'title'),
                    'status' => fn($q) => $q->select('id', 'title'),
                    'projects' => function ($q) {
                        $q->select('projects.id', 'projects.title', 'projects.directorate_id')
                            ->withPivot('status_id', 'progress');
                    },
                    'users' => fn($q) => $q->select('id', 'name'),
                    'directorate' => fn($q) => $q->select('id', 'title'),
                    'department' => fn($q) => $q->select('id', 'title'),
                ])
                ->get();

            // Transform once
            $projectTasks = $this->transformTasksToProjectTasks($tasks);

            // SECTION 1: Executive Summary (uses transformed data)
            $executiveSummary = $this->getExecutiveSummary($projectTasks, $lookupData);

            // SECTION 2: Directorate Performance (DB query - can't avoid)
            $directoratePerformance = $this->getDirectoratePerformance($user, $roles, $request);

            // SECTION 3: Project Risk Analysis (DB query - separate entity)
            $projectRisk = $this->getProjectRiskAnalysis($user, $roles, $request);

            // SECTION 4: Trend Analysis (DB query - aggregated)
            $trendData = $this->getTrendAnalysis($user, $roles);

            // SECTION 5: Priority & Status Distribution (uses transformed data)
            $distributionData = $this->getDistributionData($projectTasks, $lookupData);

            // SECTION 6: Alerts & Action Items (uses original tasks)
            $alerts = $this->getAlerts($tasks, $lookupData);

            // SECTION 7: Team Performance (DB query - aggregated)
            $teamPerformance = $this->getTeamPerformance($user, $roles);

            // SECTION 8: Detailed Task List (uses transformed data)
            $detailedTasks = $this->getDetailedTaskList($projectTasks, $request, $lookupData);

            // Filter options
            $filterOptions = $this->getFilterOptions($roles);

            $data = [
                'executiveSummary' => $executiveSummary,
                'directoratePerformance' => $directoratePerformance,
                'projectRisk' => $projectRisk,
                'trendData' => $trendData,
                'distributionData' => $distributionData,
                'alerts' => $alerts,
                'teamPerformance' => $teamPerformance,
                'detailedTasks' => $detailedTasks,
                'filterOptions' => $filterOptions,
                'lookupData' => $lookupData,
            ];

            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json($data);
            }

            return view('admin.analytics.tasks-analytics', $data);
        } catch (\Exception $e) {
            Log::error('Task Analytics Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to load analytics data',
                    'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
                ], 500);
            }

            abort(500, 'Error loading analytics data');
        }
    }

    /**
     * SECTION 1: Executive Summary
     */
    private function getExecutiveSummary($projectTasks, $lookupData): array
    {
        $completedStatusId = $lookupData['completed_status_id'];
        $now = now();

        // Calculate metrics (no DB query!)
        $total = $projectTasks->count();
        $completed = $projectTasks->where('status_id', $completedStatusId)->count();

        // On Track: Not overdue and progress > 70%
        $onTrack = $projectTasks->filter(function ($pt) use ($completedStatusId, $now) {
            return $pt->status_id != $completedStatusId &&
                (!$pt->task_due_date || $pt->task_due_date->isFuture()) &&
                $pt->progress >= 70;
        })->count();

        // At Risk: Due within 7 days and progress < 70%
        $atRisk = $projectTasks->filter(function ($pt) use ($completedStatusId, $now) {
            return $pt->status_id != $completedStatusId &&
                $pt->task_due_date &&
                $pt->task_due_date->between($now, $now->copy()->addDays(7)) &&
                $pt->progress < 70;
        })->count();

        // Delayed: Past due date
        $delayed = $projectTasks->filter(function ($pt) use ($completedStatusId, $now) {
            return $pt->status_id != $completedStatusId &&
                $pt->task_due_date &&
                $pt->task_due_date->isPast();
        })->count();

        // Overall Health Score (0-100)
        $healthScore = $total > 0 ? round(
            ($onTrack / $total) * 60 +
                ($completed / $total) * 40
        ) : 0;

        return [
            'health_score' => $healthScore,
            'total_tasks' => $total,
            'on_track' => $onTrack,
            'at_risk' => $atRisk,
            'delayed' => $delayed,
            'completed' => $completed,
            'trends' => [
                'on_track' => 0,
                'at_risk' => 0,
                'delayed' => 0,
                'completed' => $projectTasks->where('status_id', $completedStatusId)
                    ->where('task_due_date', '>=', $now->copy()->subWeek())
                    ->count(),
            ]
        ];
    }

    /**
     * SECTION 2: Directorate Performance
     */
    private function getDirectoratePerformance($user, $roles, $request): array
    {
        $query = DB::table('tasks')
            ->select(
                'directorates.id',
                'directorates.title',
                DB::raw('COUNT(DISTINCT tasks.id) as total_tasks'),
                DB::raw('AVG(CAST(COALESCE(project_task.progress, \'0\') AS NUMERIC)) as avg_progress'),
                DB::raw("SUM(CASE WHEN tasks.due_date < NOW() AND statuses.title != 'Completed' THEN 1 ELSE 0 END) as overdue_count"),
                DB::raw("SUM(CASE WHEN statuses.title = 'Completed' THEN 1 ELSE 0 END) as completed_count"),
                DB::raw("SUM(CASE WHEN tasks.due_date BETWEEN NOW() AND (NOW() + INTERVAL '7 days') AND CAST(COALESCE(project_task.progress, '0') AS NUMERIC) < 70 THEN 1 ELSE 0 END) as at_risk_count")
            )
            ->join('directorates', 'tasks.directorate_id', '=', 'directorates.id')
            ->leftJoin('project_task', 'tasks.id', '=', 'project_task.task_id')
            ->leftJoin('statuses', function ($join) {
                $join->on(DB::raw('COALESCE(project_task.status_id, tasks.status_id)'), '=', 'statuses.id');
            });

        // Apply role-based filtering
        if (!in_array(Role::SUPERADMIN, $roles) && !in_array(Role::ADMIN, $roles)) {
            if (in_array(Role::DIRECTORATE_USER, $roles) && $user->directorate_id) {
                $query->where('tasks.directorate_id', $user->directorate_id);
            }
        }

        $directorates = $query
            ->groupBy('directorates.id', 'directorates.title')
            ->orderByDesc('avg_progress')
            ->get()
            ->map(function ($dir) {
                $health = $this->calculateDirectorateHealth(
                    $dir->avg_progress,
                    $dir->overdue_count,
                    $dir->total_tasks
                );

                return [
                    'id' => $dir->id,
                    'title' => $dir->title,
                    'total_tasks' => $dir->total_tasks,
                    'avg_progress' => round((float) $dir->avg_progress, 1),
                    'completed_count' => $dir->completed_count,
                    'overdue_count' => $dir->overdue_count,
                    'at_risk_count' => $dir->at_risk_count,
                    'health_status' => $health['status'],
                    'health_color' => $health['color'],
                    'alert_message' => $this->getDirectorateAlertMessage($dir),
                ];
            });

        return $directorates->toArray();
    }

    /**
     * SECTION 3: Project Risk Analysis
     */
    private function getProjectRiskAnalysis($user, $roles, $request): array
    {
        $query = Project::with(['priority:id,title', 'status:id,title', 'directorate:id,title'])
            ->select('projects.*');

        // Apply role-based filtering
        if (!in_array(Role::SUPERADMIN, $roles) && !in_array(Role::ADMIN, $roles)) {
            if (in_array(Role::DIRECTORATE_USER, $roles) && $user->directorate_id) {
                $query->where('directorate_id', $user->directorate_id);
            } elseif (in_array(Role::PROJECT_USER, $roles)) {
                $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
            }
        }

        $projects = $query->get()->map(function ($project) {
            $isHighPriority = in_array($project->priority?->title, ['Urgent', 'High']);
            $progress = (float) $project->progress;
            $isBehind = $progress < 70 || ($project->end_date && $project->end_date->isPast());

            // Determine quadrant
            if ($isHighPriority && $isBehind) {
                $quadrant = 'critical';
                $color = '#EF4444'; // red
            } elseif ($isHighPriority && !$isBehind) {
                $quadrant = 'good';
                $color = '#10B981'; // green
            } elseif (!$isHighPriority && $isBehind) {
                $quadrant = 'watch';
                $color = '#F59E0B'; // yellow
            } else {
                $quadrant = 'ok';
                $color = '#6B7280'; // gray
            }

            return [
                'id' => $project->id,
                'title' => $project->title,
                'directorate' => $project->directorate?->title,
                'priority' => $project->priority?->title ?? 'N/A',
                'progress' => $progress,
                'status' => $project->status?->title ?? 'N/A',
                'quadrant' => $quadrant,
                'color' => $color,
                'days_remaining' => $project->end_date ? $project->end_date->diffInDays(now(), false) : null,
            ];
        });

        return [
            'critical' => $projects->where('quadrant', 'critical')->values()->toArray(),
            'good' => $projects->where('quadrant', 'good')->values()->toArray(),
            'watch' => $projects->where('quadrant', 'watch')->values()->toArray(),
            'ok' => $projects->where('quadrant', 'ok')->values()->toArray(),
        ];
    }

    /**
     * SECTION 4: Trend Analysis (6 months)
     */
    private function getTrendAnalysis($user, $roles): array
    {
        $query = DB::table('tasks')
            ->select(
                DB::raw("TO_CHAR(tasks.created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as created'),
                DB::raw('SUM(CASE WHEN tasks.completion_date IS NOT NULL THEN 1 ELSE 0 END) as completed'),
                DB::raw('AVG(CAST(COALESCE(project_task.progress, \'0\') AS NUMERIC)) as avg_progress')
            )
            ->leftJoin('project_task', 'tasks.id', '=', 'project_task.task_id')
            ->where('tasks.created_at', '>=', now()->subMonths(6));

        // Apply role-based filtering
        if (!in_array(Role::SUPERADMIN, $roles) && !in_array(Role::ADMIN, $roles)) {
            if (in_array(Role::DIRECTORATE_USER, $roles) && Auth::user()->directorate_id) {
                $query->where('tasks.directorate_id', Auth::user()->directorate_id);
            }
        }

        $trends = $query
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'labels' => $trends->pluck('month')->map(fn($m) => date('M Y', strtotime($m . '-01')))->toArray(),
            'created' => $trends->pluck('created')->toArray(),
            'completed' => $trends->pluck('completed')->toArray(),
            'avg_progress' => $trends->pluck('avg_progress')->map(fn($p) => round((float) $p, 1))->toArray(),
        ];
    }

    /**
     * SECTION 5: Distribution Data
     */
    private function getDistributionData($projectTasks, $lookupData): array
    {
        // Priority distribution (no DB query!)
        $priorityDist = $lookupData['priorities']->map(function ($priority) use ($projectTasks) {
            return [
                'label' => $priority->title,
                'count' => $projectTasks->where('task_priority_title', $priority->title)->count(),
                'color' => $lookupData['priority_colors'][$priority->title] ?? '#6B7280',
            ];
        });

        // Status distribution (no DB query!)
        $statusDist = $lookupData['statuses']->map(function ($status) use ($projectTasks) {
            return [
                'label' => $status->title,
                'count' => $projectTasks->where('status_id', $status->id)->count(),
                'color' => $status->color,
            ];
        });

        return [
            'priority' => $priorityDist->toArray(),
            'status' => $statusDist->toArray(),
        ];
    }

    /**
     * SECTION 6: Alerts & Action Items
     */
    private function getAlerts($tasks, $lookupData): array
    {
        $completedStatusId = $lookupData['completed_status_id'];
        $now = now();

        // Filter in memory (no additional DB queries!)
        $critical = $tasks->filter(function ($task) use ($completedStatusId, $now) {
            $isNotCompleted = $task->status_id != $completedStatusId ||
                $task->projects->contains(fn($p) => $p->pivot->status_id != $completedStatusId);

            return $isNotCompleted && $task->due_date && $task->due_date->isPast();
        })
            ->sortBy('due_date')
            ->take(5)
            ->map(fn($task) => $this->formatAlertTask($task, 'critical'));

        // Warning: Due soon with low progress
        $warning = $tasks->filter(function ($task) use ($completedStatusId, $now) {
            $isNotCompleted = $task->status_id != $completedStatusId ||
                $task->projects->contains(fn($p) => $p->pivot->status_id != $completedStatusId);

            return $isNotCompleted &&
                $task->due_date &&
                $task->due_date->between($now, $now->copy()->addDays(7));
        })
            ->sortBy('due_date')
            ->take(5)
            ->map(fn($task) => $this->formatAlertTask($task, 'warning'));

        // Notable: Recently completed ahead of schedule
        $notable = $tasks->filter(function ($task) use ($completedStatusId, $now) {
            return $task->status_id == $completedStatusId &&
                $task->completion_date &&
                $task->completion_date < $task->due_date &&
                $task->completion_date >= $now->copy()->subDays(7);
        })
            ->sortByDesc('completion_date')
            ->take(3)
            ->map(fn($task) => $this->formatAlertTask($task, 'notable'));

        return [
            'critical' => $critical->values()->toArray(),
            'warning' => $warning->values()->toArray(),
            'notable' => $notable->values()->toArray(),
        ];
    }

    /**
     * SECTION 7: Team Performance
     */
    private function getTeamPerformance($user, $roles): array
    {
        $query = DB::table('task_user')
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(DISTINCT tasks.id) as task_count'),
                DB::raw('AVG(CAST(COALESCE(project_task.progress, \'0\') AS NUMERIC)) as avg_progress'),
                DB::raw("SUM(CASE WHEN tasks.due_date < NOW() AND statuses.title != 'Completed' THEN 1 ELSE 0 END) as overdue_count"),
                DB::raw("SUM(CASE WHEN statuses.title = 'Completed' THEN 1 ELSE 0 END) as completed_count")
            )
            ->join('users', 'task_user.user_id', '=', 'users.id')
            ->join('tasks', 'task_user.task_id', '=', 'tasks.id')
            ->leftJoin('project_task', 'tasks.id', '=', 'project_task.task_id')
            ->leftJoin('statuses', function ($join) {
                $join->on(DB::raw('COALESCE(project_task.status_id, tasks.status_id)'), '=', 'statuses.id');
            });

        // ============================================
        // ADDED: Role-based filtering for Team Performance
        // ============================================
        if (!in_array(Role::SUPERADMIN, $roles) && !in_array(Role::ADMIN, $roles)) {
            if (in_array(Role::DIRECTORATE_USER, $roles) && $user->directorate_id) {
                // Restrict to users assigned to tasks in this directorate
                $query->where('tasks.directorate_id', $user->directorate_id);
            } elseif (in_array(Role::PROJECT_USER, $roles)) {
                // Restrict to users assigned to tasks within my assigned projects
                $myProjectIds = $user->projects()->pluck('id');
                if ($myProjectIds->isNotEmpty()) {
                    $query->whereExists(function ($subQuery) use ($myProjectIds) {
                        $subQuery->select(DB::raw(1))
                            ->from('project_task')
                            ->whereColumn('project_task.task_id', 'tasks.id')
                            ->whereIn('project_task.project_id', $myProjectIds);
                    });
                }
            } elseif (in_array(Role::DEPARTMENT_USER, $roles) && $user->directorate_id) {
                // Restrict to users assigned to tasks in this department/directorate
                $departmentIds = Department::whereHas('directorates', fn($q) => $q->where('directorates.id', $user->directorate_id))->pluck('id');
                $query->where('tasks.directorate_id', $user->directorate_id);
            } else {
                // Generic user: Only show my own stats
                $query->where('users.id', $user->id);
            }
        }

        $directorates = $query
            ->groupBy('users.id', 'users.name')
            ->havingRaw('COUNT(DISTINCT tasks.id) > 0')
            ->get();

        $team = $directorates->map(function ($member) {
            $isOverloaded = $member->task_count > 30;
            $isUnderperforming = $member->avg_progress < 60 || $member->overdue_count > 3;

            return [
                'id' => $member->id,
                'name' => $member->name,
                'task_count' => $member->task_count,
                'avg_progress' => round((float) $member->avg_progress, 1),
                'overdue_count' => $member->overdue_count,
                'completed_count' => $member->completed_count,
                'needs_support' => $isOverloaded || $isUnderperforming,
                'is_top_performer' => $member->avg_progress >= 85 && $member->overdue_count == 0,
            ];
        });

        return [
            'top_performers' => $team->where('is_top_performer', true)->take(4)->values()->toArray(),
            'needs_support' => $team->where('needs_support', true)->take(4)->values()->toArray(),
            'all' => $team->sortByDesc('avg_progress')->values()->toArray(),
        ];
    }

    /**
     * SECTION 8: Detailed Task List
     */
    private function getDetailedTaskList($projectTasks, Request $request, $lookupData): array
    {
        if ($request->filled('status_id')) {
            $projectTasks = $projectTasks->filter(fn($pt) => $pt->status_id == $request->status_id);
        }

        $paginatedData = $this->paginateCollection($projectTasks, $request);

        $tableData = $this->formatTableData($paginatedData['items'], $lookupData);

        return [
            'tasks' => $paginatedData['paginator'],
            'tableData' => $tableData,
        ];
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    private function getCachedLookupData(): array
    {
        return Cache::remember('analytics_lookup_data', 300, function () {
            $statuses = Status::all();
            $priorities = Priority::all();

            return [
                'statuses' => $statuses,
                'priorities' => $priorities,
                'status_colors' => $statuses->pluck('color', 'id')->toArray(),
                'priority_colors' => [
                    'Urgent' => '#EF4444',
                    'High' => '#F59E0B',
                    'Medium' => '#10B981',
                    'Low' => '#6B7280',
                ],
                'completed_status_id' => $statuses->firstWhere('title', 'Completed')?->id,
            ];
        });
    }

    private function buildTaskQuery($user, array $roles, Request $request)
    {
        $query = Task::query();

        // Role-based filtering
        if (in_array(Role::SUPERADMIN, $roles) || in_array(Role::ADMIN, $roles)) {
            if ($request->filled('directorate_id')) {
                $query->where('directorate_id', $request->directorate_id);
            }
        } elseif (in_array(Role::DIRECTORATE_USER, $roles) && $user->directorate_id) {
            $query->where('directorate_id', $user->directorate_id);
        } elseif (in_array(Role::DEPARTMENT_USER, $roles) && $user->directorate_id) {
            $departmentIds = Department::whereHas('directorates', fn($q) => $q->where('directorates.id', $user->directorate_id))
                ->pluck('id');
            if ($departmentIds->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('department_id', $departmentIds);
            }
        } elseif (in_array(Role::PROJECT_USER, $roles)) {
            $projectIds = $user->projects()->pluck('id');
            if ($projectIds->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('projects', fn($q) => $q->whereIn('projects.id', $projectIds));
            }
        } else {
            $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
        }

        // Additional filters
        if ($request->filled('project_id')) {
            $query->whereHas('projects', fn($q) => $q->where('id', $request->project_id));
        }
        if ($request->filled('priority_id')) {
            $query->where('priority_id', $request->priority_id);
        }

        return $query;
    }

    private function transformTasksToProjectTasks($tasks)
    {
        return $tasks->flatMap(function ($task) {
            $taskProjects = $task->projects->map(function ($project) use ($task) {
                return (object) [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'task_due_date' => $task->due_date,
                    'task_priority_id' => $task->priority_id,
                    'task_priority_title' => $task->priority?->title,
                    'task_users' => $task->users,
                    'project_id' => $project->id,
                    'project_title' => $project->title,
                    'status_id' => $project->status_id ?? $task->status_id,
                    'progress' => $project->progress ?? 0,
                    'entity' => $project->title,
                ];
            });

            if ($taskProjects->isEmpty() && ($task->directorate_id || $task->department_id)) {
                $taskProjects->push((object) [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'task_due_date' => $task->due_date,
                    'task_priority_id' => $task->priority_id,
                    'task_priority_title' => $task->priority?->title,
                    'task_users' => $task->users,
                    'project_id' => null,
                    'project_title' => null,
                    'status_id' => $task->status_id,
                    'progress' => 0, // Tasks without projects have no progress
                    'entity' => $task->department?->title ?? $task->directorate?->title ?? 'N/A',
                ]);
            }

            return $taskProjects;
        })->filter(fn($pt) => !is_null($pt->status_id));
    }

    private function calculateDirectorateHealth($avgProgress, $overdueCount, $totalTasks): array
    {
        $overdueRatio = $totalTasks > 0 ? ($overdueCount / $totalTasks) * 100 : 0;

        if ($avgProgress >= 90 && $overdueRatio < 5) {
            return ['status' => 'Excellent', 'color' => '#10B981'];
        } elseif ($avgProgress >= 75 && $overdueRatio < 10) {
            return ['status' => 'Good', 'color' => '#10B981'];
        } elseif ($avgProgress >= 60 && $overdueRatio < 20) {
            return ['status' => 'Fair', 'color' => '#F59E0B'];
        } elseif ($avgProgress >= 45 && $overdueRatio < 30) {
            return ['status' => 'At Risk', 'color' => '#F59E0B'];
        } else {
            return ['status' => 'Critical', 'color' => '#EF4444'];
        }
    }

    private function getDirectorateAlertMessage($dir): string
    {
        if ($dir->overdue_count > 0) {
            return "⚠️ {$dir->overdue_count} task" . ($dir->overdue_count > 1 ? 's' : '') . " overdue";
        } elseif ($dir->at_risk_count > 0) {
            return "⚠️ {$dir->at_risk_count} task" . ($dir->at_risk_count > 1 ? 's' : '') . " at risk";
        } elseif ($dir->avg_progress >= 90) {
            return "⭐ Excellent performance!";
        } else {
            return "✓ On track";
        }
    }

    private function formatAlertTask($task, $type): array
    {
        $project = $task->projects->first();
        $progress = $project ? ($project->progress ?? 0) : 0;
        $daysOverdue = $task->due_date ? $task->due_date->diffInDays(now(), false) : 0;

        if ($type === 'critical') {
            $message = "{$task->title} - " . abs($daysOverdue) . " days overdue";
        } elseif ($type === 'warning') {
            $message = "{$task->title} - Due in {$daysOverdue} days, " . round($progress) . "% complete";
        } else {
            $message = "{$task->title} - Completed " . abs($daysOverdue) . " days early!";
        }

        return [
            'id' => $task->id,
            'message' => $message,
            'directorate' => $task->directorate?->title ?? 'N/A',
            'project' => $project?->title ?? 'N/A',
            'priority' => $task->priority?->title ?? 'N/A',
        ];
    }

    private function getLastWeekComparison($baseQuery, $lookupData): array
    {
        // Get last week's data for trend comparison
        $lastWeek = $baseQuery->clone()
            ->where('created_at', '>=', now()->subWeek())
            ->get();

        $lastWeekProjectTasks = $this->transformTasksToProjectTasks($lastWeek);

        return [
            'on_track_diff' => 0, // Simplified for now
            'at_risk_diff' => 0,
            'delayed_diff' => 0,
            'completed_diff' => $lastWeekProjectTasks->where('status_id', $lookupData['completed_status_id'])->count(),
        ];
    }

    private function paginateCollection($collection, Request $request): array
    {
        $perPage = 10;
        $currentPage = $request->input('page', 1);
        $items = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $paginator->links_html = $paginator->links()->toHtml();

        return [
            'items' => $items,
            'paginator' => $paginator,
        ];
    }

    private function formatTableData($items, array $lookupData): array
    {
        return $items->map(function ($pt) use ($lookupData) {
            $status = $lookupData['statuses']->firstWhere('id', $pt->status_id);

            return [
                'id' => $pt->task_id,
                'title' => $pt->task_title,
                'entity' => $pt->entity,
                'status' => [
                    'title' => $status?->title ?? 'N/A',
                    'color' => $status?->color ?? 'gray'
                ],
                'priority' => [
                    'title' => $pt->task_priority_title ?? 'N/A',
                    'color' => $lookupData['priority_colors'][$pt->task_priority_title] ?? 'gray'
                ],
                'due_date' => $pt->task_due_date ? $pt->task_due_date->format('Y-m-d') : 'N/A',
                'progress' => round($pt->progress, 1),
                'users' => $pt->task_users->map(fn($user) => [
                    'initials' => $this->getInitials($user->name),
                    'name' => $user->name,
                ])->toArray(),
                'project_id' => $pt->project_id,
            ];
        })->values()->toArray();
    }

    private function getFilterOptions(array $roles): array
    {
        $options = [];

        if (in_array(Role::SUPERADMIN, $roles) || in_array(Role::ADMIN, $roles)) {
            $options['directorates'] = Directorate::select('id', 'title')->get();
        } else {
            $options['directorates'] = collect();
        }

        $options['projects'] = Project::select('id', 'title', 'directorate_id')->get();

        return $options;
    }

    private function getInitials(string $name): string
    {
        $words = explode(' ', trim($name));
        return collect($words)
            ->map(fn($word) => strtoupper($word[0] ?? ''))
            ->take(2)
            ->implode('');
    }

    public function exportTaskAnalytics(Request $request)
    {
        try {
            $user = Auth::user();
            $roles = $user->roles->pluck('id')->toArray();

            $query = $this->buildTaskQuery($user, $roles, $request);

            $query->with([
                'projects:id,title,directorate_id',
                'priority:id,title',
                'users:id,name',
                'directorate:id,title',
                'department:id,title',
            ]);

            return Excel::download(
                new TasksExport($query),
                'tasks_analytics_' . now()->format('Y-m-d_H-i-s') . '.csv'
            );
        } catch (\Exception $e) {
            Log::error('Task Export Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to export tasks');
        }
    }

    /**
     * Project Analytics with Portfolio Management Insights
     * OPTIMIZED: Zero duplicate queries
     */
    public function projectAnalytics(Request $request): View|JsonResponse
    {
        try {
            $user = Auth::user();
            $roles = $user->roles->pluck('id')->toArray();

            // ============================================
            // CRITICAL: Load lookup data ONCE at the start
            // ============================================
            $lookupData = $this->getCachedLookupData();

            // Build base query
            $baseQuery = $this->buildProjectQuery($user, $roles, $request);

            // ============================================
            // OPTIMIZATION: Fetch Data Once with proper eager loading
            // ============================================
            $dashboardProjects = $baseQuery->clone()
                ->with([
                    'status' => fn($q) => $q->select('id', 'title', 'color'),
                    'priority' => fn($q) => $q->select('id', 'title'),
                    'directorate' => fn($q) => $q->select('id', 'title'),
                    'contracts' => fn($q) => $q->select('id', 'project_id', 'contract_amount'),
                    'budgets' => fn($q) => $q->latest()->limit(1),
                    'tasks' => fn($q) => $q->select('id', 'title', 'completion_date'),
                ])
                ->get();

            $this->hydrateApprovedExpenses($dashboardProjects);

            // SECTION 1: Portfolio Health Score
            $portfolioHealth = $this->getPortfolioHealth($dashboardProjects, $lookupData);

            // SECTION 2: Budget vs Progress Matrix
            $budgetProgressMatrix = $this->getBudgetProgressMatrix($dashboardProjects);

            // SECTION 3: Directorate Project Performance
            $directorateProjectPerformance = $this->getDirectorateProjectPerformance($dashboardProjects, $lookupData);

            // SECTION 4: Financial Health Dashboard
            $financialHealth = $this->getFinancialHealth($dashboardProjects);

            // SECTION 5: Project Alerts & Action Items
            $projectAlerts = $this->getProjectAlerts($dashboardProjects, $lookupData);

            // SECTION 6: Resource Allocation (Tasks vs Contracts)
            $resourceAllocation = $this->getResourceAllocation($dashboardProjects);

            // SECTION 7: Charts (Derived from dashboard projects)
            $charts = [
                'progress' => $this->getProgressChart($dashboardProjects),
                'task_contract' => $this->getTaskContractChart($dashboardProjects),
            ];

            // SECTION 8: Detailed Project List (Separate query due to pagination)
            $detailedProjects = $this->getDetailedProjectList($baseQuery, $request);

            // Filter options
            $filterOptions = $this->getProjectFilterOptions($roles);

            $data = [
                'portfolioHealth' => $portfolioHealth,
                'budgetProgressMatrix' => $budgetProgressMatrix,
                'directorateProjectPerformance' => $directorateProjectPerformance,
                'financialHealth' => $financialHealth,
                'projectAlerts' => $projectAlerts,
                'resourceAllocation' => $resourceAllocation,
                'detailedProjects' => $detailedProjects,
                'filterOptions' => $filterOptions,
                'directorates' => $filterOptions['directorates'],
                'departments' => $filterOptions['departments'],
                'statuses' => $lookupData['statuses'],
                'priorities' => $lookupData['priorities'],
                'projects' => $detailedProjects['paginated'],
                'summary' => $portfolioHealth['summary'],
                'charts' => $charts,
            ];

            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json($data);
            }

            return view('admin.analytics.projects-analytics', $data);
        } catch (\Exception $e) {
            Log::error('Project Analytics Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to load analytics data',
                    'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
                ], 500);
            }

            abort(500, 'Error loading analytics data');
        }
    }

    // ========================================
    // PROJECT ANALYTICS HELPER METHODS
    // ========================================

    /**
     * Hydrate approved expenses using Eloquent relationships
     */
    private function hydrateApprovedExpenses(Collection $projects)
    {
        foreach ($projects as $project) {
            $contractSum = $project->contracts->sum('contract_amount');

            $project->setAttribute('total_approved_expense', $contractSum);
            $project->setAttribute(
                'financial_progress',
                $project->total_budget > 0 ? round(($contractSum / $project->total_budget) * 100, 2) : 0
            );
        }
    }

    private function buildProjectQuery($user, array $roles, Request $request)
    {
        $query = Project::query()->whereNull('deleted_at');

        if (in_array(Role::SUPERADMIN, $roles) || in_array(Role::ADMIN, $roles)) {
            if ($request->filled('directorate_id')) {
                $query->where('directorate_id', $request->directorate_id);
            }
        } elseif (in_array(Role::DIRECTORATE_USER, $roles) && $user->directorate_id) {
            $query->where('directorate_id', $user->directorate_id);
        } elseif (in_array(Role::PROJECT_USER, $roles)) {
            $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('status_id')) {
            $query->where('status_id', $request->status_id);
        }
        if ($request->filled('priority_id')) {
            $query->where('priority_id', $request->priority_id);
        }

        return $query;
    }

    /**
     * OPTIMIZED: Uses cached status lookup
     */
    private function getPortfolioHealth(Collection $projects, array $lookupData): array
    {
        $completedStatusId = $lookupData['completed_status_id'];
        $now = now();

        $total = $projects->count();
        $completed = $projects->where('status_id', $completedStatusId)->count();

        $metrics = $projects->map(function ($project) use ($completedStatusId, $now) {
            $isCompleted = $project->status_id == $completedStatusId;
            $progress = (float) ($project->progress ?? 0);
            $totalBudget = (float) $project->total_budget;

            $spent = $project->total_approved_expense;
            $budgetUtilization = $totalBudget > 0 ? ($spent / $totalBudget) * 100 : 0;

            $onTrack = !$isCompleted &&
                (!$project->end_date || $project->end_date->isFuture()) &&
                $budgetUtilization < 90 &&
                $progress >= 50;

            $daysRemaining = $project->end_date ? $project->end_date->diffInDays($now, false) : 999;
            $atRisk = !$isCompleted &&
                (($daysRemaining > 0 && $daysRemaining <= 30 && $progress < 70) ||
                    ($budgetUtilization > 85 && $budgetUtilization <= 95));

            $delayed = !$isCompleted &&
                (($project->end_date && $project->end_date->isPast()) ||
                    $budgetUtilization > 100);

            $onBudget = !$isCompleted && $budgetUtilization < 80;

            return [
                'on_track' => $onTrack,
                'at_risk' => $atRisk,
                'delayed' => $delayed,
                'on_budget' => $onBudget,
            ];
        });

        $onTrack = $metrics->where('on_track', true)->count();
        $atRisk = $metrics->where('at_risk', true)->count();
        $delayed = $metrics->where('delayed', true)->count();
        $onBudget = $metrics->where('on_budget', true)->count();

        $healthScore = $total > 0 ? round(
            ($onTrack / $total) * 40 +
                ($onBudget / $total) * 30 +
                ($completed / $total) * 20 +
                ((1 - ($delayed / max($total, 1))) * 10)
        ) : 0;

        $avgProgress = $projects->avg('progress') ?? 0;
        $overdueProjects = $projects->filter(fn($p) => $p->end_date && $p->end_date->isPast() && $p->status_id != $completedStatusId)->count();

        return [
            'health_score' => $healthScore,
            'total_projects' => $total,
            'on_track' => $onTrack,
            'at_risk' => $atRisk,
            'delayed' => $delayed,
            'on_budget' => $onBudget,
            'completed' => $completed,
            'summary' => [
                'total_projects' => $total,
                'completed_projects' => $completed,
                'overdue_projects' => $overdueProjects,
                'average_progress' => round($avgProgress, 1),
            ],
        ];
    }

    private function getBudgetProgressMatrix(Collection $projects): array
    {
        $mappedProjects = $projects->map(function ($project) {
            $totalBudget = (float) $project->total_budget;
            $spent = $project->total_approved_expense;
            $budgetSpent = $totalBudget > 0 ? ($spent / $totalBudget) * 100 : 0;
            $progress = (float) ($project->progress ?? 0);

            if ($budgetSpent > 75 && $progress < 60) {
                $quadrant = 'critical';
                $color = '#EF4444';
            } elseif ($budgetSpent > 75 && $progress >= 60) {
                $quadrant = 'watch';
                $color = '#F59E0B';
            } elseif ($budgetSpent <= 75 && $progress < 60) {
                $quadrant = 'slow';
                $color = '#F59E0B';
            } else {
                $quadrant = 'excellent';
                $color = '#10B981';
            }

            return [
                'id' => $project->id,
                'title' => $project->title,
                'directorate' => $project->directorate?->title ?? 'N/A',
                'budget_spent' => round($budgetSpent, 1),
                'progress' => round($progress, 1),
                'quadrant' => $quadrant,
                'color' => $color,
            ];
        });

        return [
            'critical' => $mappedProjects->where('quadrant', 'critical')->values()->toArray(),
            'watch' => $mappedProjects->where('quadrant', 'watch')->values()->toArray(),
            'slow' => $mappedProjects->where('quadrant', 'slow')->values()->toArray(),
            'excellent' => $mappedProjects->where('quadrant', 'excellent')->values()->toArray(),
            'all' => $mappedProjects->toArray(),
        ];
    }

    /**
     * OPTIMIZED: Uses cached status lookup
     */
    private function getDirectorateProjectPerformance(Collection $projects, array $lookupData): array
    {
        $now = now();
        $completedStatusId = $lookupData['completed_status_id'];

        $grouped = $projects->groupBy('directorate_id');

        return $grouped->map(function ($dirProjects, $directorateId) use ($now, $completedStatusId) {
            $total = $dirProjects->count();
            $totalBudget = $dirProjects->sum(fn($p) => $p->total_budget);
            $totalSpent = $dirProjects->sum(fn($p) => $p->total_approved_expense);

            $budgetUtilization = $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;
            $completedCount = $dirProjects->where('status_id', $completedStatusId)->count();

            $overdueCount = $dirProjects->filter(function ($p) use ($now, $completedStatusId) {
                return $p->status_id != $completedStatusId &&
                    $p->end_date &&
                    $p->end_date->isPast();
            })->count();

            $budgetHealth = $budgetUtilization < 70 ? 'Good' : ($budgetUtilization < 90 ? 'Fair' : 'Critical');
            $budgetColor = $budgetUtilization < 70 ? '#10B981' : ($budgetUtilization < 90 ? '#F59E0B' : '#EF4444');

            return [
                'id' => $directorateId,
                'title' => $dirProjects->first()->directorate?->title ?? 'Unknown',
                'total_projects' => $total,
                'avg_progress' => round($dirProjects->avg('progress') ?? 0, 1),
                'completed_count' => $completedCount,
                'overdue_count' => $overdueCount,
                'budget_utilization' => round($budgetUtilization, 1),
                'budget_health' => $budgetHealth,
                'budget_color' => $budgetColor,
                'alert_message' => $overdueCount > 0 ?
                    "⚠️ {$overdueCount} project" . ($overdueCount > 1 ? 's' : '') . " overdue" : (round($dirProjects->avg('progress') ?? 0) >= 85 ? "⭐ Excellent delivery" : "✓ On track"),
            ];
        })->values()->toArray();
    }

    private function getFinancialHealth(Collection $projects): array
    {
        $totalBudget = $projects->sum('total_budget');
        $totalSpent = $projects->sum('total_approved_expense');
        $totalCommitted = $projects->sum(fn($p) => $p->contracts->where('status', '!=', 'completed')->sum('contract_amount'));
        $available = max(0, $totalBudget - $totalSpent - $totalCommitted);

        $spentPercentage = $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;
        $committedPercentage = $totalBudget > 0 ? ($totalCommitted / $totalBudget) * 100 : 0;
        $availablePercentage = $totalBudget > 0 ? ($available / $totalBudget) * 100 : 0;

        $criticalProjects = $projects->filter(function ($p) {
            $totalBudget = (float) $p->total_budget;
            $spent = $p->total_approved_expense;
            $budgetUtilization = $totalBudget > 0 ? ($spent / $totalBudget) * 100 : 0;
            $progress = (float) ($p->progress ?? 0);
            return $budgetUtilization > 95 && $progress < 80;
        })->count();

        $warningProjects = $projects->filter(function ($p) {
            $totalBudget = (float) $p->total_budget;
            $spent = $p->total_approved_expense;
            $budgetUtilization = $totalBudget > 0 ? ($spent / $totalBudget) * 100 : 0;
            $progress = (float) ($p->progress ?? 0);
            return $budgetUtilization > 85 && $budgetUtilization <= 95 && $progress < 90;
        })->count();

        return [
            'total_budget' => $totalBudget,
            'spent' => $totalSpent,
            'committed' => $totalCommitted,
            'available' => $available,
            'spent_percentage' => round($spentPercentage, 1),
            'committed_percentage' => round($committedPercentage, 1),
            'available_percentage' => round($availablePercentage, 1),
            'critical_projects' => $criticalProjects,
            'warning_projects' => $warningProjects,
            'healthy_projects' => $projects->count() - $criticalProjects - $warningProjects,
            'monthly_burn_rate' => 0,
        ];
    }

    /**
     * OPTIMIZED: Uses cached status lookup
     */
    private function getProjectAlerts(Collection $projects, array $lookupData): array
    {
        $completedStatusId = $lookupData['completed_status_id'];
        $now = now();

        $activeProjects = $projects->where('status_id', '!=', $completedStatusId);

        $critical = $activeProjects->filter(function ($p) use ($now) {
            $totalBudget = (float) $p->total_budget;
            $spent = $p->total_approved_expense;
            $budgetUtilization = $totalBudget > 0 ? ($spent / $totalBudget) * 100 : 0;
            $isOverdue = $p->end_date && $p->end_date->isPast();
            return $budgetUtilization > 95 || $isOverdue || $budgetUtilization > 100;
        })->take(5)->map(fn($p) => $this->formatProjectAlert($p, 'critical'));

        $warning = $activeProjects->filter(function ($p) use ($now) {
            $totalBudget = (float) $p->total_budget;
            $spent = $p->total_approved_expense;
            $budgetUtilization = $totalBudget > 0 ? ($spent / $totalBudget) * 100 : 0;
            $daysRemaining = $p->end_date ? $p->end_date->diffInDays($now, false) : 999;
            $progress = (float) ($p->progress ?? 0);
            return ($daysRemaining > 0 && $daysRemaining <= 14 && $progress < 80) ||
                ($budgetUtilization > 85 && $budgetUtilization <= 95);
        })->take(5)->map(fn($p) => $this->formatProjectAlert($p, 'warning'));

        $notable = $activeProjects->filter(function ($p) use ($now) {
            $progress = (float) ($p->progress ?? 0);
            $daysRemaining = $p->end_date ? $p->end_date->diffInDays($now, false) : 0;
            return $progress >= 90 && $daysRemaining > 7;
        })->take(3)->map(fn($p) => $this->formatProjectAlert($p, 'notable'));

        return [
            'critical' => $critical->toArray(),
            'warning' => $warning->toArray(),
            'notable' => $notable->toArray(),
        ];
    }

    private function getResourceAllocation(Collection $projects): array
    {
        $totalTasks = $projects->sum(fn($p) => $p->tasks->count());
        $completedTasks = $projects->sum(fn($p) => $p->tasks->whereNotNull('completion_date')->count());
        $totalContracts = $projects->sum(fn($p) => $p->contracts->count());
        $totalContractValue = $projects->sum(fn($p) => $p->contracts->sum('contract_amount'));

        return [
            'tasks_count' => $totalTasks,
            'tasks_completed' => $completedTasks,
            'contracts_count' => $totalContracts,
            'contracts_value' => $totalContractValue,
        ];
    }

    private function getProgressChart(Collection $projects): array
    {
        $topProjects = $projects->take(10);

        return [
            'labels' => $topProjects->pluck('title')->toArray(),
            'physical' => $topProjects->pluck('progress')->toArray(),
            'financial' => $topProjects->pluck('financial_progress')->toArray(),
        ];
    }

    private function getTaskContractChart(Collection $projects): array
    {
        $tasksCount = $projects->sum(fn($p) => $p->tasks->count());
        $contractsCount = $projects->sum(fn($p) => $p->contracts->count());

        return [
            'labels' => ['Tasks', 'Contracts'],
            'data' => [$tasksCount, $contractsCount],
        ];
    }

    private function getDetailedProjectList($baseQuery, Request $request): array
    {
        $projects = $baseQuery->clone()
            ->with([
                'directorate' => fn($q) => $q->select('id', 'title'),
                'status' => fn($q) => $q->select('id', 'title', 'color'),
                'priority' => fn($q) => $q->select('id', 'title'),
                'contracts' => fn($q) => $q->select('id', 'project_id', 'contract_amount'),
                'budgets' => fn($q) => $q->latest()->limit(1),
            ])
            ->paginate(10);

        // Hydrate expenses for the paginated list
        $this->hydrateApprovedExpenses($projects->getCollection());

        $projects->getCollection()->transform(function ($project) {
            $totalBudget = (float) $project->total_budget;
            $spent = $project->total_approved_expense;
            $budgetUtilization = $totalBudget > 0 ? ($spent / $totalBudget) * 100 : 0;

            $budgetOverrun = max(0, $budgetUtilization - 100) / 10;
            $scheduleDelay = $project->end_date && $project->end_date->isPast() ?
                min(5, $project->end_date->diffInDays(now()) / 10) : 0;
            $progressLag = max(0, (100 - (float)($project->progress ?? 0)) / 20);
            $riskScore = min(10, round($budgetOverrun + $scheduleDelay + $progressLag, 1));

            $project->tableData = [
                'risk_score' => $riskScore,
                'budget_utilization' => round($budgetUtilization, 1),
            ];
            return $project;
        });

        return [
            'paginated' => $projects,
            'tableData' => $projects->map(fn($p) => $p->tableData)->toArray(),
        ];
    }

    private function getProjectFilterOptions(array $roles): array
    {
        $options = [];
        if (in_array(Role::SUPERADMIN, $roles) || in_array(Role::ADMIN, $roles)) {
            $options['directorates'] = Directorate::select('id', 'title')->get();
        } else {
            $options['directorates'] = collect();
        }
        $options['departments'] = Department::select('id', 'title')->get();
        return $options;
    }

    private function formatProjectAlert($project, $type): array
    {
        $totalBudget = (float) $project->total_budget;
        $spent = $project->total_approved_expense;
        $budgetUtilization = $totalBudget > 0 ? ($spent / $totalBudget) * 100 : 0;
        $progress = (float) ($project->progress ?? 0);
        $daysRemaining = $project->end_date ? $project->end_date->diffInDays(now(), false) : 0;

        if ($type === 'critical') {
            if ($budgetUtilization > 100) {
                $message = "{$project->title} - Budget exceeded by $" . number_format($spent - $totalBudget, 0);
            } elseif ($daysRemaining < 0) {
                $message = "{$project->title} - " . abs($daysRemaining) . " days overdue";
            } else {
                $message = "{$project->title} - " . round($budgetUtilization) . "% budget, " . round($progress) . "% progress";
            }
        } elseif ($type === 'warning') {
            $message = "{$project->title} - Due in " . abs($daysRemaining) . " days, " . round($progress) . "% complete";
        } else {
            $message = "{$project->title} - Excellent performance! ⭐";
        }

        return [
            'id' => $project->id,
            'message' => $message,
            'directorate' => $project->directorate?->title ?? 'N/A',
        ];
    }
}
