<?php

namespace App\Repositories\Task;

use App\Models\Task;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TaskRepository
{
    public function baseQuery(): Builder
    {
        return Task::with([
            'priority',
            'projects' => fn($q) => $q->withPivot('status_id', 'progress'),
            'users',
            'directorate',
            'parent',
            'subTasks',
        ])->latest();
    }

    public function applyRoleScope(Builder $query, $user): Builder
    {
        $roles = $user->roles->pluck('id')->toArray();

        return match (true) {
            in_array(Role::SUPERADMIN, $roles),
            in_array(Role::ADMIN, $roles) => $query,

            in_array(Role::DIRECTORATE_USER, $roles) && $user->directorate_id =>
            $query->where('directorate_id', $user->directorate_id),

            in_array(Role::DEPARTMENT_USER, $roles) && $user->directorate_id =>
            $this->departmentScope($query, $user->directorate_id),

            in_array(Role::PROJECT_USER, $roles) =>
            $this->projectScope($query, $user),

            default =>
            $query->whereHas('users', fn($q) => $q->where('users.id', $user->id)),
        };
    }

    private function departmentScope(Builder $query, int $directorateId): Builder
    {
        $departmentIds = Department::whereHas(
            'directorates',
            fn($q) => $q->where('directorates.id', $directorateId)
        )->pluck('id');

        return $departmentIds->isEmpty()
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('department_id', $departmentIds);
    }

    private function projectScope(Builder $query, $user): Builder
    {
        $projectIds = $user->projects()->whereNull('deleted_at')->pluck('id');

        return $projectIds->isEmpty()
            ? $query->whereRaw('1 = 0')
            : $query->whereHas(
                'projects',
                fn($q) =>
                $q->whereIn('projects.id', $projectIds)
                    ->whereNull('deleted_at')
            );
    }

    public function get(): Collection
    {
        return $this->baseQuery()->get();
    }
}
