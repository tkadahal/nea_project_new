<?php

declare(strict_types=1);

namespace App\Trait;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait RoleBasedAccess
{
    /**
     * Apply role-based filtering to query (for User model)
     */
    public function scopeApplyRoleFilter(Builder $query): Builder
    {
        $user = Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        $roleIds = $user->roles()->pluck('id')->toArray();

        if (in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds)) {
            return $query;
        }

        if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            $directorateId = $user->directorate ? [$user->directorate->id] : [];

            return $query->whereHas('directorate', function ($q) use ($directorateId) {
                $q->whereIn('directorate_id', $directorateId);
            });
        }

        if (in_array(Role::PROJECT_USER, $roleIds)) {
            $projectIds = $user->projects()->pluck('projects.id')->toArray();

            return $query->where(function ($q) use ($projectIds, $user) {
                $q->where('users.id', $user->id)
                    ->orWhereHas('projects', function ($subQ) use ($projectIds) {
                        $subQ->whereIn('projects.id', $projectIds);
                    });
            });
        }

        return $query->where('id', $user->id);
    }

    /**
     * Apply role-based filtering for resources (generic for any model)
     */
    public function scopeApplyResourceAccessFilter(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        $roleIds = Auth::user()->roles()->pluck('id')->toArray();

        // SuperAdmin and Admin can see everything
        if (in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds)) {
            return $query;
        }

        // Get model table name to check relationships
        $model = $query->getModel();
        $table = $model->getTable();

        // Directorate User can see resources in their directorate
        if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            // Direct directorate_id column
            if (in_array('directorate_id', $model->getFillable()) || $model->isFillable('directorate_id')) {
                return $query->where('directorate_id', $user->directorate_id);
            }

            // Has project relationship with directorate
            if (method_exists($model, 'project')) {
                return $query->whereHas('project', function ($q) use ($user) {
                    $q->where('directorate_id', $user->directorate_id);
                });
            }
        }

        // Project User can see resources in their projects
        if (in_array(Role::PROJECT_USER, $roleIds)) {
            $projectIds = Auth::user()->projects()->pluck('projects.id')->toArray();

            if (empty($projectIds)) {
                return $query->whereRaw('1 = 0');
            }

            // Has many-to-many projects relationship
            if (method_exists($model, 'projects')) {
                return $query->whereHas('projects', function ($q) use ($projectIds) {
                    $q->whereIn('projects.id', $projectIds);
                });
            }

            // Has direct project_id column (like Budget)
            if (in_array('project_id', $model->getFillable()) || $model->isFillable('project_id')) {
                return $query->whereIn('project_id', $projectIds);
            }
        }

        // Default: only own resources (if model has user_id or created_by)
        if (in_array('user_id', $model->getFillable()) || $model->isFillable('user_id')) {
            return $query->where('user_id', $user->id);
        }

        if (in_array('created_by', $model->getFillable()) || $model->isFillable('created_by')) {
            return $query->where('created_by', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Check if authenticated user can access a resource
     */
    public static function canAccessResource(User $authUser, Model $resource, bool $allowSelf = false): bool
    {
        // Check if it's the same user (for User model)
        if ($resource instanceof User) {
            if ($authUser->id === $resource->id) {
                return $allowSelf;
            }
        }

        $roleIds = $authUser->roles->pluck('id')->toArray();

        // SuperAdmin can access everything
        if (in_array(Role::SUPERADMIN, $roleIds)) {
            return true;
        }

        // Admin can access everything except Super Admin users
        if (in_array(Role::ADMIN, $roleIds)) {
            if ($resource instanceof User) {
                $targetRoleIds = $resource->roles->pluck('id')->toArray();
                if (in_array(Role::SUPERADMIN, $targetRoleIds)) {
                    return false;
                }
            }
            return true;
        }

        // For User model specifically
        if ($resource instanceof User) {
            // Cannot manage Admin or Super Admin users
            $targetRoleIds = $resource->roles->pluck('id')->toArray();
            if (in_array(Role::SUPERADMIN, $targetRoleIds) || in_array(Role::ADMIN, $targetRoleIds)) {
                return false;
            }

            // Directorate User can access users in their directorate
            if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                return $resource->directorate_id === $authUser->directorate_id;
            }

            // Project User can access users who share projects
            if (in_array(Role::PROJECT_USER, $roleIds)) {
                $authUserProjectIds = $authUser->projects()->pluck('projects.id')->toArray();
                $targetUserProjectIds = $resource->projects()->pluck('projects.id')->toArray();
                return !empty(array_intersect($authUserProjectIds, $targetUserProjectIds));
            }
        }

        // For other models with directorate_id
        if (in_array('directorate_id', $resource->getFillable())) {
            if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                return $resource->directorate_id === $authUser->directorate_id;
            }
        }

        // For models with project relationship (many-to-many)
        if (method_exists($resource, 'projects')) {
            if (in_array(Role::PROJECT_USER, $roleIds)) {
                $authUserProjectIds = $authUser->projects()->pluck('projects.id')->toArray();
                $resourceProjectIds = $resource->projects()->pluck('projects.id')->toArray();
                return !empty(array_intersect($authUserProjectIds, $resourceProjectIds));
            }
        }

        // For models with project_id (belongs to single project) - like Budget
        if (in_array('project_id', $resource->getFillable())) {
            if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                // Check if budget's project belongs to user's directorate
                if (method_exists($resource, 'project') && $resource->project) {
                    return $resource->project->directorate_id === $authUser->directorate_id;
                }
            }

            if (in_array(Role::PROJECT_USER, $roleIds)) {
                $authUserProjectIds = $authUser->projects()->pluck('projects.id')->toArray();
                return in_array($resource->project_id, $authUserProjectIds);
            }
        }

        // Check ownership (user_id or created_by)
        if (in_array('user_id', $resource->getFillable())) {
            return $resource->user_id === $authUser->id;
        }

        if (in_array('created_by', $resource->getFillable())) {
            return $resource->created_by === $authUser->id;
        }

        return false;
    }

    /**
     * Check if user can view resource (allows self for User model)
     */
    public static function canViewResource(User $authUser, Model $resource): bool
    {
        // For User model, can view self
        if ($resource instanceof User && $authUser->id === $resource->id) {
            return true;
        }

        return self::canAccessResource($authUser, $resource, false);
    }

    /**
     * Check if user can edit/update resource (disallows self for User model)
     */
    public static function canEditResource(User $authUser, Model $resource): bool
    {
        return self::canAccessResource($authUser, $resource, false);
    }

    /**
     * Check if user can delete resource (disallows self for User model)
     */
    public static function canDeleteResource(User $authUser, Model $resource): bool
    {
        return self::canAccessResource($authUser, $resource, false);
    }

    /**
     * Get accessible project IDs for the current user
     */
    public static function getAccessibleProjectIds(?User $user = null): array
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return [];
        }

        $roleIds = Auth::user()->roles()->pluck('id')->toArray();

        // SuperAdmin and Admin can see all projects
        if (in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds)) {
            return \App\Models\Project::pluck('id')->toArray();
        }

        // Directorate User can see projects in their directorate
        if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            return \App\Models\Project::where('directorate_id', $user->directorate_id)
                ->pluck('id')
                ->toArray();
        }

        // Project User can see their assigned projects
        if (in_array(Role::PROJECT_USER, $roleIds)) {
            return Auth::user()->projects()->pluck('projects.id')->toArray();
        }

        return [];
    }

    /**
     * Get accessible directorate IDs for the current user
     */
    public static function getAccessibleDirectorateIds(?User $user = null): array
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return [];
        }

        $roleIds = Auth::user()->roles()->pluck('id')->toArray();

        // SuperAdmin and Admin can see all directorates
        if (in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds)) {
            return \App\Models\Directorate::pluck('id')->toArray();
        }

        // Directorate User can see their own directorate
        if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            return $user->directorate_id ? [$user->directorate_id] : [];
        }

        // Project User can see directorates of their projects
        if (in_array(Role::PROJECT_USER, $roleIds)) {
            return Auth::user()->projects()
                ->with('directorate')
                ->get()
                ->pluck('directorate.id')
                ->unique()
                ->filter()
                ->values()
                ->toArray();
        }

        return [];
    }
}
