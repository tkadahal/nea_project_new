<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Models\Department;
use App\Models\Directorate;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class AccessControlService
{
    public function getUserAccessibleDirectorates(User $user, array $roleIds): Collection
    {
        if ($this->isSuperAdminOrAdmin($roleIds)) {
            return Directorate::all();
        }

        if ($this->isDirectorateUser($roleIds) && $user->directorate_id) {
            return Directorate::where('id', $user->directorate_id)->get();
        }

        return collect();
    }

    public function getUserAccessibleDepartments(User $user, array $roleIds, ?int $directorateId = null): Collection
    {
        if ($this->isSuperAdminOrAdmin($roleIds)) {
            return $directorateId
                ? Department::whereHas('directorates', fn($q) => $q->where('directorates.id', $directorateId))->get()
                : Department::all();
        }

        if (($this->isDirectorateUser($roleIds) || $this->isProjectUser($roleIds)) && $user->directorate_id) {
            $targetDirectorateId = $directorateId ?? $user->directorate_id;

            if ($targetDirectorateId != $user->directorate_id) {
                return collect();
            }

            return Department::whereHas('directorates', fn($q) => $q->where('directorates.id', $targetDirectorateId))
                ->get();
        }

        return collect();
    }

    public function getUserAccessibleProjects(User $user, array $roleIds, ?int $directorateId = null): Collection
    {
        if ($this->isSuperAdminOrAdmin($roleIds)) {
            return $directorateId
                ? Project::where('directorate_id', $directorateId)->whereNull('deleted_at')->get()
                : Project::whereNull('deleted_at')->get();
        }

        if ($this->isDirectorateUser($roleIds) && $user->directorate_id) {
            $targetDirectorateId = $directorateId ?? $user->directorate_id;

            return Project::where('directorate_id', $targetDirectorateId)
                ->whereNull('deleted_at')
                ->get();
        }

        if ($this->isProjectUser($roleIds)) {
            $query = $user->projects()->whereNull('deleted_at');

            if ($directorateId) {
                $query->where('directorate_id', $directorateId);
            }

            return $query->get();
        }

        return collect();
    }

    public function getUserAccessibleUsers(User $user, array $roleIds, ?int $directorateId = null, ?int $departmentId = null, array $projectIds = []): Collection
    {
        if ($this->isSuperAdminOrAdmin($roleIds)) {
            return $this->getUsersByContext($directorateId, $departmentId, $projectIds);
        }

        if (($this->isDirectorateUser($roleIds) || $this->isProjectUser($roleIds)) && $user->directorate_id) {
            $targetDirectorateId = $directorateId ?? $user->directorate_id;

            if ($targetDirectorateId != $user->directorate_id) {
                return collect();
            }

            return $this->getUsersByContext($targetDirectorateId, $departmentId, $projectIds);
        }

        return collect();
    }

    public function canAccessProject(User $user, array $roleIds, Project $project): bool
    {
        if ($this->isSuperAdminOrAdmin($roleIds)) {
            return true;
        }

        if ($this->isDirectorateUser($roleIds) && $user->directorate_id) {
            return $project->directorate_id == $user->directorate_id;
        }

        if ($this->isProjectUser($roleIds)) {
            return $user->projects()->where('id', $project->id)->exists();
        }

        return false;
    }

    public function isSuperAdminOrAdmin(array $roleIds): bool
    {
        return in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds);
    }

    public function isDirectorateUser(array $roleIds): bool
    {
        return in_array(Role::DIRECTORATE_USER, $roleIds);
    }

    public function isDepartmentUser(array $roleIds): bool
    {
        return in_array(Role::DEPARTMENT_USER, $roleIds);
    }

    public function isProjectUser(array $roleIds): bool
    {
        return in_array(Role::PROJECT_USER, $roleIds);
    }

    private function getUsersByContext(?int $directorateId, ?int $departmentId, array $projectIds): Collection
    {
        $query = User::query();

        if (!empty($projectIds)) {
            return $query->whereHas('projects', fn($q) => $q->whereIn('projects.id', $projectIds))
                ->select('id', 'name')
                ->get();
        }

        if ($departmentId) {
            $department = Department::find($departmentId);
            if ($department) {
                $directorateIds = $department->directorates()->pluck('directorates.id');
                return $query->whereIn('directorate_id', $directorateIds)
                    ->whereHas('roles', fn($q) => $q->where('id', Role::DEPARTMENT_USER))
                    ->select('id', 'name')
                    ->get();
            }
        }

        if ($directorateId) {
            return $query->where('directorate_id', $directorateId)
                ->whereHas('roles', fn($q) => $q->where('id', Role::DIRECTORATE_USER))
                ->select('id', 'name')
                ->get();
        }

        return $query->select('id', 'name')->get();
    }
}
