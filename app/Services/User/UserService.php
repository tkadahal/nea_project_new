<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Repositories\User\UserRepository;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function getIndexData(): array
    {
        $user = Auth::user();
        $isSuperAdminOrAdmin = $user->hasRole(Role::SUPERADMIN) || $user->hasRole(Role::ADMIN);

        return [
            'headers' => $this->getTableHeaders(),
            'data' => [],
            'users' => collect(),
            'routePrefix' => 'admin.user',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this user?',
            'arrayColumnColor' => 'purple',
            'projectManager' => $user->isProjectManager(),
            'isSuperAdminOrAdmin' => $isSuperAdminOrAdmin,
        ];
    }

    public function getFilteredUsersData(
        int $perPage = 20,
        ?string $roleFilter = null,
        ?string $directorateFilter = null,
        ?string $search = null
    ): array {
        try {
            $authUser = Auth::user();

            // Get ALL users (now pre-sorted by repository)
            $users = $this->userRepository->getAllUsers(
                perPage: $perPage,
                roleFilter: $roleFilter,
                directorateFilter: $directorateFilter,
                search: $search
            );

            $transformedData = $users->getCollection()->map(function ($user) use ($authUser) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('title')->toArray() ?? [],
                    'directorate_id' => $user->directorate ? $user->directorate->title : 'N/A',
                    'can_access' => $this->canUserAccess($authUser, $user),
                ];
            })->values()->toArray();

            return [
                'data' => $transformedData,
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ];
        } catch (\Exception $e) {
            report($e);
            throw $e;
        }
    }

    private function canUserAccess(User $authUser, User $targetUser): bool
    {
        $roleIds = $authUser->roles->pluck('id')->toArray();

        // SuperAdmin and Admin can access all users
        if (in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds)) {
            return true;
        }

        // Directorate User can access users in their directorate
        if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            return $targetUser->directorate_id === $authUser->directorate_id;
        }

        // Project User can access users who share at least one project
        if (in_array(Role::PROJECT_USER, $roleIds)) {
            $authUserProjectIds = $authUser->projects()->pluck('projects.id')->toArray();
            $targetUserProjectIds = $targetUser->projects()->pluck('projects.id')->toArray();

            // Can access if they share any project OR if it's themselves
            return !empty(array_intersect($authUserProjectIds, $targetUserProjectIds))
                || $targetUser->id === $authUser->id;
        }

        // Default: can only access themselves
        return $targetUser->id === $authUser->id;
    }

    private function getTableHeaders(): array
    {
        return [
            trans('global.user.fields.id'),
            trans('global.user.fields.name'),
            trans('global.user.fields.email'),
            trans('global.user.fields.roles'),
            trans('global.user.fields.directorate_id'),
        ];
    }
}
