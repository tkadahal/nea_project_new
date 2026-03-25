<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectActivityDependency;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectScheduleService
{
    public function syncDependencies(int $projectId): void
    {
        DB::transaction(function () use ($projectId) {
            ProjectActivityDependency::where('project_id', $projectId)
                ->where('is_auto', true)
                ->delete();

            $project = Project::with(['activitySchedules' => function ($q) {
                $q->select([
                    'project_activity_schedules.id',
                    'project_activity_schedules.code',
                    'project_activity_schedules.level',
                    'project_activity_schedules.parent_id',
                ])->withCount('children')
                    ->orderBy('code');
            }])->find($projectId);

            if (! $project || $project->activitySchedules->isEmpty()) {
                return;
            }

            $schedules = $project->activitySchedules;
            $dependencies = [];

            $phases = $schedules->groupBy(fn ($s) => substr($s->code, 0, 1));

            foreach ($phases as $phaseCode => $phaseSchedules) {
                $topLevelInPhase = $phaseSchedules->where('level', 2)
                    ->sortBy('code')
                    ->values();

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
            if (! empty($dependencies)) {
                ProjectActivityDependency::insert($dependencies);
            }
        });
    }

    /**
     * Ripple dates forward from a starting point
     */
    public function rippleDates(int $projectId, ?int $startFromScheduleId = null): void
    {
        $assignments = DB::table('project_schedule_assignments')
            ->where('project_id', $projectId)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('status', 'active')
            ->get()
            ->keyBy('schedule_id');

        // If no valid assignments, nothing to ripple
        if ($assignments->isEmpty()) {
            return;
        }

        // Get all dependencies for this project
        $dependencies = DB::table('project_activity_dependencies')
            ->where('project_id', $projectId)
            ->get();

        if ($dependencies->isEmpty()) {
            return; // No dependencies to process
        }

        // Build adjacency list for topological sort
        $graph = [];
        $inDegree = [];

        foreach ($assignments as $assignment) {
            $scheduleId = $assignment->schedule_id;
            if (! isset($graph[$scheduleId])) {
                $graph[$scheduleId] = [];
                $inDegree[$scheduleId] = 0;
            }
        }

        foreach ($dependencies as $dep) {
            if (! isset($assignments[$dep->predecessor_id]) || ! isset($assignments[$dep->successor_id])) {
                continue; // Skip if either schedule not in project or not active
            }

            $graph[$dep->predecessor_id][] = [
                'successor' => $dep->successor_id,
                'type' => $dep->type,
                'lag' => $dep->lag_days,
            ];

            if (! isset($inDegree[$dep->successor_id])) {
                $inDegree[$dep->successor_id] = 0;
            }
            $inDegree[$dep->successor_id]++;
        }

        // Topological sort (Kahn's algorithm)
        $queue = [];
        foreach ($inDegree as $scheduleId => $degree) {
            if ($degree === 0) {
                $queue[] = $scheduleId;
            }
        }

        $sorted = [];
        while (! empty($queue)) {
            $current = array_shift($queue);
            $sorted[] = $current;

            if (isset($graph[$current])) {
                foreach ($graph[$current] as $edge) {
                    $successor = $edge['successor'];
                    $inDegree[$successor]--;
                    if ($inDegree[$successor] === 0) {
                        $queue[] = $successor;
                    }
                }
            }
        }

        // If we should only ripple from a specific schedule, filter the sorted list
        if ($startFromScheduleId !== null) {
            $startIndex = array_search($startFromScheduleId, $sorted);
            if ($startIndex !== false) {
                $sorted = array_slice($sorted, $startIndex);
            } else {
                return; // Start schedule not found in dependency chain
            }
        }

        // Now ripple dates according to topological order
        foreach ($sorted as $scheduleId) {
            if (! isset($assignments[$scheduleId])) {
                continue;
            }

            $assignment = $assignments[$scheduleId];

            // Calculate current duration (in days, rounded to integer)
            try {
                $startDate = new \DateTime($assignment->start_date);
                $endDate = new \DateTime($assignment->end_date);
                $duration = max(0, (int) $startDate->diff($endDate)->days); // ✅ Use diff() and cast to int
            } catch (\Exception $e) {
                // Skip if dates are invalid
                continue;
            }

            // Check all predecessors to find the latest constraint
            $latestConstraintDate = null;

            foreach ($dependencies as $dep) {
                if ($dep->successor_id != $scheduleId) {
                    continue;
                }

                if (! isset($assignments[$dep->predecessor_id])) {
                    continue;
                }

                $predAssignment = $assignments[$dep->predecessor_id];

                // Validate predecessor dates
                if (! $predAssignment->start_date || ! $predAssignment->end_date) {
                    continue;
                }

                try {
                    $predStart = new \DateTime($predAssignment->start_date);
                    $predEnd = new \DateTime($predAssignment->end_date);
                } catch (\Exception $e) {
                    continue; // Skip invalid predecessor dates
                }

                // Calculate constraint date based on dependency type
                $constraintDate = null;
                switch ($dep->type) {
                    case 'FS': // Finish-to-Start (most common)
                        $constraintDate = clone $predEnd;
                        $lagDays = (int) $dep->lag_days; // ✅ Cast to int
                        if ($lagDays !== 0) {
                            $constraintDate->modify(($lagDays > 0 ? '+' : '').$lagDays.' days');
                        }
                        break;

                    case 'SS': // Start-to-Start
                        $constraintDate = clone $predStart;
                        $lagDays = (int) $dep->lag_days;
                        if ($lagDays !== 0) {
                            $constraintDate->modify(($lagDays > 0 ? '+' : '').$lagDays.' days');
                        }
                        break;

                    case 'FF': // Finish-to-Finish
                        $constraintDate = clone $predEnd;
                        $lagDays = (int) $dep->lag_days;
                        if ($lagDays !== 0) {
                            $constraintDate->modify(($lagDays > 0 ? '+' : '').$lagDays.' days');
                        }
                        // For FF, we need to work backwards from finish
                        if ($duration > 0) {
                            $constraintDate->modify('-'.$duration.' days');
                        }
                        break;

                    case 'SF': // Start-to-Finish (rare)
                        $constraintDate = clone $predStart;
                        $lagDays = (int) $dep->lag_days;
                        if ($lagDays !== 0) {
                            $constraintDate->modify(($lagDays > 0 ? '+' : '').$lagDays.' days');
                        }
                        if ($duration > 0) {
                            $constraintDate->modify('-'.$duration.' days');
                        }
                        break;
                }

                // Keep the latest constraint
                if ($constraintDate && (! $latestConstraintDate || $constraintDate > $latestConstraintDate)) {
                    $latestConstraintDate = $constraintDate;
                }
            }

            // If there's a constraint, update the dates
            if ($latestConstraintDate) {
                try {
                    $currentStart = new \DateTime($assignment->start_date);

                    // Only update if the constraint pushes the date forward
                    if ($latestConstraintDate > $currentStart) {
                        $newStartDate = clone $latestConstraintDate;
                        $newEndDate = clone $newStartDate;

                        // ✅ Use integer days for modification
                        if ($duration > 0) {
                            $newEndDate->modify('+'.$duration.' days');
                        }

                        // Update in database
                        DB::table('project_schedule_assignments')
                            ->where('project_id', $projectId)
                            ->where('schedule_id', $scheduleId)
                            ->update([
                                'start_date' => $newStartDate->format('Y-m-d'),
                                'end_date' => $newEndDate->format('Y-m-d'),
                                'updated_at' => now(),
                            ]);

                        // Update in our local cache for subsequent iterations
                        $assignment->start_date = $newStartDate->format('Y-m-d');
                        $assignment->end_date = $newEndDate->format('Y-m-d');
                        $assignments[$scheduleId] = $assignment;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }

    /**
     * Calculate critical path
     */
    public function calculateCriticalPath(int $projectId): array
    {
        $assignments = DB::table('project_schedule_assignments as psa')
            ->join('project_activity_schedules as pas', 'psa.schedule_id', '=', 'pas.id')
            ->where('psa.project_id', $projectId)
            ->where('psa.status', 'active')
            ->whereNotNull('psa.start_date')
            ->whereNotNull('psa.end_date')
            ->select([
                'pas.id as schedule_id',
                'pas.code',
                'pas.name',
                'psa.start_date',
                'psa.end_date',
                'psa.progress',
            ])
            ->get()
            ->keyBy('schedule_id');

        if ($assignments->isEmpty()) {
            return [
                'has_data' => false,
                'message' => 'No activities with planned dates found. Please set start and end dates for activities.',
                'critical_activities' => [],
                'slacks' => [],
                'project_duration' => 0,
            ];
        }

        $dependencies = DB::table('project_activity_dependencies')
            ->where('project_id', $projectId)
            ->get();

        $durations = [];
        $validScheduleIds = [];

        foreach ($assignments as $assignment) {
            try {
                $start = new \DateTime($assignment->start_date);
                $end = new \DateTime($assignment->end_date);
                $duration = max(1, (int) $start->diff($end)->days);

                $durations[$assignment->schedule_id] = $duration;
                $validScheduleIds[] = $assignment->schedule_id;
            } catch (\Exception $e) {
                continue;
            }
        }

        if (empty($durations)) {
            return [
                'has_data' => false,
                'message' => 'Unable to calculate durations. Please check date formats.',
                'critical_activities' => [],
                'slacks' => [],
                'project_duration' => 0,
            ];
        }

        $predecessors = [];
        $successors = [];

        foreach ($validScheduleIds as $scheduleId) {
            $predecessors[$scheduleId] = [];
            $successors[$scheduleId] = [];
        }

        foreach ($dependencies as $dep) {
            if (
                ! in_array($dep->predecessor_id, $validScheduleIds) ||
                ! in_array($dep->successor_id, $validScheduleIds)
            ) {
                continue;
            }

            $predecessors[$dep->successor_id][] = $dep->predecessor_id;
            $successors[$dep->predecessor_id][] = $dep->successor_id;
        }

        $ES = [];
        $EF = [];

        foreach ($validScheduleIds as $scheduleId) {
            $ES[$scheduleId] = 0;
            $EF[$scheduleId] = 0;
        }

        $visited = [];
        $queue = [];

        foreach ($validScheduleIds as $scheduleId) {
            if (empty($predecessors[$scheduleId])) {
                $queue[] = $scheduleId;
            }
        }

        if (empty($queue)) {
            return [
                'has_data' => false,
                'message' => 'Circular dependency detected or no starting activities found.',
                'critical_activities' => [],
                'slacks' => [],
                'project_duration' => 0,
            ];
        }

        while (! empty($queue)) {
            $current = array_shift($queue);

            if (isset($visited[$current])) {
                continue;
            }

            $allPredecessorsProcessed = true;
            foreach ($predecessors[$current] as $pred) {
                if (! isset($visited[$pred])) {
                    $allPredecessorsProcessed = false;
                    break;
                }
            }

            if (! $allPredecessorsProcessed) {
                $queue[] = $current;

                continue;
            }

            $maxPredEF = 0;
            foreach ($predecessors[$current] as $pred) {
                $maxPredEF = max($maxPredEF, $EF[$pred]);
            }

            $ES[$current] = $maxPredEF;
            $EF[$current] = $ES[$current] + $durations[$current];

            $visited[$current] = true;

            foreach ($successors[$current] as $succ) {
                if (! isset($visited[$succ])) {
                    $queue[] = $succ;
                }
            }
        }

        $projectDuration = 0;
        foreach ($EF as $ef) {
            $projectDuration = max($projectDuration, $ef);
        }

        $LS = [];
        $LF = [];

        foreach ($validScheduleIds as $scheduleId) {
            $LF[$scheduleId] = $projectDuration;
            $LS[$scheduleId] = $projectDuration;
        }

        $visited = [];
        $queue = [];

        foreach ($validScheduleIds as $scheduleId) {
            if (empty($successors[$scheduleId])) {
                $LF[$scheduleId] = $EF[$scheduleId];
                $queue[] = $scheduleId;
            }
        }

        while (! empty($queue)) {
            $current = array_shift($queue);

            if (isset($visited[$current])) {
                continue;
            }

            $allSuccessorsProcessed = true;
            foreach ($successors[$current] as $succ) {
                if (! isset($visited[$succ])) {
                    $allSuccessorsProcessed = false;
                    break;
                }
            }

            if (! $allSuccessorsProcessed) {
                $queue[] = $current;

                continue;
            }

            $minSuccLS = $projectDuration;
            foreach ($successors[$current] as $succ) {
                $minSuccLS = min($minSuccLS, $LS[$succ]);
            }

            $LF[$current] = $minSuccLS;
            $LS[$current] = $LF[$current] - $durations[$current];

            $visited[$current] = true;

            foreach ($predecessors[$current] as $pred) {
                if (! isset($visited[$pred])) {
                    $queue[] = $pred;
                }
            }
        }

        $slacks = [];
        $criticalActivities = [];

        foreach ($validScheduleIds as $scheduleId) {
            $slack = $LS[$scheduleId] - $ES[$scheduleId];
            $slacks[$scheduleId] = max(0, $slack);

            if ($slack <= 0) {
                $criticalActivities[] = $scheduleId;
            }
        }

        return [
            'has_data' => true,
            'critical_activities' => $criticalActivities,
            'slacks' => $slacks,
            'project_duration' => $projectDuration,
            'early_start' => $ES,
            'early_finish' => $EF,
            'late_start' => $LS,
            'late_finish' => $LF,
            'total_activities' => count($validScheduleIds),
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
            if (isset($visited[$node])) {
                return;
            }
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

        while (! empty($queue)) {
            $current = array_shift($queue);
            $successors[] = $current;

            foreach ($graph as $node => $deps) {
                if (in_array($current, $deps) && ! in_array($node, $successors) && ! in_array($node, $queue)) {
                    $queue[] = $node;
                }
            }
        }

        return $successors;
    }
}
