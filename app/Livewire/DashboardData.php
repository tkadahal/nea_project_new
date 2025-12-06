<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class DashboardData extends Component
{
    public $directorateId;

    public $projectIds;

    public $projectStatusFilter = ['status' => '', 'dateRange' => ''];

    public $tasksFilter = ['status' => '', 'dateRange' => ''];

    public $sprintFilterRange = 'ALL';

    public $showProjectStatusFilter = false;

    public $showTasksFilter = false;

    protected $queryString = [
        'projectStatusFilter' => ['except' => ['status' => '', 'dateRange' => '']],
        'tasksFilter' => ['except' => ['status' => '', 'dateRange' => '']],
        'sprintFilterRange' => ['except' => 'ALL'],
    ];

    public function mount(?int $directorateId = null, ?array $projectIds = [])
    {
        $this->directorateId = $directorateId;
        $this->projectIds = $projectIds;
    }

    public function render()
    {
        return view('livewire.dashboard-data', [
            'tasks' => $this->getTasks(),
            'project_status' => $this->getProjectStatus(),
            'sprint_data' => $this->getSprintData(),
        ]);
    }

    private function getTasks(): Collection
    {
        $query = Task::with(['status', 'users']);

        if ($this->directorateId) {
            $query->whereHas('projects', fn ($q) => $q->where('directorate_id', $this->directorateId));
        } elseif ($this->projectIds) {
            $query->whereHas('projects', fn ($q) => $q->whereIn('id', $this->projectIds));
        }

        if ($this->tasksFilter['status']) {
            $query->where('status_id', $this->tasksFilter['status']);
        }

        if ($this->tasksFilter['dateRange']) {
            [$start, $end] = explode(' to ', $this->tasksFilter['dateRange']);
            $query->whereBetween('created_at', [Carbon::parse($start), Carbon::parse($end)]);
        }

        return $query->latest()->take(5)->get()->map(function ($task) {
            return (object) [
                'id' => $task->id,
                'name' => $task->title ?? 'Unnamed Task',
                'status' => $task->status,
                'assigned_to' => $task->users->first()?->name ?? 'Unassigned',
                'total_time_spent' => $this->generateRandomTime(),
            ];
        });
    }

    private function getProjectStatus(): array
    {
        $query = Project::query();

        if ($this->directorateId) {
            $query->where('directorate_id', $this->directorateId);
        } elseif ($this->projectIds) {
            $query->whereIn('id', $this->projectIds);
        }

        if ($this->projectStatusFilter['dateRange']) {
            [$start, $end] = explode(' to ', $this->projectStatusFilter['dateRange']);
            $query->whereBetween('created_at', [Carbon::parse($start), Carbon::parse($end)]);
        }

        $total = max(1, $query->count());
        Log::debug('Total projects: '.$total);

        $statusCounts = $query->select('status_id', DB::raw('count(*) as count'))
            ->groupBy('status_id')
            ->pluck('count', 'status_id')->all();

        $completed = $statusCounts[Status::STATUS_COMPLETED] ?? 0;
        $inProgress = $statusCounts[Status::STATUS_IN_PROGRESS] ?? 0;
        $behind = $statusCounts[Status::STATUS_TODO] ?? 0;

        Log::debug("Completed: $completed, In Progress: $inProgress, Behind: $behind");

        $total = $completed + $inProgress + $behind;
        $total = max(1, $total);

        $completedPercent = round(($completed / $total) * 100);
        $inProgressPercent = round(($inProgress / $total) * 100);
        $behindPercent = round(($behind / $total) * 100);

        return [
            'completed' => $completedPercent,
            'in_progress' => $inProgressPercent,
            'behind' => $behindPercent,
        ];
    }

    private function getSprintData(): array
    {
        $endDate = Carbon::now();
        $numSprints = match ($this->sprintFilterRange) {
            '1M' => 1,
            '6M' => 2,
            '1Y' => 4,
            default => 24, // ALL
        };
        $startDate = $endDate->copy()->subMonths($numSprints * 3);
        $sprints = [];

        $query = Task::select(
            DB::raw("TO_CHAR(created_at, 'YYYY-MM-01') as month_start"),
            DB::raw('status_id'),
            DB::raw('COUNT(*) as count')
        );

        if ($this->directorateId) {
            $query->whereHas('projects', fn ($q) => $q->where('directorate_id', $this->directorateId));
        } elseif ($this->projectIds) {
            $query->whereHas('projects', fn ($q) => $q->whereIn('id', $this->projectIds));
        }

        $query->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw("TO_CHAR(created_at, 'YYYY-MM-01')"), 'status_id');

        $tasksByMonthAndStatus = $query->get()->reduce(function ($carry, $item) {
            $carry[$item->month_start][$item->status_id] = $item->count;

            return $carry;
        }, []);

        for ($i = 0; $i < $numSprints; $i++) {
            $sprintStart = $startDate->copy()->addMonths($i * 3);
            $sprintEnd = $sprintStart->copy()->addMonths(2);

            $sprintData = [
                'todo' => 0,
                'in_progress' => 0,
                'completed' => 0,
            ];

            for ($month = 0; $month < 3; $month++) {
                $monthDate = $sprintStart->copy()->addMonths($month)->format('Y-m-01');
                $monthData = $tasksByMonthAndStatus[$monthDate] ?? [];

                foreach ($monthData as $statusId => $count) {
                    if ($statusId == Status::STATUS_TODO) {
                        $sprintData['todo'] += $count;
                    } elseif ($statusId == Status::STATUS_IN_PROGRESS) {
                        $sprintData['in_progress'] += $count;
                    } elseif ($statusId == Status::STATUS_COMPLETED) {
                        $sprintData['completed'] += $count;
                    }
                }
            }

            $sprints['Sprint '.($i + 1)] = $sprintData;
        }

        return $sprints;
    }

    private function generateRandomTime(): string
    {
        $hours = rand(1, 100);
        $minutes = rand(0, 59);

        return "{$hours}h {$minutes}min";
    }
}
