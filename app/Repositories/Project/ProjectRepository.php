<?php

declare(strict_types=1);

namespace App\Repositories\Project;

use App\Models\Role;
use App\Models\User;
use App\Models\Project;
use App\Models\Directorate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectRepository
{
    /**
     * Get projects for the authenticated user with role-based filtering
     * OPTIMIZED: Uses select(), chunk processing, and eager loading
     */
    public function getProjectsForUser(
        User $user,
        bool $withRelations = true,
        ?int $paginate = null
    ): Collection|LengthAwarePaginator {
        $query = Project::query()
            ->select([
                'id',
                'title',
                'description',
                'directorate_id',
                'budget_heading_id',
                'start_date',
                'end_date',
                //'budget',
                //'total_budget',
                'priority_id',
                'progress',
                'project_manager',
                'status_id'
            ])
            ->orderBy('id', 'desc');

        // Apply role-based filtering
        $roleIds = $user->roles->pluck('id')->toArray();

        if (!in_array(Role::SUPERADMIN, $roleIds) && !in_array(Role::ADMIN, $roleIds)) {
            if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                $query->where('directorate_id', $user->directorate_id ?? 0);
            } else {
                $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
            }
        }

        // Eager load only necessary relationships
        if ($withRelations) {
            $query->with([
                'directorate:id,title',
                'priority:id,title',
                'projectManager:id,name',
                'status:id,title',
                'budgetHeading:id,title',
            ])->withCount('comments');
        }

        // Return paginated or all results
        return $paginate ? $query->paginate($paginate) : $query->get();
    }

    /**
     * Get filtered projects with pagination - OPTIMIZED for AJAX
     */
    public function getFilteredProjects(
        User $user,
        int $perPage = 12,
        ?string $directorateId = null,
        ?string $search = null
    ): LengthAwarePaginator {
        $query = Project::query()
            ->select([
                'id',
                'title',
                'description',
                'directorate_id',
                'budget_heading_id',
                'start_date',
                'end_date',
                //'budget',
                //'total_budget',
                'priority_id',
                'progress',
                'project_manager',
                'status_id'
            ])
            ->orderBy('id', 'desc');

        // Apply role-based filtering
        $roleIds = $user->roles->pluck('id')->toArray();

        if (!in_array(Role::SUPERADMIN, $roleIds) && !in_array(Role::ADMIN, $roleIds)) {
            if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                $query->where('directorate_id', $user->directorate_id ?? 0);
            } else {
                $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
            }
        }

        // Apply directorate filter
        if ($directorateId) {
            $query->where('directorate_id', $directorateId);
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Eager load only necessary relationships
        $query->with([
            'directorate:id,title',
            'priority:id,title',
            'projectManager:id,name',
            'status:id,title',
            'budgetHeading:id,title',
        ])->withCount('comments');

        return $query->paginate($perPage);
    }

    /**
     * Create a new project
     */
    public function create(array $data): Project
    {
        return Project::create($data);
    }

    /**
     * Update an existing project
     */
    public function update(Project $project, array $data): bool
    {
        return $project->update($data);
    }

    /**
     * Delete a project
     */
    public function delete(Project $project): bool
    {
        return $project->delete();
    }

    /**
     * Get directorates for dropdown
     */
    public function getDirectoratesForDropdown(): Collection
    {
        return Directorate::pluck('title', 'id');
    }

    /**
     * Get projects count by directorate (for dashboards)
     */
    public function getProjectCountByDirectorate(): Collection
    {
        return Project::query()
            ->select('directorate_id', DB::raw('count(*) as count'))
            ->groupBy('directorate_id')
            ->pluck('count', 'directorate_id');
    }
}
