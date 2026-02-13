<?php

declare(strict_types=1);

namespace App\Services\Budget;

use App\Models\Role;
use App\Models\User;
use App\Models\Directorate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Project\ProjectRepository;

class ProjectAccessService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository
    ) {}

    public function getUserProjects(?User $user = null, ?int $directorateId = null): Collection
    {
        return $this->projectRepository->getUserAccessibleProjects($user, $directorateId);
    }

    public function getDirectorateTitle(?User $user = null): string
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return 'All Directorates';
        }

        $roleIds = $user->roles->pluck('id')->toArray();

        if (in_array(Role::DIRECTORATE_USER, $roleIds) && $user->directorate_id) {
            return $user->directorate->title ?? 'Unknown Directorate';
        }

        return 'All Directorates';
    }

    public function getDirectoratesForFilter(?User $user = null, ?int $selectedDirectorateId = null): array
    {
        $user = $user ?? Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();

        // Directorate users can't filter - locked to their directorate
        if (in_array(Role::DIRECTORATE_USER, $roleIds) && $user->directorate_id) {
            return [];
        }

        // SuperAdmin and Admin can filter
        return Directorate::orderBy('title')->get()->map(function ($d) use ($selectedDirectorateId) {
            return [
                'value' => $d->id,
                'label' => $d->title,
                'selected' => $selectedDirectorateId == $d->id,
            ];
        })->prepend([
            'value' => '',
            'label' => trans('global.all_directorates') ?? 'All Directorates',
            'selected' => !$selectedDirectorateId,
        ])->toArray();
    }
}
