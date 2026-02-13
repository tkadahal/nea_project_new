<?php

declare(strict_types=1);

namespace App\Helpers\Task;

use App\DTOs\Task\TaskDTO;
use App\Models\Status;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TaskHelper
{
    public function transformForBoardView(Collection $tasks, array $statusColors, array $priorityColors): array
    {
        $allTasks = $this->expandTasksWithProjects($tasks, $statusColors, $priorityColors);

        $grouped = $allTasks->groupBy(fn($taskItem) => $taskItem->status_id ?? 'none')
            ->map(fn($group) => $group->map(fn($taskItem) => $this->formatTaskForBoard($taskItem, $statusColors, $priorityColors))->values());

        return [
            'tasks' => $grouped,
            'taskCounts' => $grouped->map->count()->toArray(),
        ];
    }

    public function transformForListView(Collection $tasks, array $statusColors, array $priorityColors): array
    {
        $allTasks = $this->expandTasksWithProjects($tasks, $statusColors, $priorityColors);

        return [
            'tasksFlat' => $allTasks->map(fn($taskItem) => $this->formatTaskForList($taskItem, $statusColors, $priorityColors))->values(),
        ];
    }

    public function transformForCalendarView(Collection $tasks, array $statusColors): array
    {
        $allTasks = $this->expandTasksWithProjects($tasks, $statusColors, []);

        return [
            'calendarData' => $allTasks
                ->map(fn($taskItem) => $this->formatTaskForCalendar($taskItem, $statusColors))
                ->filter(fn($event) => $event['start'] !== null)
                ->values()
                ->all(),
        ];
    }

    public function transformForTableView(Collection $tasks, array $statusColors, array $priorityColors): array
    {
        $allTasks = $this->expandTasksWithProjects($tasks, $statusColors, $priorityColors);

        return [
            'tableHeaders' => [
                trans('global.task.fields.id'),
                trans('global.task.fields.title'),
                trans('global.task.fields.project_id'),
                trans('global.task.fields.parent_id'),
                trans('global.details'),
            ],
            'tableData' => $allTasks->map(fn($taskItem) => $this->formatTaskForTable($taskItem, $statusColors, $priorityColors))->values()->toArray(),
        ];
    }

    public function prepareProjectSyncData(array $projectIds, array $validated, Task $task, ?int $currentProjectId = null): array
    {
        $syncData = [];
        foreach ($projectIds as $projectId) {
            $projectTask = $task->projects()->where('project_id', $projectId)->first();

            $syncData[$projectId] = [
                'status_id' => ($currentProjectId == $projectId)
                    ? ($validated['status_id'] ?? $projectTask?->pivot?->status_id ?? $task->status_id)
                    : ($task->status_id),
                'progress' => ($currentProjectId == $projectId)
                    ? ($validated['progress'] ?? $projectTask?->pivot?->progress ?? $task->progress)
                    : ($task->progress),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        return $syncData;
    }

    private function expandTasksWithProjects(Collection $tasks, array $statusColors, array $priorityColors): Collection
    {
        return $tasks->flatMap(function ($task) use ($statusColors, $priorityColors) {
            $results = [];

            if ($task->projects->isNotEmpty()) {
                $results = $task->projects->filter(fn($project) => !is_null($project->id))
                    ->map(function ($project) use ($task, $statusColors, $priorityColors) {
                        $status = $project->pivot->status_id
                            ? Status::find($project->pivot->status_id)
                            : ($task->status_id ? Status::find($task->status_id) : null);

                        return (object) [
                            'task' => $task,
                            'project' => $project,
                            'status_id' => $project->pivot->status_id ?? $task->status_id,
                            'status' => $status,
                            'progress' => $project->pivot->progress ?? $task->progress,
                            'project_id' => $project->id,
                        ];
                    })->toArray();
            } else {
                $status = $task->status_id ? Status::find($task->status_id) : null;
                $results[] = (object) [
                    'task' => $task,
                    'project' => null,
                    'status_id' => $task->status_id,
                    'status' => $status,
                    'progress' => $task->progress,
                    'project_id' => null,
                ];
            }

            return $results;
        })->filter(fn($taskItem) => !is_null($taskItem->task->id));
    }

    private function formatTaskForBoard($taskItem, array $statusColors, array $priorityColors): array
    {
        $task = $taskItem->task;

        return [
            'id' => $task->id,
            'title' => $task->title ?? 'Untitled Task',
            'description' => $task->description ?? 'No description',
            'priority' => $task->priority ? [
                'title' => $task->priority->title,
                'color' => $priorityColors[$task->priority->title] ?? 'gray'
            ] : null,
            'priority_id' => $task->priority_id,
            'status' => $taskItem->status ? [
                'id' => $taskItem->status->id,
                'title' => $taskItem->status->title
            ] : null,
            'status_id' => $taskItem->status_id,
            'status_color' => $taskItem->status ? ($statusColors[$taskItem->status->id] ?? 'gray') : 'gray',
            'view_url' => $taskItem->project_id
                ? route('admin.task.show', [$task->id, $taskItem->project_id])
                : route('admin.task.show', $task->id),
            'project_id' => $taskItem->project_id,
            'project_name' => $taskItem->project?->title ?? 'N/A',
            'directorate_id' => $task->directorate_id ? (string) $task->directorate_id : '',
            'directorate_name' => $task->directorate?->title ?? 'N/A',
            'department_id' => $task->department_id ? (string) $task->department_id : '',
            'department_name' => $task->department?->title ?? 'N/A',
            'progress' => $taskItem->progress ?? 'N/A',
            'start_date' => $task->start_date?->format('Y-m-d'),
            'due_date' => $task->due_date?->format('Y-m-d'),
            'parent_id' => $task->parent_id ? (string) $task->parent_id : null,
            'parent_title' => $task->parent?->title,
            'sub_tasks' => $this->formatSubTasks($task->subTasks, $statusColors, $priorityColors, $taskItem->project_id),
        ];
    }

    private function formatTaskForList($taskItem, array $statusColors, array $priorityColors): array
    {
        $task = $taskItem->task;

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $taskItem->status ? $taskItem->status->title : 'N/A',
            'status_id' => $taskItem->status_id,
            'progress' => $taskItem->progress ?? 'N/A',
            'priority' => $task->priority ? $task->priority->title : 'N/A',
            'priority_id' => $task->priority_id,
            'due_date' => $task->due_date ? Carbon::parse($task->due_date)->format('Y-m-d') : 'N/A',
            'projects' => $taskItem->project ? [$taskItem->project->title] : ['N/A'],
            'users' => $task->users->pluck('name')->all(),
            'directorate' => $task->directorate?->title ?? 'N/A',
            'directorate_id' => $task->directorate_id ? (string) $task->directorate_id : '',
            'department_id' => $task->department_id ? (string) $task->department_id : '',
            'project_id' => $taskItem->project_id,
            'view_url' => $taskItem->project_id
                ? route('admin.task.show', [$task->id, $taskItem->project_id])
                : route('admin.task.show', $task->id),
            'parent_id' => $task->parent_id ? (string) $task->parent_id : null,
            'parent_title' => $task->parent?->title,
            'sub_tasks' => $this->formatSubTasks($task->subTasks, $statusColors, $priorityColors, $taskItem->project_id),
        ];
    }

    private function formatTaskForCalendar($taskItem, array $statusColors): array
    {
        $task = $taskItem->task;
        $startDate = $task->start_date ? Carbon::parse($task->start_date) : ($task->due_date ? Carbon::parse($task->due_date) : null);
        $endDate = $task->due_date ? Carbon::parse($task->due_date) : null;

        return [
            'id' => $task->id,
            'title' => $task->title ?? 'Untitled Task',
            'start' => $startDate?->format('Y-m-d'),
            'end' => $endDate?->addDay()->format('Y-m-d'),
            'color' => $taskItem->status ? ($statusColors[$taskItem->status->id] ?? 'gray') : 'gray',
            'url' => $taskItem->project_id
                ? route('admin.task.show', [$task->id, $taskItem->project_id])
                : route('admin.task.show', $task->id),
            'extendedProps' => [
                'status' => $taskItem->status ? $taskItem->status->title : 'N/A',
                'progress' => $taskItem->progress ?? 'N/A',
                'priority' => $task->priority ? $task->priority->title : 'N/A',
                'priority_id' => $task->priority_id,
                'project' => $taskItem->project?->title ?? 'N/A',
                'users' => $task->users->pluck('name')->all(),
                'directorate' => $task->directorate?->title ?? 'N/A',
                'directorate_id' => $task->directorate_id ? (string) $task->directorate_id : '',
                'department_id' => $task->department_id ? (string) $task->department_id : '',
                'start_date' => $task->start_date?->format('Y-m-d'),
                'project_id' => $taskItem->project_id,
                'parent_id' => $task->parent_id ? (string) $task->parent_id : null,
                'parent_title' => $task->parent?->title,
            ],
        ];
    }

    private function formatTaskForTable($taskItem, array $statusColors, array $priorityColors): array
    {
        $task = $taskItem->task;

        return [
            'id' => $task->id,
            'title' => $task->title,
            'project' => $taskItem->project?->title ?? 'N/A',
            'parent_id' => $task->parent_id ? (string) $task->parent_id : null,
            'parent_title' => $task->parent?->title,
            'details' => [
                'status' => $taskItem->status
                    ? ['title' => $taskItem->status->title, 'color' => $statusColors[$taskItem->status->id] ?? 'gray']
                    : ['title' => 'N/A', 'color' => 'gray'],
                'progress' => $taskItem->progress ?? 'N/A',
                'priority' => $task->priority
                    ? ['title' => $task->priority->title, 'color' => $priorityColors[$task->priority->title] ?? 'gray']
                    : ['title' => 'N/A', 'color' => 'gray'],
                'due_date' => $task->due_date ? Carbon::parse($task->due_date)->format('Y-m-d') : 'N/A',
                'users' => $task->users->pluck('name')->toArray(),
                'directorate' => $task->directorate
                    ? ['title' => $task->directorate->title, 'color' => $statusColors[$task->directorate->id] ?? 'gray']
                    : ['title' => 'N/A', 'color' => 'gray'],
            ],
            'project_id' => $taskItem->project_id,
            'directorate_id' => $task->directorate_id ? (string) $task->directorate_id : '',
            'department_id' => $task->department_id ? (string) $task->department_id : '',
            'search_data' => $this->buildSearchData($task, $taskItem),
        ];
    }

    private function formatSubTasks(Collection $subTasks, array $statusColors, array $priorityColors, ?int $projectId): array
    {
        return $subTasks->map(function ($subTask) use ($statusColors, $priorityColors, $projectId) {
            return [
                'id' => $subTask->id,
                'title' => $subTask->title ?? 'Untitled Sub-task',
                'description' => $subTask->description ?? 'No description',
                'priority' => $subTask->priority ? [
                    'title' => $subTask->priority->title,
                    'color' => $priorityColors[$subTask->priority->title] ?? 'gray'
                ] : null,
                'priority_id' => $subTask->priority_id,
                'status' => $subTask->status ? [
                    'id' => $subTask->status->id,
                    'title' => $subTask->status->title,
                    'color' => $statusColors[$subTask->status->id] ?? 'gray'
                ] : null,
                'status_id' => $subTask->status_id,
                'status_color' => $subTask->status ? ($statusColors[$subTask->status->id] ?? 'gray') : 'gray',
                'view_url' => $projectId
                    ? route('admin.task.show', [$subTask->id, $projectId])
                    : route('admin.task.show', $subTask->id),
                'project_id' => $projectId,
                'directorate_id' => $subTask->directorate_id ? (string) $subTask->directorate_id : '',
                'directorate_name' => $subTask->directorate?->title ?? 'N/A',
                'department_id' => $subTask->department_id ? (string) $subTask->department_id : '',
                'department_name' => $subTask->department?->title ?? 'N/A',
                'start_date' => $subTask->start_date?->format('Y-m-d'),
                'due_date' => $subTask->due_date?->format('Y-m-d'),
            ];
        })->values()->toArray();
    }

    private function buildSearchData($task, $taskItem): string
    {
        return strtolower(
            $task->title . ' ' .
                ($taskItem->status ? $taskItem->status->title : '') . ' ' .
                ($task->priority ? $task->priority->title : '') . ' ' .
                ($task->due_date ? $task->due_date->format('Y-m-d') : '') . ' ' .
                ($taskItem->project?->title ?? '') . ' ' .
                ($task->users->pluck('name')->join(' ') ?? '') . ' ' .
                ($task->directorate?->title ?? '') . ' ' .
                ($task->directorate_id ?? '') . ' ' .
                ($task->department_id ?? '') . ' ' .
                ($task->parent ? $task->parent->title : '')
        );
    }
}
