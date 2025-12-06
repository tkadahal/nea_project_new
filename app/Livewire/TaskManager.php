<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use Carbon\Carbon;
use Livewire\Component;

class TaskManager extends Component
{
    public $activeView = 'board';

    public $search = '';

    public $tasks;

    public $tasksFlat;

    public $calendarData;

    public $tableHeaders;

    public $tableData;

    public $statuses;

    public $priorities;

    public $projectsForFilter;

    public $statusColors;

    public $priorityColors;

    public $routePrefix = 'admin.task';

    public $deleteConfirmationMessage = 'Are you sure you want to delete this task?';

    public $actions = ['view', 'edit', 'delete'];

    public function mount()
    {
        $this->activeView = session('task_view_preference', 'board');
        $this->statuses = cache()->remember('statuses', now()->addHours(24), fn () => Status::all());
        $this->priorities = cache()->remember('priorities', now()->addHours(24), fn () => Priority::all());
        $this->projectsForFilter = cache()->remember('projects', now()->addHours(24), fn () => Project::all());
        $this->statusColors = [
            1 => '#3498db',
            2 => '#f39c12',
            3 => '#2ecc71',
        ];
        $this->priorityColors = [
            'Urgent' => '#EF4444',
            'High' => '#F59E0B',
            'Medium' => '#10B981',
            'Low' => '#6B7280',
        ];
        $this->loadViewData();
    }

    public function setView($view)
    {
        $this->activeView = $view;
        session(['task_view_preference' => $view]);
        $this->search = ''; // Reset search when switching views
        $this->loadViewData();
    }

    public function updateTaskStatus($taskId, $statusId)
    {
        Task::findOrFail($taskId)->update(['status_id' => $statusId]);
        $this->loadViewData();
        $this->emit('taskUpdated'); // Emit event to refresh client-side scripts
    }

    public function updatedSearch()
    {
        $this->loadViewData();
    }

    private function loadViewData()
    {
        $query = Task::with(['status', 'priority'])->latest();

        if ($this->search && $this->activeView === 'list') {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        $tasks = $query->get();

        if ($this->activeView === 'board') {
            $this->tasks = $tasks->groupBy('status_id')->map(function ($group) {
                return $group->values();
            });
        } elseif ($this->activeView === 'list') {
            $this->tasksFlat = $tasks->values();
        } elseif ($this->activeView === 'calendar') {
            $this->calendarData = $tasks->map(function ($task) {
                $startDate = $task->start_date ? Carbon::parse($task->start_date) : ($task->due_date ? Carbon::parse($task->due_date) : null);
                $endDate = $task->due_date ? Carbon::parse($task->due_date) : null;

                return [
                    'id' => $task->id,
                    'title' => $task->title ?? 'Untitled Task',
                    'start' => $startDate ? $startDate->format('Y-m-d') : null,
                    'end' => $endDate ? $endDate->copy()->addDay()->format('Y-m-d') : null,
                    'color' => $task->status ? ($this->statusColors[$task->status->id] ?? 'gray') : 'gray',
                    'url' => route('admin.task.show', $task->id),
                    'extendedProps' => [
                        'status' => $task->status->title ?? 'N/A',
                        'priority' => $task->priority->title ?? 'N/A',
                    ],
                ];
            })->filter(fn ($event) => $event['start'] !== null)->values()->all();
        } elseif ($this->activeView === 'table') {
            $this->tableHeaders = [
                trans('global.task.fields.id'),
                trans('global.task.fields.title'),
                trans('global.task.fields.status_id'),
                trans('global.task.fields.priority_id'),
                trans('global.task.fields.due_date'),
            ];
            $this->tableData = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status->title,
                    'priority' => $task->priority->title,
                    'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : 'N/A',
                ];
            })->all();
        }
    }

    public function render()
    {
        return view('livewire.task-manager');
    }
}
