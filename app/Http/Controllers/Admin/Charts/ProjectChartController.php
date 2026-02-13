<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Charts;

use App\Models\Role;
use App\Models\Project;
use App\Models\FiscalYear;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\ProjectExpense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\ProjectActivityPlan;
use App\Models\ProjectActivityDefinition;

class ProjectChartController extends Controller
{
    /**
     * Main charts dashboard
     */
    public function index(Request $request): View
    {
        try {
            $user = Auth::user();
            $roles = $user->roles->pluck('id')->toArray();

            // Get all chart data
            $ganttData = $this->getGanttData($user, $roles, $request);
            $quarterlyComparison = $this->getPortfolioQuarterlyComparison($user, $roles);
            $burnRateData = $this->getPortfolioBurnRate($user, $roles);
            $progressSummary = $this->getProgressSummary($user, $roles);
            $topProjects = $this->getTopProjectsByBudget($user, $roles);

            return view('admin.charts.project-charts', [
                'ganttData' => $ganttData,
                'quarterlyComparison' => $quarterlyComparison,
                'burnRateData' => $burnRateData,
                'progressSummary' => $progressSummary,
                'topProjects' => $topProjects,
            ]);
        } catch (\Exception $e) {
            Log::error('Project Charts Error: ' . $e->getMessage());
            abort(500, 'Error loading charts data');
        }
    }

    /**
     * Single project detail with all charts
     */
    public function show(Request $request, $projectId): View
    {
        try {
            $project = Project::with(['directorate', 'status', 'priority'])->findOrFail($projectId);

            return view('admin.charts.project-detail-chart', [
                'project' => $project,
                'sCurveData' => $this->getSCurveData($projectId),
                'quarterlyComparison' => $this->getQuarterlyComparison($projectId),
                'burnRateData' => $this->getMonthlyBurnRate($projectId),
                'activityHeatmap' => $this->getActivityHeatmap($projectId),
                'progressHistory' => $this->getProgressHistory($projectId),
            ]);
        } catch (\Exception $e) {
            Log::error('Project Detail Charts Error: ' . $e->getMessage());
            abort(500, 'Error loading project charts');
        }
    }

    // ========================================
    // PORTFOLIO CHARTS (All Projects)
    // ========================================

    /**
     * CHART 1: Gantt Chart Data
     */
    private function getGanttData($user, array $roles, Request $request): array
    {
        $query = Project::query()
            ->with(['status:id,title,color', 'directorate:id,title'])
            ->whereNull('deleted_at')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date');

        // Apply role filtering
        if (!in_array(Role::SUPERADMIN, $roles) && !in_array(Role::ADMIN, $roles)) {
            if (in_array(Role::DIRECTORATE_USER, $roles) && $user->directorate_id) {
                $query->where('directorate_id', $user->directorate_id);
            } elseif (in_array(Role::PROJECT_USER, $roles)) {
                $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
            }
        }

        // Filter by directorate if provided
        if ($request->filled('directorate_id')) {
            $query->where('directorate_id', $request->directorate_id);
        }

        $projects = $query->orderBy('start_date')->get()->map(function ($project) {
            $now = now();
            $start = $project->start_date;
            $end = $project->end_date;

            // Calculate time progress
            $totalDays = $start->diffInDays($end) ?: 1;
            $elapsedDays = max(0, $start->diffInDays(min($now, $end)));
            $timeProgress = ($elapsedDays / $totalDays) * 100;

            // Determine status
            $progress = (float) ($project->progress ?? 0);
            $isOnTrack = $progress >= ($timeProgress - 10); // 10% tolerance
            $isDelayed = $end->isPast() && $progress < 100;

            $status = $isDelayed ? 'delayed' : ($isOnTrack ? 'on-track' : 'behind');
            $color = $isDelayed ? '#EF4444' : ($isOnTrack ? '#10B981' : '#F59E0B');

            return [
                'id' => $project->id,
                'name' => $project->title,
                'directorate' => $project->directorate?->title ?? 'N/A',
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'progress' => round($progress, 1),
                'time_progress' => round($timeProgress, 1),
                'status' => $status,
                'status_label' => ucfirst(str_replace('-', ' ', $status)),
                'color' => $color,
                'days_remaining' => $end->isFuture() ? $end->diffInDays($now) : 0,
                'days_elapsed' => $elapsedDays,
                'total_days' => $totalDays,
            ];
        });

        return $projects->values()->toArray();
    }

    /**
     * CHART 2: Portfolio Quarterly Comparison
     */
    private function getPortfolioQuarterlyComparison($user, array $roles): array
    {
        $currentFiscalYear = FiscalYear::where('active', true)->first();

        if (!$currentFiscalYear) {
            return [
                'labels' => ['Q1', 'Q2', 'Q3', 'Q4'],
                'planned' => [0, 0, 0, 0],
                'actual' => [0, 0, 0, 0],
            ];
        }

        // Get all projects for the user
        $projectIds = $this->getAccessibleProjectIds($user, $roles);

        // Get planned amounts
        $plans = ProjectActivityPlan::whereHas('definitionVersion', function ($q) use ($projectIds) {
            $q->whereIn('project_id', $projectIds)->where('is_current', true);
        })
            ->where('fiscal_year_id', $currentFiscalYear->id)
            ->get();

        $plannedQuarterly = [
            'Q1' => $plans->sum('q1_amount'),
            'Q2' => $plans->sum('q2_amount'),
            'Q3' => $plans->sum('q3_amount'),
            'Q4' => $plans->sum('q4_amount'),
        ];

        // Get actual amounts
        $actualQuarterly = ['Q1' => 0, 'Q2' => 0, 'Q3' => 0, 'Q4' => 0];

        $expenses = ProjectExpense::whereHas('plan.definitionVersion', function ($q) use ($projectIds) {
            $q->whereIn('project_id', $projectIds)->where('is_current', true);
        })
            ->with(['quarters' => fn($q) => $q->where('status', 'finalized')])
            ->get();

        foreach ($expenses as $expense) {
            foreach ($expense->quarters as $quarter) {
                $quarterLabel = 'Q' . $quarter->quarter;
                if (isset($actualQuarterly[$quarterLabel])) {
                    $actualQuarterly[$quarterLabel] += (float) $quarter->amount;
                }
            }
        }

        return [
            'labels' => array_keys($plannedQuarterly),
            'planned' => array_values(array_map(fn($v) => round($v, 2), $plannedQuarterly)),
            'actual' => array_values(array_map(fn($v) => round($v, 2), $actualQuarterly)),
        ];
    }

    /**
     * CHART 3: Portfolio Monthly Burn Rate
     */
    private function getPortfolioBurnRate($user, array $roles): array
    {
        $projectIds = $this->getAccessibleProjectIds($user, $roles);

        // Get expenses from last 12 months
        $startDate = now()->subMonths(12)->startOfMonth();

        $expenses = ProjectExpense::whereHas('plan.definitionVersion', function ($q) use ($projectIds) {
            $q->whereIn('project_id', $projectIds)->where('is_current', true);
        })
            ->where('effective_date', '>=', $startDate)
            ->with(['quarters' => fn($q) => $q->where('status', 'finalized')])
            ->get();

        // Group by month
        $monthlySpending = [];

        foreach ($expenses as $expense) {
            if ($expense->effective_date) {
                $month = $expense->effective_date->format('Y-m');
                $total = $expense->quarters->sum('amount');

                if (!isset($monthlySpending[$month])) {
                    $monthlySpending[$month] = 0;
                }
                $monthlySpending[$month] += (float) $total;
            }
        }

        // Fill missing months with 0
        $current = $startDate->copy();
        $end = now();
        while ($current <= $end) {
            $monthKey = $current->format('Y-m');
            if (!isset($monthlySpending[$monthKey])) {
                $monthlySpending[$monthKey] = 0;
            }
            $current->addMonth();
        }

        ksort($monthlySpending);

        return [
            'labels' => array_map(fn($m) => date('M Y', strtotime($m . '-01')), array_keys($monthlySpending)),
            'data' => array_values(array_map(fn($v) => round($v, 2), $monthlySpending)),
        ];
    }

    /**
     * CHART 4: Progress Summary (Donut Chart)
     */
    private function getProgressSummary($user, array $roles): array
    {
        $query = Project::query()->whereNull('deleted_at');

        if (!in_array(Role::SUPERADMIN, $roles) && !in_array(Role::ADMIN, $roles)) {
            if (in_array(Role::DIRECTORATE_USER, $roles) && $user->directorate_id) {
                $query->where('directorate_id', $user->directorate_id);
            } elseif (in_array(Role::PROJECT_USER, $roles)) {
                $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
            }
        }

        $projects = $query->get();

        $notStarted = $projects->where('progress', '<=', 0)->count();
        $inProgress = $projects->where('progress', '>', 0)->where('progress', '<', 100)->count();
        $completed = $projects->where('progress', '>=', 100)->count();

        return [
            'labels' => ['Not Started', 'In Progress', 'Completed'],
            'data' => [$notStarted, $inProgress, $completed],
            'colors' => ['#6B7280', '#3B82F6', '#10B981'],
        ];
    }

    /**
     * Top Projects by Budget
     */
    private function getTopProjectsByBudget($user, array $roles): array
    {
        $query = Project::query()
            ->with(['directorate:id,title', 'budgets' => fn($q) => $q->latest('id')->limit(1)])
            ->whereNull('deleted_at');

        if (!in_array(Role::SUPERADMIN, $roles) && !in_array(Role::ADMIN, $roles)) {
            if (in_array(Role::DIRECTORATE_USER, $roles) && $user->directorate_id) {
                $query->where('directorate_id', $user->directorate_id);
            } elseif (in_array(Role::PROJECT_USER, $roles)) {
                $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
            }
        }

        $projects = $query->get()
            ->sortByDesc(fn($p) => $p->total_budget)
            ->take(10)
            ->map(function ($project) {
                return [
                    'title' => $project->title,
                    'budget' => $project->total_budget,
                ];
            });

        return [
            'labels' => $projects->pluck('title')->toArray(),
            'data' => $projects->pluck('budget')->map(fn($v) => round($v, 2))->toArray(),
            'ids' => $projects->pluck('id')->toArray(),
        ];
    }

    // ========================================
    // SINGLE PROJECT CHARTS
    // ========================================

    /**
     * S-Curve: Planned vs Actual Cumulative
     */
    private function getSCurveData($projectId): array
    {
        $currentFiscalYear = FiscalYear::where('active', true)->first();

        if (!$currentFiscalYear) {
            return [
                'labels' => ['Q1', 'Q2', 'Q3', 'Q4'],
                'planned' => [0, 0, 0, 0],
                'actual' => [0, 0, 0, 0],
                'total_budget' => 0,
            ];
        }

        $project = Project::findOrFail($projectId);

        // Get planned amounts
        $plans = ProjectActivityPlan::whereHas('definitionVersion', function ($q) use ($projectId) {
            $q->where('project_id', $projectId)->where('is_current', true);
        })
            ->where('fiscal_year_id', $currentFiscalYear->id)
            ->get();

        $plannedQuarterly = [
            'Q1' => $plans->sum('q1_amount'),
            'Q2' => $plans->sum('q2_amount'),
            'Q3' => $plans->sum('q3_amount'),
            'Q4' => $plans->sum('q4_amount'),
        ];

        // Calculate cumulative planned
        $cumulativePlanned = [];
        $cumSum = 0;
        foreach ($plannedQuarterly as $quarter => $amount) {
            $cumSum += (float) $amount;
            $cumulativePlanned[$quarter] = round($cumSum, 2);
        }

        // Get actual expenses
        $actualQuarterly = ['Q1' => 0, 'Q2' => 0, 'Q3' => 0, 'Q4' => 0];

        $expenses = ProjectExpense::whereHas('plan.definitionVersion', function ($q) use ($projectId) {
            $q->where('project_id', $projectId)->where('is_current', true);
        })
            ->with(['quarters' => fn($q) => $q->where('status', 'finalized')])
            ->get();

        foreach ($expenses as $expense) {
            foreach ($expense->quarters as $quarter) {
                $quarterLabel = 'Q' . $quarter->quarter;
                if (isset($actualQuarterly[$quarterLabel])) {
                    $actualQuarterly[$quarterLabel] += (float) $quarter->amount;
                }
            }
        }

        // Calculate cumulative actual
        $cumulativeActual = [];
        $cumSum = 0;
        foreach ($actualQuarterly as $quarter => $amount) {
            $cumSum += $amount;
            $cumulativeActual[$quarter] = round($cumSum, 2);
        }

        return [
            'labels' => array_keys($plannedQuarterly),
            'planned' => array_values($cumulativePlanned),
            'actual' => array_values($cumulativeActual),
            'total_budget' => round($project->total_budget, 2),
        ];
    }

    /**
     * Quarterly Comparison for Single Project
     */
    private function getQuarterlyComparison($projectId): array
    {
        $currentFiscalYear = FiscalYear::where('active', true)->first();

        if (!$currentFiscalYear) {
            return [];
        }

        $plans = ProjectActivityPlan::whereHas('definitionVersion', function ($q) use ($projectId) {
            $q->where('project_id', $projectId)->where('is_current', true);
        })
            ->where('fiscal_year_id', $currentFiscalYear->id)
            ->get();

        $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
        $comparison = [];

        foreach ($quarters as $index => $quarter) {
            $quarterNum = $index + 1;
            $field = 'q' . $quarterNum . '_amount';

            $planned = $plans->sum($field);

            $actual = ProjectExpense::whereHas('plan.definitionVersion', function ($q) use ($projectId) {
                $q->where('project_id', $projectId)->where('is_current', true);
            })
                ->with(['quarters' => fn($q) => $q->where('status', 'finalized')->where('quarter', $quarterNum)])
                ->get()
                ->sum(fn($e) => $e->quarters->sum('amount'));

            $variance = (float) $actual - (float) $planned;
            $variancePercent = $planned > 0 ? ($variance / $planned) * 100 : 0;

            $comparison[] = [
                'quarter' => $quarter,
                'planned' => round($planned, 2),
                'actual' => round($actual, 2),
                'variance' => round($variance, 2),
                'variance_percent' => round($variancePercent, 1),
            ];
        }

        return $comparison;
    }

    /**
     * Monthly Burn Rate for Single Project
     */
    private function getMonthlyBurnRate($projectId): array
    {
        $startDate = now()->subMonths(12)->startOfMonth();

        $expenses = ProjectExpense::whereHas('plan.definitionVersion', function ($q) use ($projectId) {
            $q->where('project_id', $projectId)->where('is_current', true);
        })
            ->where('effective_date', '>=', $startDate)
            ->with(['quarters' => fn($q) => $q->where('status', 'finalized')])
            ->get();

        $monthlySpending = [];

        foreach ($expenses as $expense) {
            if ($expense->effective_date) {
                $month = $expense->effective_date->format('Y-m');
                $total = $expense->quarters->sum('amount');

                if (!isset($monthlySpending[$month])) {
                    $monthlySpending[$month] = 0;
                }
                $monthlySpending[$month] += (float) $total;
            }
        }

        ksort($monthlySpending);

        return [
            'labels' => array_map(fn($m) => date('M Y', strtotime($m . '-01')), array_keys($monthlySpending)),
            'data' => array_values(array_map(fn($v) => round($v, 2), $monthlySpending)),
        ];
    }

    /**
     * Activity Heatmap
     */
    private function getActivityHeatmap($projectId): array
    {
        $activities = ProjectActivityDefinition::where('project_id', $projectId)
            ->where('is_current', true)
            ->get();

        $heatmapData = $activities->map(function ($activity) {
            $expenses = ProjectExpense::whereHas('plan', function ($q) use ($activity) {
                $q->where('activity_definition_version_id', $activity->id);
            })
                ->with(['quarters' => fn($q) => $q->where('status', 'finalized')])
                ->get();

            $totalSpent = $expenses->sum(fn($e) => $e->quarters->sum('amount'));
            $plannedBudget = (float) $activity->total_budget;
            $utilization = $plannedBudget > 0 ? ($totalSpent / $plannedBudget) * 100 : 0;

            return [
                'name' => $activity->description,
                'program' => $activity->program,
                'budget' => round($plannedBudget, 2),
                'spent' => round($totalSpent, 2),
                'utilization' => round($utilization, 1),
                'color' => $utilization > 100 ? '#EF4444' : ($utilization > 80 ? '#F59E0B' : '#10B981'),
            ];
        });

        return $heatmapData->sortByDesc('utilization')->values()->toArray();
    }

    /**
     * Progress History
     */
    private function getProgressHistory($projectId): array
    {
        // Simplified: Return quarterly progress based on spending
        $project = Project::findOrFail($projectId);
        $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];

        $data = [];
        $cumulativeSpent = 0;
        $totalBudget = $project->total_budget;

        foreach ($quarters as $index => $quarter) {
            $quarterSpending = ProjectExpense::whereHas('plan.definitionVersion', function ($q) use ($projectId) {
                $q->where('project_id', $projectId)->where('is_current', true);
            })
                ->with(['quarters' => fn($q) => $q->where('status', 'finalized')->where('quarter', $index + 1)])
                ->get()
                ->sum(fn($e) => $e->quarters->sum('amount'));

            $cumulativeSpent += (float) $quarterSpending;
            $financialProgress = $totalBudget > 0 ? ($cumulativeSpent / $totalBudget) * 100 : 0;

            $data[] = [
                'label' => $quarter,
                'financial_progress' => round($financialProgress, 1),
                'physical_progress' => round($project->progress, 1),
            ];
        }

        return $data;
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    private function getAccessibleProjectIds($user, array $roles): array
    {
        $query = Project::query()->whereNull('deleted_at');

        if (!in_array(Role::SUPERADMIN, $roles) && !in_array(Role::ADMIN, $roles)) {
            if (in_array(Role::DIRECTORATE_USER, $roles) && $user->directorate_id) {
                $query->where('directorate_id', $user->directorate_id);
            } elseif (in_array(Role::PROJECT_USER, $roles)) {
                $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
            }
        }

        return $query->pluck('id')->toArray();
    }
}
