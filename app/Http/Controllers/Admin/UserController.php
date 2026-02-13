<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use App\Models\Project;
use Illuminate\View\View;
use App\Models\Directorate;
use Illuminate\Http\Request;
use App\Services\User\UserService;
use App\Trait\RoleBasedAccess;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function index(): View|JsonResponse
    {
        abort_if(Gate::denies('user_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (request()->wantsJson() || request()->ajax()) {
            return $this->getUsersJson();
        }

        $data = $this->userService->getIndexData();
        return view('admin.users.index', $data);
    }

    private function getUsersJson(): JsonResponse
    {
        try {
            $perPage = (int) request('per_page', 20);
            $roleFilter = request('role_filter');
            $directorateFilter = request('directorate_filter');
            $search = request('search');

            $data = $this->userService->getFilteredUsersData(
                perPage: $perPage,
                roleFilter: $roleFilter,
                directorateFilter: $directorateFilter,
                search: $search
            );

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load users',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function create(): View
    {
        abort_if(Gate::denies('user_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();
        $isDirectorateOrProjectUser = in_array(Role::DIRECTORATE_USER, $roleIds) || in_array(Role::PROJECT_USER, $roleIds);
        $directorateId = $user->directorate_id;

        // Use trait helper methods for getting accessible resources
        $roles = $isDirectorateOrProjectUser ? collect([]) : Role::pluck('title', 'id');
        $directorates = $isDirectorateOrProjectUser ? collect([]) : Directorate::pluck('title', 'id');

        // Use trait method to get accessible projects
        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds($user);
        $projects = Project::whereIn('id', $accessibleProjectIds)->pluck('title', 'id');

        return view('admin.users.create', compact('roles', 'directorates', 'projects', 'isDirectorateOrProjectUser', 'directorateId'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('user_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();
        $isDirectorateOrProjectUser = in_array(Role::DIRECTORATE_USER, $roleIds) || in_array(Role::PROJECT_USER, $roleIds);

        $validated = $request->validated();

        if ($isDirectorateOrProjectUser) {
            $validated['roles'] = [Role::PROJECT_USER];
            $validated['directorate_id'] = $user->directorate_id;
        }

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $newUser = User::create(\Illuminate\Support\Arr::except($validated, ['projects', 'roles']));

        $newUser->roles()->sync($validated['roles'] ?? []);
        $newUser->projects()->sync($validated['projects'] ?? []);

        return redirect()->route('admin.user.index')
            ->with('message', 'User created successfully.');
    }

    public function edit($id): View
    {
        abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $authUser = Auth::user();
        $user = User::with(['roles', 'directorate', 'projects'])->findOrFail($id);

        // Use trait method for authorization
        abort_unless(
            RoleBasedAccess::canEditResource($authUser, $user),
            Response::HTTP_FORBIDDEN,
            'You do not have permission to edit this user.'
        );

        $roleIds = $authUser->roles->pluck('id')->toArray();
        $isDirectorateOrProjectUser = in_array(Role::DIRECTORATE_USER, $roleIds) || in_array(Role::PROJECT_USER, $roleIds);

        $roles = $isDirectorateOrProjectUser ? collect([]) : Role::pluck('title', 'id');
        $directorates = $isDirectorateOrProjectUser ? collect([]) : Directorate::pluck('title', 'id');

        // Use trait method to get accessible projects
        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds($authUser);
        $projects = Project::whereIn('id', $accessibleProjectIds)->pluck('title', 'id');

        return view('admin.users.edit', compact('user', 'roles', 'directorates', 'projects', 'isDirectorateOrProjectUser'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $authUser = Auth::user();

        // Use trait method for authorization
        abort_unless(
            RoleBasedAccess::canEditResource($authUser, $user),
            Response::HTTP_FORBIDDEN,
            'You do not have permission to update this user.'
        );

        $roleIds = $authUser->roles->pluck('id')->toArray();
        $isDirectorateOrProjectUser = in_array(Role::DIRECTORATE_USER, $roleIds) || in_array(Role::PROJECT_USER, $roleIds);

        $validated = $request->validated();

        if ($isDirectorateOrProjectUser) {
            $validated['roles'] = [Role::PROJECT_USER];
            $validated['directorate_id'] = $authUser->directorate_id;
        }

        if (isset($validated['password']) && !empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (!$isDirectorateOrProjectUser && !isset($validated['directorate_id'])) {
            $validated['directorate_id'] = $user->directorate_id;
        }

        $user->update($validated);
        $user->roles()->sync($validated['roles'] ?? []);
        $user->projects()->sync($validated['projects'] ?? []);

        return redirect()->route('admin.user.index')
            ->with('message', 'User updated successfully.');
    }

    public function show(User $user): View
    {
        abort_if(Gate::denies('user_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $authUser = Auth::user();

        // Use trait method for authorization (allows viewing self)
        abort_unless(
            RoleBasedAccess::canViewResource($authUser, $user),
            Response::HTTP_FORBIDDEN,
            'You do not have permission to view this user.'
        );

        $user->load(['roles', 'directorate', 'projects']);

        return view('admin.users.show', compact('user'));
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if(Gate::denies('user_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $authUser = Auth::user();

        // Prevent self-deletion
        if ($authUser->id === $user->id) {
            return back()->with('error', 'You cannot delete or remove yourself.');
        }

        // Use trait method for authorization
        abort_unless(
            RoleBasedAccess::canDeleteResource($authUser, $user),
            Response::HTTP_FORBIDDEN,
            'You do not have permission to delete or remove this user.'
        );

        $roleIds = $authUser->roles->pluck('id')->toArray();

        // SuperAdmin and Admin can actually delete users
        if (in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds)) {
            $user->delete();
            return back()->with('message', 'User deleted successfully.');
        }

        // Other roles: remove from projects only
        if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            $projectIds = Project::where('directorate_id', $authUser->directorate_id)->pluck('id');
            $user->projects()->detach($projectIds);
        } elseif (in_array(Role::PROJECT_USER, $roleIds)) {
            $authUserProjectIds = $authUser->projects()->pluck('projects.id');
            $user->projects()->detach($authUserProjectIds);
        }

        return back()->with('message', 'User removed from projects successfully.');
    }

    public function getProjects($directorateId)
    {
        try {
            $projects = Project::where('directorate_id', $directorateId)
                ->pluck('title', 'id')
                ->map(fn($label, $value) => [
                    'value' => (string) $value,
                    'label' => $label,
                ])
                ->values()
                ->all();

            return response()->json($projects);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch projects: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function loadUsers($directorateId = null)
    {
        $authUser = Auth::user();

        // Use trait method to filter users
        $query = User::applyResourceAccessFilter($authUser);

        if ($directorateId && $directorateId != 0) {
            $query->where('directorate_id', $directorateId);
        }

        $users = $query->select('id', 'name', 'employee_id', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    public function loadProjects($directorateId)
    {
        $authUser = Auth::user();

        // Use trait method to get accessible project IDs
        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds($authUser);

        $projects = Project::where('directorate_id', $directorateId)
            ->whereIn('id', $accessibleProjectIds)
            ->with('status')
            ->select('id', 'title', 'status_id')
            ->orderBy('title')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'status' => $project->status?->title ?? null,
                ];
            });

        return response()->json($projects);
    }

    public function assignUserToProject(): View | RedirectResponse
    {
        $currentUser = Auth::user();
        $roleIds = $currentUser->roles->pluck('id')->toArray();

        if (!in_array(Role::SUPERADMIN, $roleIds)) {
            abort(403, 'Only superadmin can assign users across directorates.');
        }

        $directorates = Directorate::orderBy('title')->get(['id', 'title']);

        return view('admin.users.assignment', compact('directorates'));
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        $currentUser = Auth::user();
        $roleIds = $currentUser->roles->pluck('id')->toArray();

        if (!in_array(Role::SUPERADMIN, $roleIds)) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'user_id'    => 'required|numeric|exists:users,id',
            'project_id' => 'required|numeric|exists:projects,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $project = Project::findOrFail($validated['project_id']);

        if ($user->projects()->where('project_id', $project->id)->exists()) {
            return back()->with('warning', 'User is already assigned to this project.');
        }

        $user->projects()->attach($project->id);

        return back()->with('message', "User '{$user->name}' assigned to '{$project->title}' successfully.");
    }
}
