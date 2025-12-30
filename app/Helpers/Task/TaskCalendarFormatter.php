<?php

namespace App\Helpers\Task;

use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class TaskCalendarFormatter
{
    public static function handle(Collection $taskItems, array $statusColors): array
    {
        return [
            'calendarData' => $taskItems
                ->map(function ($item) use ($statusColors) {
                    $task = $item->task;

                    $start = $task->start_date
                        ? Carbon::parse($task->start_date)
                        : ($task->due_date ? Carbon::parse($task->due_date) : null);

                    if (!$start) {
                        return null;
                    }

                    return [
                        'id' => $task->id,
                        'title' => $task->title ?? 'Untitled Task',
                        'start' => $start->format('Y-m-d'),
                        'end' => $task->due_date
                            ? Carbon::parse($task->due_date)->addDay()->format('Y-m-d')
                            : null,

                        'color' => $item->status
                            ? ($statusColors[$item->status->id] ?? 'gray')
                            : 'gray',

                        'url' => $item->project_id
                            ? route('admin.task.show', [$task->id, $item->project_id])
                            : route('admin.task.show', $task->id),

                        'extendedProps' => [
                            'status' => $item->status?->title ?? 'N/A',
                            'progress' => $item->progress ?? 'N/A',
                            'priority' => $task->priority?->title ?? 'N/A',
                            'priority_id' => $task->priority_id,
                            'project' => $item->project?->title ?? 'N/A',
                            'users' => $task->users->pluck('name')->all(),
                            'directorate' => $task->directorate?->title ?? 'N/A',
                            'directorate_id' => (string) ($task->directorate_id ?? ''),
                            'department_id' => (string) ($task->department_id ?? ''),
                            'start_date' => optional($task->start_date)->format('Y-m-d'),
                            'project_id' => $item->project_id,
                            'parent_id' => (string) ($task->parent_id ?? ''),
                            'parent_title' => $task->parent?->title,
                        ],
                    ];
                })
                ->filter()
                ->values()
                ->all(),
        ];
    }
}
