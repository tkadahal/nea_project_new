<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use App\Models\Task;
use App\Models\Directorate;
use App\Models\Priority;

class TaskGanttChart extends Component
{
    public $directorateId = null;
    public $priorityId = null;
    public $tasks;
    public $availableDirectorates;
    public $priorities;
    public $viewMode = 'Week'; // Default view mode

    public function mount()
    {
        $this->availableDirectorates = Directorate::all()->pluck('title', 'id')->toArray();
        $this->priorities = Priority::all()->pluck('title', 'id')->toArray();
        $this->updateTasks();
    }

    public function updated($property)
    {
        if (in_array($property, ['directorateId', 'priorityId'])) {
            $this->updateTasks();
        }
    }

    public function updateTasks()
    {
        $query = Task::with(['projects.directorate', 'priority']);

        if ($this->directorateId) {
            $query->whereHas('projects', function ($q) {
                $q->where('directorate_id', $this->directorateId);
            });
        }

        if ($this->priorityId) {
            $query->where('priority_id', $this->priorityId);
        }

        $this->tasks = $query->get()->map(function ($task) {
            $directorateTitle = $task->projects->first()?->directorate?->title ?? 'N/A';
            $directorateId = $task->projects->first()?->directorate?->id ?? null;

            return [
                'id' => $task->id,
                'title' => $task->title,
                'start' => $task->start_date->format('Y-m-d'),
                'end' => $task->due_date->format('Y-m-d'),
                'progress' => $task->progress ?? 0,
                'directorate' => $directorateTitle,
                'directorate_id' => $directorateId,
                'priority' => $task->priority_id ?? null,
                'priority_title' => $task->priority->title ?? 'N/A',
                'resourceId' => $task->id % 3 + 1,
            ];
        })->all();

        // Dispatch event to update the Gantt chart
        $this->dispatch('tasksUpdated', $this->tasks)->to('task-gantt-chart');
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
        $this->dispatch('viewModeChanged', ['mode' => $mode])->to('task-gantt-chart');
    }

    public function render()
    {
        return view('livewire.task-gantt-chart');
    }
}
