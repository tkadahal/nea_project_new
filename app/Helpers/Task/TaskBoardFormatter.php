<?php

namespace App\Helpers\Task;

use App\Models\Status;
use Illuminate\Support\Collection;

class TaskBoardFormatter
{
    public static function handle(
        Collection $taskItems,
        array $statusColors,
        array $priorityColors
    ): array {
        $grouped = $taskItems->groupBy(fn($item) => $item->status_id ?? 'none');

        $tasks = $grouped->map(function ($group) use ($statusColors, $priorityColors) {
            return $group->map(function ($item) use ($statusColors, $priorityColors) {
                $task = $item->task;

                return [
                    'id' => $task->id,
                    'title' => $task->title ?? 'Untitled Task',
                    'description' => $task->description ?? 'No description',

                    'priority' => $task->priority
                        ? [
                            'title' => $task->priority->title,
                            'color' => $priorityColors[$task->priority->title] ?? 'gray',
                        ]
                        : null,

                    'priority_id' => $task->priority_id,

                    'status' => $item->status
                        ? ['id' => $item->status->id, 'title' => $item->status->title]
                        : null,

                    'status_id' => $item->status_id,
                    'status_color' => $item->status
                        ? ($statusColors[$item->status->id] ?? 'gray')
                        : 'gray',

                    'view_url' => $item->project_id
                        ? route('admin.task.show', [$task->id, $item->project_id])
                        : route('admin.task.show', $task->id),

                    'project_id' => $item->project_id,
                    'project_name' => $item->project?->title ?? 'N/A',

                    'directorate_id' => (string) ($task->directorate_id ?? ''),
                    'directorate_name' => $task->directorate?->title ?? 'N/A',

                    'department_id' => (string) ($task->department_id ?? ''),
                    'department_name' => $task->department?->title ?? 'N/A',

                    'progress' => $item->progress ?? 'N/A',

                    'start_date' => optional($task->start_date)->format('Y-m-d'),
                    'due_date' => optional($task->due_date)->format('Y-m-d'),

                    'parent_id' => (string) ($task->parent_id ?? ''),
                    'parent_title' => $task->parent?->title,

                    'sub_tasks' => self::formatSubTasks(
                        $task->subTasks,
                        $item,
                        $statusColors,
                        $priorityColors
                    ),
                ];
            })->values();
        });

        return [
            'tasks' => $tasks,
            'taskCounts' => $tasks->map->count()->toArray(),
        ];
    }

    private static function formatSubTasks($subTasks, $parentItem, $statusColors, $priorityColors): array
    {
        return $subTasks->map(function ($subTask) use ($parentItem, $statusColors, $priorityColors) {
            return [
                'id' => $subTask->id,
                'title' => $subTask->title ?? 'Untitled Sub-task',
                'description' => $subTask->description ?? 'No description',

                'priority' => $subTask->priority
                    ? [
                        'title' => $subTask->priority->title,
                        'color' => $priorityColors[$subTask->priority->title] ?? 'gray',
                    ]
                    : null,

                'priority_id' => $subTask->priority_id,

                'status' => $subTask->status
                    ? ['id' => $subTask->status->id, 'title' => $subTask->status->title]
                    : null,

                'status_id' => $subTask->status_id,
                'status_color' => $subTask->status
                    ? ($statusColors[$subTask->status->id] ?? 'gray')
                    : 'gray',

                'view_url' => $parentItem->project_id
                    ? route('admin.task.show', [$subTask->id, $parentItem->project_id])
                    : route('admin.task.show', $subTask->id),

                'project_id' => $parentItem->project_id,
                'project_name' => $parentItem->project?->title ?? 'N/A',

                'directorate_id' => (string) ($subTask->directorate_id ?? ''),
                'directorate_name' => $subTask->directorate?->title ?? 'N/A',

                'department_id' => (string) ($subTask->department_id ?? ''),
                'department_name' => $subTask->department?->title ?? 'N/A',

                'start_date' => optional($subTask->start_date)->format('Y-m-d'),
                'due_date' => optional($subTask->due_date)->format('Y-m-d'),
            ];
        })->values()->toArray();
    }
}
