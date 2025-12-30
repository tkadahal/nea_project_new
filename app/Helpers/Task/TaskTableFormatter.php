<?php

namespace App\Helpers\Task;

use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class TaskTableFormatter
{
    public static function handle(
        Collection $taskItems,
        array $statusColors,
        array $priorityColors
    ): array {
        return [
            'tableHeaders' => [
                trans('global.task.fields.id'),
                trans('global.task.fields.title'),
                trans('global.task.fields.project_id'),
                trans('global.task.fields.parent_id'),
                trans('global.details'),
            ],

            'tableData' => $taskItems->map(function ($item) use ($statusColors, $priorityColors) {
                $task = $item->task;

                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'project' => $item->project?->title ?? 'N/A',

                    'parent_id' => (string) ($task->parent_id ?? ''),
                    'parent_title' => $task->parent?->title,

                    'details' => [
                        'status' => $item->status
                            ? [
                                'title' => $item->status->title,
                                'color' => $statusColors[$item->status->id] ?? 'gray',
                            ]
                            : ['title' => 'N/A', 'color' => 'gray'],

                        'progress' => $item->progress ?? 'N/A',

                        'priority' => $task->priority
                            ? [
                                'title' => $task->priority->title,
                                'color' => $priorityColors[$task->priority->title] ?? 'gray',
                            ]
                            : ['title' => 'N/A', 'color' => 'gray'],

                        'due_date' => $task->due_date
                            ? Carbon::parse($task->due_date)->format('Y-m-d')
                            : 'N/A',

                        'users' => $task->users->pluck('name')->toArray(),
                        'directorate' => $task->directorate?->title ?? 'N/A',
                    ],

                    'project_id' => $item->project_id,
                    'directorate_id' => (string) ($task->directorate_id ?? ''),
                    'department_id' => (string) ($task->department_id ?? ''),
                ];
            })->values()->toArray(),
        ];
    }
}
