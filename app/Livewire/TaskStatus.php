<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Directorate;
use App\Models\Department;
use App\Models\Task;
use App\Models\Status;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TaskStatus extends Component
{
    public Collection $tasks;

    public ?int $directorateFilter = null;

    public array $availableDirectorates = [];

    public function mount()
    {
        $this->loadAvailableDirectorates();
        $this->updateTasks();
    }

    public function updatedDirectorateFilter()
    {
        $this->updateTasks();
    }

    private function loadAvailableDirectorates()
    {
        $user = Auth::user();
        $roles = $user->roles->pluck('id')->toArray();

        if (in_array(Role::SUPERADMIN, $roles) || in_array(Role::ADMIN, $roles)) {
            $this->availableDirectorates = Directorate::pluck('title', 'id')->toArray();
        } elseif (in_array(Role::DIRECTORATE_USER, $roles) && $user->directorate_id) {
            $this->availableDirectorates = Directorate::where('id', $user->directorate_id)
                ->pluck('title', 'id')
                ->toArray();
            $this->directorateFilter = $user->directorate_id;
        }
    }

    private function updateTasks()
    {
        $user = Auth::user();
        $roles = $user->roles->pluck('id')->toArray();

        if (in_array(Role::SUPERADMIN, $roles) || in_array(Role::ADMIN, $roles)) {
            $this->tasks = $this->getTasks();
        } elseif (in_array(Role::DIRECTORATE_USER, $roles) && $user->directorate_id) {
            $this->tasks = $this->getTasks(directorateId: $user->directorate_id);
        } elseif (in_array(Role::DEPARTMENT_USER, $roles) && $user->directorate_id) {
            $departmentIds = Department::whereHas('directorates', fn($q) => $q->where('directorates.id', $user->directorate_id))
                ->pluck('id');
            $this->tasks = $departmentIds->isNotEmpty() ? $this->getTasks(departmentIds: $departmentIds) : collect([]);
        } elseif (in_array(Role::PROJECT_USER, $roles)) {
            $userProjectIds = $user->projects()->pluck('id');
            $this->tasks = $userProjectIds->isNotEmpty() ? $this->getTasks(projectIds: $userProjectIds) : collect([]);
        } else {
            $this->tasks = collect([]);
        }
    }

    private function getTasks(?int $directorateId = null, ?Collection $departmentIds = null, ?Collection $projectIds = null): Collection
    {
        $query = Task::with([
            'users',
            'projects' => function ($q) {
                $q->withPivot('status_id', 'progress')
                    ->with(['status' => fn($sq) => $sq->select('id', 'title', 'color'), 'directorate', 'department']);
            },
            'directorate',
            'department',
            'subTasks' => function ($q) {
                $q->with([
                    'users',
                    'status' => fn($sq) => $sq->select('id', 'title', 'color'),
                    'directorate',
                    'department',
                    'projects' => fn($pq) => $pq->select('id', 'title', 'directorate_id', 'department_id')
                        ->withPivot('status_id', 'progress')
                ]);
            }
        ])->whereNull('parent_id');

        if ($directorateId) {
            $query->where('directorate_id', $directorateId);
        } elseif ($departmentIds && $departmentIds->isNotEmpty()) {
            $query->whereIn('department_id', $departmentIds);
        } elseif ($projectIds && $projectIds->isNotEmpty()) {
            $query->whereHas('projects', function ($pq) use ($projectIds) {
                $pq->whereIn('projects.id', $projectIds);
            });
        }

        return $query->latest()->take(5)->get()->map(function ($task) {
            if ($task->projects->isNotEmpty() && $task->projects->first()->pivot->status_id) {
                $pivotStatus = Status::find($task->projects->first()->pivot->status_id);
                $status = (object) [
                    'id' => $pivotStatus?->id ?? 1,
                    'title' => $pivotStatus?->title ?? 'Not Started',
                    'color' => $pivotStatus?->color ?? '#DC143C',
                ];
            } else {
                $status = (object) [
                    'id' => $task->status?->id ?? 1,
                    'title' => $task->status?->title ?? 'Not Started',
                    'color' => $task->status?->color ?? '#DC143C',
                ];
            }

            $subTasks = $task->subTasks->map(function ($subTask) {
                if ($subTask->projects->isNotEmpty() && $subTask->projects->first()->pivot->status_id) {
                    $pivotStatus = Status::find($subTask->projects->first()->pivot->status_id);
                    $subStatus = (object) [
                        'id' => $pivotStatus?->id ?? 1,
                        'title' => $pivotStatus?->title ?? 'Not Started',
                        'color' => $pivotStatus?->color ?? '#DC143C',
                    ];
                } else {
                    $subStatus = (object) [
                        'id' => $subTask->status?->id ?? 1,
                        'title' => $subTask->status?->title ?? 'Not Started',
                        'color' => $subTask->status?->color ?? '#DC143C',
                    ];
                }

                return (object) [
                    'id' => $subTask->id,
                    'name' => $subTask->title ?? 'Unnamed Sub-task',
                    'status' => $subStatus,
                    'assigned_to' => $subTask->users->isNotEmpty()
                        ? $subTask->users->map->initials()->implode(', ')
                        : 'Unassigned',
                    'total_time_spent' => $this->calculateTimeSinceCreation($subTask->created_at),
                    'project_id' => $subTask->projects->isNotEmpty() ? $subTask->projects->first()->id : null,
                    'project_name' => $subTask->projects->isNotEmpty() ? $subTask->projects->first()->title : null,
                    'directorate_id' => $subTask->directorate_id,
                    'directorate_name' => $subTask->directorate?->title,
                    'department_id' => $subTask->department_id,
                    'department_name' => $subTask->department?->title,
                ];
            });

            return (object) [
                'id' => $task->id,
                'name' => $task->title ?? 'Unnamed Task',
                'status' => $status,
                'assigned_to' => $task->users->isNotEmpty()
                    ? $task->users->map->initials()->implode(', ')
                    : 'Unassigned',
                'total_time_spent' => $this->calculateTimeSinceCreation($task->created_at),
                'project_id' => $task->projects->isNotEmpty() ? $task->projects->first()->id : null,
                'project_name' => $task->projects->isNotEmpty() ? $task->projects->first()->title : null,
                'directorate_id' => $task->directorate_id,
                'directorate_name' => $task->directorate?->title,
                'department_id' => $task->department_id,
                'department_name' => $task->department?->title,
                'sub_tasks' => $subTasks,
            ];
        })->filter(function ($task) {
            return !is_null($task->project_id) || !is_null($task->directorate_id) || !is_null($task->department_id);
        })->take(5);
    }

    private function calculateTimeSinceCreation($createdAt): string
    {
        $now = now();
        $created = $createdAt instanceof \Carbon\Carbon ? $createdAt : \Carbon\Carbon::parse($createdAt);
        $diffInHours = floor($created->diffInHours($now));
        $diffInMinutes = $created->diffInMinutes($now) % 60;

        return "{$diffInHours} hr {$diffInMinutes} min";
    }

    public function render()
    {
        return view('livewire.task-status');
    }
}
