<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Models\Task;
use App\Models\User;
use App\DTOs\Task\TaskDTO;
use App\Helpers\Task\TaskHelper;
use App\Notifications\TaskCreated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Task\TaskRepository;
use App\Notifications\Task\NotificationService;

class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TaskHelper $taskHelper,
        private readonly NotificationService $notificationService
    ) {}

    public function getFilteredTasks(array $filters, User $user, array $roleIds): Collection
    {
        $relations = [
            'priority',
            'projects' => fn($query) => $query->withPivot('status_id', 'progress'),
            'users',
            'directorate',
            'parent',
            'subTasks'
        ];

        $query = $this->taskRepository->query()->with($relations)->latest();
        $query = $this->taskRepository->applyRoleFilters($query, $user, $roleIds, $filters['directorate_id'] ?? null);
        $query = $this->taskRepository->applyFilters($query, $filters);

        return $query->get();
    }

    public function createTask(array $validated, User $user): Task
    {
        return DB::transaction(function () use ($validated, $user) {
            $validated['assigned_by'] = $user->id;

            // Extract subtasks and prepare task data
            $subtasks = isset($validated['subtasks']) ? json_decode($validated['subtasks'], true) : [];
            $taskData = array_diff_key($validated, array_flip(['progress', 'projects', 'users', 'subtasks']));

            // Create main task
            $task = $this->taskRepository->create($taskData);

            // Sync projects
            if (!empty($validated['projects'])) {
                $this->taskRepository->syncProjects($task, $validated['projects'], [
                    'status_id' => $validated['status_id'],
                    'progress' => $validated['progress'] ?? null,
                ]);
            }

            // Sync users
            $this->taskRepository->syncUsers($task, $validated['users'] ?? []);

            // Create subtasks
            if (!empty($subtasks)) {
                $this->createSubtasks($task, $subtasks, $validated);
            }

            // Send notifications
            //$this->notificationService->notifyTaskCreated($task, $validated);

            return $task;
        });
    }

    public function updateTask(Task $task, array $validated, ?int $projectId = null): Task
    {
        return DB::transaction(function () use ($task, $validated, $projectId) {
            // Update task data
            $taskData = array_diff_key($validated, array_flip(['status_id', 'progress', 'projects', 'users']));
            $this->taskRepository->update($task, $taskData);

            // Sync projects
            if (!empty($validated['projects'])) {
                $projectSyncData = $this->taskHelper->prepareProjectSyncData(
                    $validated['projects'],
                    $validated,
                    $task,
                    $projectId
                );
                $task->projects()->sync($projectSyncData);
            } else {
                $task->projects()->detach();
            }

            // Update project-specific data
            if ($projectId) {
                $this->updateProjectSpecificData($task, $projectId, $validated);
            } else {
                $this->taskRepository->update($task, [
                    'status_id' => $validated['status_id'] ?? $task->status_id,
                    'progress' => $validated['progress'] ?? $task->progress,
                ]);
            }

            // Sync users
            if (isset($validated['users'])) {
                $this->taskRepository->syncUsers($task, $validated['users']);
            }

            return $task->fresh();
        });
    }

    public function updateTaskStatus(int $taskId, int $statusId, ?int $projectId = null): void
    {
        $task = $this->taskRepository->findById($taskId, ['subTasks']);

        if (!$task) {
            throw new \Exception('Task not found');
        }

        if ($task->subTasks()->exists()) {
            throw new \Exception('This task has subtasks, please update the subtask first');
        }

        DB::transaction(function () use ($task, $statusId, $projectId) {
            if ($task->parent_id) {
                // For subtasks, update both tasks table and pivot
                $this->taskRepository->update($task, ['status_id' => $statusId]);
                if ($projectId) {
                    $this->taskRepository->updateProjectPivot($task, $projectId, ['status_id' => $statusId]);
                }
            } else {
                // For main tasks
                if ($projectId) {
                    $this->taskRepository->updateProjectPivot($task, $projectId, ['status_id' => $statusId]);
                } else {
                    $this->taskRepository->update($task, ['status_id' => $statusId]);
                }
            }
        });
    }

    public function transformForView(Collection $tasks, string $view, array $statusColors, array $priorityColors): array
    {
        return match ($view) {
            'board' => $this->taskHelper->transformForBoardView($tasks, $statusColors, $priorityColors),
            'list' => $this->taskHelper->transformForListView($tasks, $statusColors, $priorityColors),
            'calendar' => $this->taskHelper->transformForCalendarView($tasks, $statusColors),
            'table' => $this->taskHelper->transformForTableView($tasks, $statusColors, $priorityColors),
            default => [],
        };
    }

    private function createSubtasks(Task $parentTask, array $subtasks, array $parentValidated): void
    {
        $projectSyncData = [];
        if (!empty($parentValidated['projects'])) {
            foreach ($parentValidated['projects'] as $projectId) {
                $projectSyncData[$projectId] = [
                    'status_id' => $parentValidated['status_id'],
                    'progress' => $parentValidated['progress'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach ($subtasks as $subtaskData) {
            if (!empty($subtaskData['title'])) {
                $subtask = $this->taskRepository->create([
                    'title' => $subtaskData['title'],
                    'status_id' => $subtaskData['completed'] ? 2 : 1,
                    'parent_id' => $parentTask->id,
                    'directorate_id' => $parentTask->directorate_id,
                    'department_id' => $parentTask->department_id,
                    'assigned_by' => Auth::id(),
                    'start_date' => $parentTask->start_date,
                    'due_date' => $parentTask->due_date,
                    'priority_id' => $parentTask->priority_id,
                ]);

                if (!empty($projectSyncData)) {
                    $subtask->projects()->sync($projectSyncData);
                }

                $this->taskRepository->syncUsers($subtask, $parentValidated['users'] ?? []);
            }
        }
    }

    private function updateProjectSpecificData(Task $task, int $projectId, array $validated): void
    {
        $projectTask = $task->projects()->where('project_id', $projectId)->first();

        if ($projectTask) {
            $this->taskRepository->updateProjectPivot($task, $projectId, [
                'status_id' => $validated['status_id'] ?? $projectTask->pivot->status_id,
                'progress' => $validated['progress'] ?? $projectTask->pivot->progress,
            ]);
        }
    }
}
