<?php

namespace App\Helpers\Task;

use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class TaskListFormatter
{
    public static function handle(Collection $taskItems): array
    {
        return [
            'tasksFlat' => $taskItems->map(function ($item) {
                $task = $item->task;

                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $item->status?->title ?? 'N/A',
                    'status_id' => $item->status_id,
                    'progress' => $item->progress ?? 'N/A',
                    'priority' => $task->priority?->title ?? 'N/A',
                    'priority_id' => $task->priority_id,
                    'due_date' => $task->due_date
                        ? Carbon::parse($task->due_date)->format('Y-m-d')
                        : 'N/A',

                    'projects' => $item->project
                        ? [$item->project->title]
                        : ['N/A'],

                    'users' => $task->users->pluck('name')->all(),

                    'directorate' => $task->directorate?->title ?? 'N/A',
                    'directorate_id' => (string) ($task->directorate_id ?? ''),
                    'department_id' => (string) ($task->department_id ?? ''),

                    'project_id' => $item->project_id,

                    'view_url' => $item->project_id
                        ? route('admin.task.show', [$task->id, $item->project_id])
                        : route('admin.task.show', $task->id),

                    'parent_id' => (string) ($task->parent_id ?? ''),
                    'parent_title' => $task->parent?->title,
                ];
            })->values(),
        ];
    }
}
