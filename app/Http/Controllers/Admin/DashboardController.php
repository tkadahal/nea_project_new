<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Models\Contract;
use Illuminate\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $roles = $user->roles->pluck('id')->toArray();

        $number_blocks = [];
        $tasks = collect([]);
        $project_status = ['completed' => 0, 'in_progress' => 0, 'behind' => 0];
        $activity_logs = $this->getActivityLogs($user);

        $userProjectIds = in_array(Role::PROJECT_USER, $roles)
            ? $user->projects()->pluck('id')
            : collect([]);

        if (in_array(Role::SUPERADMIN, $roles) || in_array(Role::ADMIN, $roles)) {
            $number_blocks = $this->getAdminNumberBlocks();
        } elseif (in_array(Role::DIRECTORATE_USER, $roles)) {
            $number_blocks = $this->getDirectorateNumberBlocks($user);
            $directorateId = $user->directorate_id;
        } elseif (in_array(Role::PROJECT_USER, $roles)) {
            $number_blocks = $this->getProjectNumberBlocks($user, $userProjectIds);
        }

        $project_status = array_map(function ($value) {
            return is_numeric($value) ? (int) $value : 0;
        }, $project_status);

        return view('dashboard', compact('number_blocks', 'activity_logs'));
    }

    private function getAdminNumberBlocks(): array
    {
        return [
            ['title' => trans('global.user.title'), 'number' => User::count(), 'url' => route('admin.user.index')],
            ['title' => trans('global.project.title'), 'number' => Project::count(), 'url' => route('admin.project.index')],
            ['title' => trans('global.contract.title'), 'number' => Contract::count(), 'url' => route('admin.contract.index')],
            ['title' => trans('global.task.title'), 'number' => Task::count(), 'url' => route('admin.task.index')],
        ];
    }

    private function getDirectorateNumberBlocks(User $user): array
    {
        $directorateId = $user->directorate_id;

        if (! $directorateId) {
            return [
                ['title' => trans('global.user.title'), 'number' => 0, 'url' => route('admin.user.index')],
                ['title' => trans('global.project.title'), 'number' => 0, 'url' => route('admin.project.index')],
                ['title' => trans('global.contract.title'), 'number' => 0, 'url' => route('admin.contract.index')],
                ['title' => trans('global.task.title'), 'number' => 0, 'url' => route('admin.task.index')],
            ];
        }

        return [
            ['title' => trans('global.user.title'), 'number' => User::where('directorate_id', $directorateId)->count(), 'url' => route('admin.user.index')],
            ['title' => trans('global.project.title'), 'number' => Project::where('directorate_id', $directorateId)->count(), 'url' => route('admin.project.index')],
            ['title' => trans('global.contract.title'), 'number' => Contract::whereHas('project', fn($q) => $q->where('directorate_id', $directorateId))->count(), 'url' => route('admin.contract.index')],
            ['title' => trans('global.task.title'), 'number' => Task::whereHas('projects', fn($q) => $q->where('directorate_id', $directorateId))->count(), 'url' => route('admin.task.index')],
        ];
    }

    private function getProjectNumberBlocks(User $user, Collection $projectIds): array
    {
        $distinctUserCount = $projectIds->isEmpty()
            ? 0
            : DB::table('project_user')
            ->whereIn('project_id', $projectIds)
            ->distinct('user_id')
            ->count('user_id');

        if ($projectIds->isEmpty()) {
            return [
                ['title' => trans('global.user.title'), 'number' => $distinctUserCount, 'url' => route('admin.user.index')],
                ['title' => trans('global.project.title'), 'number' => 0, 'url' => route('admin.project.index')],
                ['title' => trans('global.contract.title'), 'number' => 0, 'url' => route('admin.contract.index')],
                ['title' => trans('global.task.title'), 'number' => 0, 'url' => route('admin.task.index')],
            ];
        }

        return [
            ['title' => trans('global.user.title'), 'number' => $distinctUserCount, 'url' => route('admin.user.index')],
            ['title' => trans('global.project.title'), 'number' => $projectIds->count(), 'url' => route('admin.project.index')],
            ['title' => trans('global.contract.title'), 'number' => Contract::whereIn('project_id', $projectIds)->count(), 'url' => route('admin.contract.index')],
            ['title' => trans('global.task.title'), 'number' => Task::whereHas('projects', fn($q) => $q->whereIn('id', $projectIds))->count(), 'url' => route('admin.task.index')],
        ];
    }

    private function getActivityLogs(User $user): Collection
    {
        return Activity::where('causer_type', 'App\Models\User')
            ->where('causer_id', $user->id ?? 1)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($activity) {
                return (object) [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'subject_type' => $activity->subject_type,
                    'subject_id' => $activity->subject_id,
                    'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
                ];
            });
    }
}
