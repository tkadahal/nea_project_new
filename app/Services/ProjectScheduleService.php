<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectActivitySchedule;
use App\Models\ProjectActivityDependency;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjectScheduleService
{
    /**
     * Smart dependency sync - respects hierarchy and phases
     */
    public function syncDependencies(int $projectId): void
    {
        DB::transaction(function () use ($projectId) {
            // 1. Clear ONLY auto-created dependencies (preserve manual ones)
            ProjectActivityDependency::where('project_id', $projectId)
                ->where('is_auto', true)
                ->delete();

            // 2. Get all schedules for this project through pivot
            $project = Project::with(['activitySchedules' => function ($q) {
                $q->select([
                    'project_activity_schedules.id',
                    'project_activity_schedules.code',
                    'project_activity_schedules.level',
                    'project_activity_schedules.parent_id',
                ])->withCount('children')
                    ->orderBy('code');
            }])->find($projectId);

            if (!$project || $project->activitySchedules->isEmpty()) {
                return;
            }

            $schedules = $project->activitySchedules;
            $dependencies = [];

            // 3. Group by phase (A, B, C, etc.)
            $phases = $schedules->groupBy(fn($s) => substr($s->code, 0, 1));

            foreach ($phases as $phaseCode => $phaseSchedules) {
                // Get top-level activities in this phase (e.g., A.1, A.2, A.3)
                $topLevelInPhase = $phaseSchedules->where('level', 2)
                    ->sortBy('code')
                    ->values();

                // Link top-level activities sequentially within phase
                for ($i = 0; $i < count($topLevelInPhase) - 1; $i++) {
                    $dependencies[] = [
                        'project_id' => $projectId,
                        'predecessor_id' => $topLevelInPhase[$i]->id,
                        'successor_id' => $topLevelInPhase[$i + 1]->id,
                        'type' => 'FS',
                        'lag_days' => 0,
                        'is_auto' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // For each parent, link its children sequentially
                foreach ($topLevelInPhase as $parent) {
                    $children = $phaseSchedules
                        ->where('parent_id', $parent->id)
                        ->sortBy('code')
                        ->values();

                    if ($children->count() > 1) {
                        for ($i = 0; $i < count($children) - 1; $i++) {
                            $dependencies[] = [
                                'project_id' => $projectId,
                                'predecessor_id' => $children[$i]->id,
                                'successor_id' => $children[$i + 1]->id,
                                'type' => 'FS',
                                'lag_days' => 0,
                                'is_auto' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }
            }

            // 4. Link phases together (A → B → C)
            $phaseLetters = $phases->keys()->sort()->values();
            for ($i = 0; $i < count($phaseLetters) - 1; $i++) {
                $currentPhase = $phases[$phaseLetters[$i]];
                $nextPhase = $phases[$phaseLetters[$i + 1]];

                // Last activity of current phase → First activity of next phase
                $lastOfCurrent = $currentPhase->where('level', 2)->sortByDesc('code')->first();
                $firstOfNext = $nextPhase->where('level', 2)->sortBy('code')->first();

                if ($lastOfCurrent && $firstOfNext) {
                    $dependencies[] = [
                        'project_id' => $projectId,
                        'predecessor_id' => $lastOfCurrent->id,
                        'successor_id' => $firstOfNext->id,
                        'type' => 'FS',
                        'lag_days' => 0,
                        'is_auto' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // 5. Bulk insert
            if (!empty($dependencies)) {
                ProjectActivityDependency::insert($dependencies);
            }
        });
    }

    /**
     * Ripple dates forward from a starting point
     */
    public function rippleDates(int $projectId, ?int $startFromScheduleId = null): void
    {
        DB::transaction(function () use ($projectId, $startFromScheduleId) {
            // Get all pivot assignments using DB facade
            $assignments = DB::table('project_schedule_assignments')
                ->where('project_id', $projectId)
                ->get()
                ->keyBy('schedule_id');

            if ($assignments->isEmpty()) {
                return;
            }

            // Get dependencies
            $dependencies = ProjectActivityDependency::where('project_id', $projectId)
                ->with(['predecessor', 'successor'])
                ->get()
                ->groupBy('successor_id');

            // Build dependency graph
            $graph = [];
            foreach ($dependencies as $successorId => $deps) {
                $graph[$successorId] = $deps->pluck('predecessor_id')->toArray();
            }

            // Topological sort to process in correct order
            $sortedIds = $this->topologicalSort($graph, $assignments->keys()->toArray());

            // If starting from specific activity, only process successors
            if ($startFromScheduleId) {
                $sortedIds = $this->getSuccessors($graph, $startFromScheduleId);
            }

            foreach ($sortedIds as $scheduleId) {
                $current = $assignments->get($scheduleId);

                if (!$current) continue;

                // Skip completed activities
                if ($current->progress >= 100) continue;

                // Get dependencies for this activity
                $activityDeps = $dependencies->get($scheduleId);
                if (!$activityDeps || $activityDeps->isEmpty()) continue;

                // Calculate earliest start based on all predecessors
                $earliestStart = null;

                foreach ($activityDeps as $dep) {
                    $pred = $assignments->get($dep->predecessor_id);
                    if (!$pred) continue;

                    // Calculate successor start based on dependency type
                    $predDate = $this->calculateSuccessorDate($pred, $dep);

                    if (!$earliestStart || $predDate->gt($earliestStart)) {
                        $earliestStart = $predDate;
                    }
                }

                if ($earliestStart) {
                    // Preserve original duration
                    $duration = Carbon::parse($current->start_date)->diffInDays($current->end_date);

                    // Update using DB facade
                    DB::table('project_schedule_assignments')
                        ->where('project_id', $projectId)
                        ->where('schedule_id', $scheduleId)
                        ->update([
                            'start_date' => $earliestStart->format('Y-m-d'),
                            'end_date' => $earliestStart->copy()->addDays($duration)->format('Y-m-d'),
                            'updated_at' => now(),
                        ]);
                }
            }
        });
    }

    /**
     * Calculate critical path
     */
    public function calculateCriticalPath(int $projectId): array
    {
        // Get all assignments using DB
        $assignments = DB::table('project_schedule_assignments')
            ->where('project_id', $projectId)
            ->get()
            ->keyBy('schedule_id');

        if ($assignments->isEmpty()) {
            return [
                'critical_activities' => [],
                'slacks' => [],
                'project_duration' => 0,
            ];
        }

        // Get all dependencies
        $dependencies = ProjectActivityDependency::where('project_id', $projectId)->get();
        $depsBySuccessor = $dependencies->groupBy('successor_id');

        // Forward pass - calculate Early Start (ES) and Early Finish (EF)
        $es = [];
        $ef = [];

        foreach ($assignments as $id => $assignment) {
            $maxPredEF = 0;

            $deps = $depsBySuccessor->get($id, collect());
            foreach ($deps as $dep) {
                if (isset($ef[$dep->predecessor_id])) {
                    $maxPredEF = max($maxPredEF, $ef[$dep->predecessor_id] + $dep->lag_days);
                }
            }

            $es[$id] = $maxPredEF;
            $duration = $assignment->start_date && $assignment->end_date
                ? Carbon::parse($assignment->start_date)->diffInDays($assignment->end_date)
                : 1;
            $ef[$id] = $es[$id] + $duration;
        }

        // Backward pass - calculate Late Start (LS) and Late Finish (LF)
        $projectEnd = !empty($ef) ? max($ef) : 0;
        $ls = [];
        $lf = [];

        foreach (array_reverse($assignments->keys()->toArray()) as $id) {
            $assignment = $assignments->get($id);

            // Find successors
            $minSuccLS = $projectEnd;
            $successorDeps = $dependencies->where('predecessor_id', $id);

            foreach ($successorDeps as $dep) {
                if (isset($ls[$dep->successor_id])) {
                    $minSuccLS = min($minSuccLS, $ls[$dep->successor_id] - $dep->lag_days);
                }
            }

            $lf[$id] = $minSuccLS;
            $duration = $assignment->start_date && $assignment->end_date
                ? Carbon::parse($assignment->start_date)->diffInDays($assignment->end_date)
                : 1;
            $ls[$id] = $lf[$id] - $duration;
        }

        // Calculate slack (float) and identify critical path
        $criticalPath = [];
        $slacks = [];

        foreach ($assignments as $id => $assignment) {
            $slack = ($ls[$id] ?? 0) - ($es[$id] ?? 0);
            $slacks[$id] = $slack;

            if ($slack == 0) {
                $criticalPath[] = $id;
            }
        }

        return [
            'critical_activities' => $criticalPath,
            'slacks' => $slacks,
            'project_duration' => $projectEnd,
            'early_start' => $es,
            'early_finish' => $ef,
            'late_start' => $ls,
            'late_finish' => $lf,
        ];
    }

    /**
     * Calculate successor start date based on dependency type
     */
    private function calculateSuccessorDate($predecessor, $dependency): Carbon
    {
        $baseDate = match ($dependency->type) {
            'FS' => $predecessor->actual_end_date ?? $predecessor->end_date,
            'SS' => $predecessor->actual_start_date ?? $predecessor->start_date,
            'FF' => $predecessor->actual_end_date ?? $predecessor->end_date,
            'SF' => $predecessor->actual_start_date ?? $predecessor->start_date,
            default => $predecessor->end_date,
        };

        return Carbon::parse($baseDate)->addDays($dependency->lag_days + 1);
    }

    /**
     * Topological sort for dependency order
     */
    private function topologicalSort(array $graph, array $allNodes): array
    {
        $sorted = [];
        $visited = [];

        $visit = function ($node) use (&$visit, &$sorted, &$visited, $graph) {
            if (isset($visited[$node])) return;
            $visited[$node] = true;

            if (isset($graph[$node])) {
                foreach ($graph[$node] as $dep) {
                    $visit($dep);
                }
            }

            $sorted[] = $node;
        };

        // Visit all nodes (including those without dependencies)
        foreach ($allNodes as $node) {
            $visit($node);
        }

        return array_reverse($sorted);
    }

    /**
     * Get all successors of an activity
     */
    private function getSuccessors(array $graph, int $startId): array
    {
        $successors = [];
        $queue = [$startId];

        while (!empty($queue)) {
            $current = array_shift($queue);
            $successors[] = $current;

            foreach ($graph as $node => $deps) {
                if (in_array($current, $deps) && !in_array($node, $successors) && !in_array($node, $queue)) {
                    $queue[] = $node;
                }
            }
        }

        return $successors;
    }
}
