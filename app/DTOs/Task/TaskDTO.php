<?php

declare(strict_types=1);

namespace App\DTOs\Task;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class TaskDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?int $priorityId,
        public readonly ?int $statusId,
        public readonly ?int $directorateId,
        public readonly ?int $departmentId,
        public readonly ?int $parentId,
        public readonly ?int $progress,
        public readonly ?Carbon $startDate,
        public readonly ?Carbon $dueDate,
        public readonly ?Carbon $completionDate,
        public readonly ?Carbon $createdAt,
        public readonly ?Carbon $updatedAt,
        public readonly Collection $projects,
        public readonly Collection $users,
        public readonly Collection $subTasks,
        public readonly ?array $priority = null,
        public readonly ?array $status = null,
        public readonly ?string $directorateName = null,
        public readonly ?string $departmentName = null,
        public readonly ?string $parentTitle = null,
    ) {}

    public static function fromModel($task, ?int $projectId = null, ?array $statusColors = [], ?array $priorityColors = []): self
    {
        $project = $projectId ? $task->projects->firstWhere('id', $projectId) : $task->projects->first();
        $statusId = $project?->pivot?->status_id ?? $task->status_id;
        $status = $statusId ? \App\Models\Status::find($statusId) : null;

        return new self(
            id: $task->id,
            title: $task->title ?? 'Untitled Task',
            description: $task->description ?? 'No description',
            priorityId: $task->priority_id,
            statusId: $statusId,
            directorateId: $task->directorate_id,
            departmentId: $task->department_id,
            parentId: $task->parent_id,
            progress: $project?->pivot?->progress ?? $task->progress,
            startDate: $task->start_date,
            dueDate: $task->due_date,
            completionDate: $task->completion_date,
            createdAt: $task->created_at,
            updatedAt: $task->updated_at,
            projects: $task->projects,
            users: $task->users,
            subTasks: $task->subTasks ?? collect(),
            priority: $task->priority ? [
                'title' => $task->priority->title,
                'color' => $priorityColors[$task->priority->title] ?? 'gray'
            ] : null,
            status: $status ? [
                'id' => $status->id,
                'title' => $status->title,
                'color' => $statusColors[$status->id] ?? 'gray'
            ] : null,
            directorateName: $task->directorate?->title ?? 'N/A',
            departmentName: $task->department?->title ?? 'N/A',
            parentTitle: $task->parent?->title,
        );
    }

    public function toArray(?int $projectId = null): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'priority_id' => $this->priorityId,
            'status' => $this->status,
            'status_id' => $this->statusId,
            'status_color' => $this->status['color'] ?? 'gray',
            'view_url' => $projectId
                ? route('admin.task.show', [$this->id, $projectId])
                : route('admin.task.show', $this->id),
            'project_id' => $projectId,
            'project_name' => $this->projects->firstWhere('id', $projectId)?->title ?? 'N/A',
            'directorate_id' => $this->directorateId ? (string) $this->directorateId : '',
            'directorate_name' => $this->directorateName,
            'department_id' => $this->departmentId ? (string) $this->departmentId : '',
            'department_name' => $this->departmentName,
            'progress' => $this->progress ?? 'N/A',
            'start_date' => $this->startDate?->format('Y-m-d'),
            'due_date' => $this->dueDate?->format('Y-m-d'),
            'parent_id' => $this->parentId ? (string) $this->parentId : null,
            'parent_title' => $this->parentTitle,
        ];
    }
}
