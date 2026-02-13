<?php

declare(strict_types=1);

namespace App\Repositories\Task;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TaskRepository
{
    public function __construct(
        private readonly Task $model
    ) {}

    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function findById(int $id, array $relations = []): ?Task
    {
        return $this->model->with($relations)->find($id);
    }

    public function create(array $data): Task
    {
        return $this->model->create($data);
    }

    public function update(Task $task, array $data): bool
    {
        return $task->update($data);
    }

    public function delete(Task $task): bool
    {
        return $task->delete();
    }

    public function getWithRelations(array $relations = []): Collection
    {
        return $this->model->with($relations)->latest()->get();
    }

    public function applyRoleFilters(Builder $query, User $user, array $roleIds, ?int $directorateId = null): Builder
    {
        if ($this->isSuperAdminOrAdmin($roleIds)) {
            if ($directorateId) {
                $query->where('directorate_id', $directorateId);
            }
            return $query;
        }

        if ($this->isDirectorateUser($roleIds) && $user->directorate_id) {
            return $query->where('directorate_id', $user->directorate_id);
        }

        if ($this->isDepartmentUser($roleIds) && $user->directorate_id) {
            return $this->applyDepartmentUserFilter($query, $user);
        }

        if ($this->isProjectUser($roleIds)) {
            return $this->applyProjectUserFilter($query, $user);
        }

        return $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['priority_id'])) {
            $query->where('priority_id', $filters['priority_id']);
        }

        if (!empty($filters['project_id'])) {
            if ($filters['project_id'] === 'none') {
                $query->whereDoesntHave('projects');
            } else {
                $query->whereHas('projects', fn($q) => $q->where('projects.id', $filters['project_id']));
            }
        }

        if (!empty($filters['date_start']) && !empty($filters['date_end'])) {
            $this->applyDateRangeFilter($query, $filters['date_start'], $filters['date_end']);
        }

        return $query;
    }

    public function syncProjects(Task $task, array $projectIds, array $pivotData): void
    {
        $syncData = [];
        foreach ($projectIds as $projectId) {
            $syncData[$projectId] = array_merge($pivotData, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $task->projects()->sync($syncData);
    }

    public function syncUsers(Task $task, array $userIds): void
    {
        $task->users()->sync($userIds);
    }

    public function updateProjectPivot(Task $task, int $projectId, array $data): void
    {
        $task->projects()->updateExistingPivot($projectId, array_merge($data, [
            'updated_at' => now(),
        ]));
    }

    private function isSuperAdminOrAdmin(array $roleIds): bool
    {
        return in_array(\App\Models\Role::SUPERADMIN, $roleIds)
            || in_array(\App\Models\Role::ADMIN, $roleIds);
    }

    private function isDirectorateUser(array $roleIds): bool
    {
        return in_array(\App\Models\Role::DIRECTORATE_USER, $roleIds);
    }

    private function isDepartmentUser(array $roleIds): bool
    {
        return in_array(\App\Models\Role::DEPARTMENT_USER, $roleIds);
    }

    private function isProjectUser(array $roleIds): bool
    {
        return in_array(\App\Models\Role::PROJECT_USER, $roleIds);
    }

    private function applyDepartmentUserFilter(Builder $query, User $user): Builder
    {
        $departmentIds = \App\Models\Department::whereHas(
            'directorates',
            fn($q) => $q->where('directorates.id', $user->directorate_id)
        )->pluck('id');

        if ($departmentIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('department_id', $departmentIds);
    }

    private function applyProjectUserFilter(Builder $query, User $user): Builder
    {
        $projectIds = $user->projects()->whereNull('deleted_at')->pluck('id');

        if ($projectIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'projects',
            fn($q) => $q->whereIn('projects.id', $projectIds)->whereNull('deleted_at')
        );
    }

    private function applyDateRangeFilter(Builder $query, string $startDate, string $endDate): void
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $query->where(function ($query) use ($start, $end) {
            $query->whereBetween('start_date', [$start, $end])
                ->orWhereBetween('due_date', [$start, $end])
                ->orWhere(function ($query) use ($start, $end) {
                    $query->where('start_date', '<=', $end)
                        ->where('due_date', '>=', $start);
                });
        });
    }
}
