<?php

declare(strict_types=1);

namespace App\Services\Project\ProjectFormDataServices;

use App\Models\Role;
use App\Models\User;
use App\Models\Status;
use App\Models\Project;
use App\Models\Priority;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\Directorate;
use App\Models\BudgetHeading;
use Illuminate\Support\Facades\Auth;
use App\Trait\RoleBasedAccess;

/**
 * Service for preparing form data
 */
class ProjectFormDataService
{
    public function getFormData(?Project $project = null): array
    {
        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();

        $data = [
            'directorates' => collect(),
            'departments' => collect(),
            'users' => collect(),
            'statuses' => Status::pluck('title', 'id'),
            'priorities' => Priority::pluck('title', 'id'),
            'fiscalYears' => FiscalYear::pluck('title', 'id'),
            'budgetHeadings' => BudgetHeading::pluck('title', 'id'),
        ];

        $accessibleDirectorateIds = RoleBasedAccess::getAccessibleDirectorateIds($user);
        $data['directorates'] = Directorate::whereIn('id', $accessibleDirectorateIds)
            ->pluck('title', 'id');

        if ($project) {
            $data['project'] = $project;

            if ($project->directorate_id) {
                $data['departments'] = Department::whereHas('directorates', function ($query) use ($project) {
                    $query->where('directorate_id', $project->directorate_id);
                })->pluck('title', 'id');

                if (in_array(Role::SUPERADMIN, $roleIds)) {
                    $data['users'] = User::where('directorate_id', $project->directorate_id)
                        ->pluck('name', 'id');
                } elseif (in_array(Role::PROJECT_USER, $roleIds)) {
                    $data['users'] = User::whereIn('id', function ($query) use ($project) {
                        $query->select('user_id')
                            ->from('project_user')
                            ->where('project_id', $project->id);
                    })->pluck('name', 'id');
                }
            }
        }

        return $data;
    }
}
