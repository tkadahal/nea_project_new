<?php

declare(strict_types=1);

namespace App\Queries\Project;

use App\Models\Project;
use App\Models\User;
use App\Models\Role;

class ProjectIndexQuery
{
    public static function forUser(User $user)
    {
        $query = Project::with([
            'directorate',
            'priority',
            'projectManager',
            'status',
            'budgets',
            'comments'
        ])->withCount('comments')->latest();

        $roleIds = $user->roles->pluck('id')->toArray();

        if (! in_array(Role::SUPERADMIN, $roleIds) && ! in_array(Role::ADMIN, $roleIds)) {
            if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                $query->where('directorate_id', $user->directorate_id ?? 0);
            } else {
                $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
            }
        }

        return $query;
    }
}
