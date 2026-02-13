<?php

declare(strict_types=1);

namespace App\Notifications\Task;

use App\Models\Department;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskCreated;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notifyTaskCreated(Task $task, array $validated): void
    {
        $notifiedUsers = collect();

        // Notify project users
        if (!empty($validated['projects'])) {
            $this->notifyProjectUsers($task, $validated['projects'], $notifiedUsers);
        }

        // Notify department users
        if (!empty($validated['department_id']) && $notifiedUsers->isEmpty()) {
            $this->notifyDepartmentUsers($task, $validated['department_id'], $notifiedUsers);
        }

        // Notify directorate users
        if (!empty($validated['directorate_id']) && empty($validated['projects']) && $notifiedUsers->isEmpty()) {
            $this->notifyDirectorateUsers($task, $validated['directorate_id'], $notifiedUsers);
        }

        // Notify parent task users
        if (!empty($validated['parent_id'])) {
            $this->notifyParentTaskUsers($task, $validated['parent_id'], $notifiedUsers);
        }
    }

    public function notifyTaskUpdated(Task $task, array $validated): void
    {
        // Implementation for task update notifications
        // Similar structure to notifyTaskCreated
    }

    public function notifyTaskDeleted(Task $task): void
    {
        // Implementation for task deletion notifications
    }

    private function notifyProjectUsers(Task $task, array $projectIds, Collection &$notifiedUsers): void
    {
        foreach ($projectIds as $projectId) {
            $project = Project::find($projectId);
            if (!$project) {
                continue;
            }

            foreach ($project->users as $user) {
                if (!$notifiedUsers->contains($user->id)) {
                    $user->notify(new TaskCreated($task, $projectId));
                    $notifiedUsers->push($user->id);
                }
            }
        }
    }

    private function notifyDepartmentUsers(Task $task, int $departmentId, Collection &$notifiedUsers): void
    {
        $department = Department::find($departmentId);
        if (!$department) {
            return;
        }

        $directorateIds = $department->directorates()->pluck('directorates.id');
        $users = User::whereIn('directorate_id', $directorateIds)
            ->whereHas('roles', fn($q) => $q->where('id', Role::DEPARTMENT_USER))
            ->get();

        foreach ($users as $user) {
            if (!$notifiedUsers->contains($user->id)) {
                $user->notify(new TaskCreated($task, null));
                $notifiedUsers->push($user->id);
            }
        }
    }

    private function notifyDirectorateUsers(Task $task, int $directorateId, Collection &$notifiedUsers): void
    {
        $users = User::where('directorate_id', $directorateId)
            ->whereHas('roles', fn($q) => $q->where('id', Role::DIRECTORATE_USER))
            ->get();

        foreach ($users as $user) {
            if (!$notifiedUsers->contains($user->id)) {
                $user->notify(new TaskCreated($task, null));
                $notifiedUsers->push($user->id);
            }
        }
    }

    private function notifyParentTaskUsers(Task $task, int $parentId, Collection &$notifiedUsers): void
    {
        $parentTask = Task::find($parentId);
        if (!$parentTask) {
            return;
        }

        foreach ($parentTask->users as $user) {
            if (!$notifiedUsers->contains($user->id)) {
                $user->notify(new TaskCreated($task, null));
                $notifiedUsers->push($user->id);
            }
        }
    }
}
