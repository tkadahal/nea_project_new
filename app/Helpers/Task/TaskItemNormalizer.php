<?php

namespace App\Helpers\Task;

use App\Models\Status;
use Illuminate\Support\Collection;

class TaskItemNormalizer
{
    public static function normalize(Collection $tasks): Collection
    {
        return $tasks->flatMap(function ($task) {

            // Task linked to projects
            if ($task->projects->isNotEmpty()) {
                return $task->projects
                    ->filter(fn($project) => $project && $project->id)
                    ->map(function ($project) use ($task) {
                        $statusId = $task->status_id;

                        return (object)[
                            'task' => $task,
                            'project' => $project,
                            'project_id' => $project->id,
                            'status' => $task->status ? ['id' => $task->status->id, 'title' => $task->status->title] : null,
                            'status' => $statusId ? Status::find($statusId) : null,
                            'progress' => $project->pivot->progress ?? $task->progress,
                        ];
                    });
            }

            // Task without project
            $statusId = $task->status_id;

            return [(object)[
                'task' => $task,
                'project' => null,
                'project_id' => null,
                'status_id' => $statusId,
                'status' => $statusId ? Status::find($statusId) : null,
                'progress' => $task->progress,
            ]];
        })->filter(fn($item) => $item->task && $item->task->id);
    }
}
