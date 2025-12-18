<?php

declare(strict_types=1);

namespace App\ViewModels\Project;

use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use App\Models\Priority;
use App\Models\FiscalYear;
use App\Models\Directorate;
use App\Models\BudgetHeading;
use App\Models\Department;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class ProjectFormData
{
    public static function create(): array
    {
        return self::base();
    }

    public static function edit(Project $project): array
    {
        $data = self::base();

        $project->load(['budgets', 'files']);

        $data['project'] = $project;
        $data['departments'] = self::departments($project->directorate_id);
        $data['users'] = self::usersForEdit($project);

        return $data;
    }

    private static function base(): array
    {
        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();

        return [
            'directorates'   => self::directorates($user, $roleIds),
            'departments'    => collect(),
            'users'          => collect(),
            'statuses'       => Status::pluck('title', 'id'),
            'priorities'     => Priority::pluck('title', 'id'),
            'fiscalYears'    => FiscalYear::pluck('title', 'id'),
            'budgetHeadings' => BudgetHeading::pluck('title', 'id'),
        ];
    }

    private static function directorates($user, array $roleIds)
    {
        if (in_array(Role::SUPERADMIN, $roleIds)) {
            return Directorate::pluck('title', 'id');
        }

        if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            return Directorate::where('id', $user->directorate_id)
                ->pluck('title', 'id');
        }

        return collect();
    }

    private static function departments(?int $directorateId)
    {
        if (! $directorateId) {
            return collect();
        }

        return Department::whereHas('directorates', function ($q) use ($directorateId) {
            $q->where('directorate_id', $directorateId);
        })->pluck('title', 'id');
    }

    private static function usersForEdit(Project $project)
    {
        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();

        if (in_array(Role::PROJECT_USER, $roleIds)) {
            return User::whereIn('id', function ($q) use ($project) {
                $q->select('user_id')
                    ->from('project_user')
                    ->where('project_id', $project->id);
            })->pluck('name', 'id');
        }

        if ($project->directorate_id) {
            return User::where('directorate_id', $project->directorate_id)
                ->pluck('name', 'id');
        }

        return collect();
    }
}
