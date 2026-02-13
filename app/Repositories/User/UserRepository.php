<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function getAllUsers(
        int $perPage = 20,
        ?string $roleFilter = null,
        ?string $directorateFilter = null,
        ?string $search = null
    ): LengthAwarePaginator {
        $authUser = Auth::user();
        $roleIds = $authUser->roles->pluck('id')->toArray();

        $isSuperAdminOrAdmin = in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds);

        // Get all users without pagination first
        $query = User::query()
            ->select(['id', 'name', 'email', 'directorate_id'])
            ->with(['roles:id,title', 'directorate:id,title', 'projects:id,title']);

        // Filter out Super Admin and Admin users if current user is not Super Admin or Admin
        if (!$isSuperAdminOrAdmin) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->whereIn('roles.id', [Role::SUPERADMIN, Role::ADMIN]);
            });
        }

        // Apply filters
        if ($roleFilter) {
            $query->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('roles.id', $roleFilter);
            });
        }

        if ($directorateFilter) {
            $query->where('directorate_id', $directorateFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Get all results
        $allUsers = $query->get();

        // Determine accessible users based on role
        $accessibleUserIds = $this->getAccessibleUserIds($authUser, $roleIds, $allUsers);

        // Sort: accessible users first, then others
        $sortedUsers = $allUsers->sortByDesc(function ($user) use ($accessibleUserIds) {
            return in_array($user->id, $accessibleUserIds) ? 1 : 0;
        })->values();

        // Manual pagination
        $currentPage = request()->input('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $itemsForCurrentPage = $sortedUsers->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $itemsForCurrentPage,
            $sortedUsers->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    private function getAccessibleUserIds(User $authUser, array $roleIds, $allUsers): array
    {
        // SuperAdmin and Admin can access all users
        if (in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds)) {
            return $allUsers->pluck('id')->toArray();
        }

        $accessibleIds = [];

        // Directorate User can access users in their directorate
        if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            $accessibleIds = $allUsers->filter(function ($user) use ($authUser) {
                return $user->directorate_id === $authUser->directorate_id;
            })->pluck('id')->toArray();
        }

        // Project User can access users who share at least one project
        if (in_array(Role::PROJECT_USER, $roleIds)) {
            $authUserProjectIds = $authUser->projects()->pluck('projects.id')->toArray();

            $accessibleIds = $allUsers->filter(function ($user) use ($authUserProjectIds, $authUser) {
                $targetUserProjectIds = $user->projects->pluck('id')->toArray();
                return !empty(array_intersect($authUserProjectIds, $targetUserProjectIds))
                    || $user->id === $authUser->id;
            })->pluck('id')->toArray();
        }

        // If no specific role matched, can only access themselves
        if (empty($accessibleIds)) {
            $accessibleIds = [$authUser->id];
        }

        return $accessibleIds;
    }

    public function getFilteredUsers(
        User $user,
        int $perPage = 20,
        ?string $roleFilter = null,
        ?string $directorateFilter = null,
        ?string $search = null
    ): LengthAwarePaginator {
        $query = User::query()
            ->select(['id', 'name', 'email', 'directorate_id'])
            ->with(['roles:id,title', 'directorate:id,title'])
            ->latest();

        $query->applyRoleFilter();

        if ($roleFilter) {
            $query->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('roles.id', $roleFilter);
            });
        }

        if ($directorateFilter) {
            $query->where('directorate_id', $directorateFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function getRolesForDropdown(): mixed
    {
        return \App\Models\Role::pluck('title', 'id');
    }

    public function getDirectoratesForDropdown(): mixed
    {
        return \App\Models\Directorate::pluck('title', 'id');
    }
}
