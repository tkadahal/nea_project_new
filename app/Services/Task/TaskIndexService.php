<?php

namespace App\Services\Task;

use App\DTOs\Task\TaskFilterDTO;
use App\Helpers\Task\TaskViewFormatter;
use App\Repositories\Task\TaskRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Helpers\Task\TaskItemNormalizer;
use App\Models\{
    Status,
    Priority,
    Directorate,
    Department,
    Project
};

class TaskIndexService
{
    public function __construct(
        private readonly TaskRepository $taskRepository
    ) {}

    public function handle(Request $request): array
    {
        $user = Auth::user();
        $dto  = TaskFilterDTO::fromRequest($request);

        $statuses   = Cache::remember('task_statuses', 86400, fn() => Status::all());
        $priorities = Cache::remember('task_priorities', 86400, fn() => Priority::all());

        $statusColors   = $statuses->pluck('color', 'id')->toArray();
        $priorityColors = config('colors.priority');

        $query = $this->taskRepository
            ->applyRoleScope(
                $this->taskRepository->baseQuery(),
                $user
            );

        // simple filters
        if ($dto->directorateId) $query->where('directorate_id', $dto->directorateId);
        if ($dto->departmentId)  $query->where('department_id', $dto->departmentId);
        if ($dto->priorityId)    $query->where('priority_id', $dto->priorityId);

        $tasks = TaskItemNormalizer::normalize(
            $query->get()
        );

        return [
            'activeView' => $dto->activeView,
            'statuses' => $statuses,
            'priorities' => $priorities,
            'directorates' => Directorate::all(),
            'departments' => Cache::remember('departments', 86400, fn() => Department::all()),
            'projectsForFilter' => Cache::remember(
                'projects_for_filter',
                86400,
                fn() => Project::whereNull('deleted_at')->get()
            ),
            'statusColors' => $statusColors,
            'priorityColors' => $priorityColors,
            'routePrefix' => 'admin.task',
            'deleteConfirmationMessage' => 'Are you sure you want to delete this task?',
            'actions' => ['view', 'edit', 'delete'],

            ...TaskViewFormatter::format(
                $tasks,
                $dto->activeView,
                $statusColors,
                $priorityColors
            ),
        ];
    }
}
