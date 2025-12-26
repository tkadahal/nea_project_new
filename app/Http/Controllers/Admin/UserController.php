<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use App\Models\Project;
use Illuminate\View\View;
use App\Models\Directorate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(): View
    {
        abort_if(Gate::denies('user_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $userQuery = User::with(['roles', 'directorate'])->latest();

        try {
            $roleIds = $user->roles->pluck('id')->toArray();

            if (!in_array(Role::SUPERADMIN, $roleIds) && !in_array(Role::ADMIN, $roleIds)) {
                if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                    $directorateId = $user->directorate ? [$user->directorate->id] : [];
                    $userQuery->whereHas('directorate', function ($query) use ($directorateId) {
                        $query->whereIn('directorate_id', $directorateId);
                    });
                } elseif (in_array(Role::PROJECT_USER, $roleIds)) {
                    $projectIds = $user->projects()->pluck('projects.id')->toArray();
                    $userQuery->whereHas('projects', function ($query) use ($projectIds) {
                        $query->whereIn('projects.id', $projectIds);
                    });
                } else {
                    $userQuery->where('id', $user->id);
                }
            }
        } catch (\Exception $e) {
            $data['error'] = 'Unable to load users due to an error.';
        }

        $users = $userQuery->get();

        $headers = [
            trans('global.user.fields.id'),
            trans('global.user.fields.name'),
            trans('global.user.fields.email'),
            trans('global.user.fields.roles'),
            trans('global.user.fields.directorate_id'),
        ];

        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('title')->toArray() ?? [],
                'directorate_id' => $user->directorate ? $user->directorate->title : 'N/A',
            ];
        })->all();

        return view('admin.users.index', [
            'headers' => $headers,
            'data' => $data,
            'users' => $users,
            'routePrefix' => 'admin.user',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this user?',
            'arrayColumnColor' => 'purple',
            'projectManager' => $user->isProjectManager(),
        ]);
    }

    public function create(): View
    {
        abort_if(Gate::denies('user_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();
        $isDirectorateOrProjectUser = in_array(3, $roleIds) || in_array(4, $roleIds);
        $directorateId = $user->directorate_id;

        $roles = $isDirectorateOrProjectUser ? collect([]) : Role::pluck('title', 'id');
        $directorates = $isDirectorateOrProjectUser ? collect([]) : Directorate::pluck('title', 'id');
        $projects = collect([]);

        if ($isDirectorateOrProjectUser) {
            if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                $projects = Project::where('directorate_id', $directorateId)->pluck('title', 'id');
            } elseif (in_array(Role::PROJECT_USER, $roleIds)) {
                $projects = $user->projects()->pluck('title', 'id');
            }
        } else {
            $projects = Project::pluck('title', 'id');
        }

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
            $validated['roles'] = [4];
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
        $roleIds = $authUser->roles->pluck('id')->toArray();
        $isDirectorateOrProjectUser = in_array(Role::DIRECTORATE_USER, $roleIds) || in_array(Role::PROJECT_USER, $roleIds);

        $user = User::with(['roles', 'directorate', 'projects'])->findOrFail($id);

        if ($isDirectorateOrProjectUser) {
            if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                if ($user->directorate_id !== $authUser->directorate_id) {
                    abort(Response::HTTP_FORBIDDEN, 'You can only edit users in your directorate.');
                }
            } elseif (in_array(Role::PROJECT_USER, $roleIds)) {
                $authUserProjectIds = $authUser->projects()->pluck('projects.id')->toArray();
                $userProjectIds = $user->projects()->pluck('projects.id')->toArray();
                if (empty(array_intersect($authUserProjectIds, $userProjectIds))) {
                    abort(Response::HTTP_FORBIDDEN, 'You can only edit users in your projects.');
                }
            }
        }

        $roles = $isDirectorateOrProjectUser ? collect([]) : Role::pluck('title', 'id');
        $directorates = $isDirectorateOrProjectUser ? collect([]) : Directorate::pluck('title', 'id');

        $projects = collect([]);
        if ($isDirectorateOrProjectUser) {
            if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                $projects = Project::where('directorate_id', $authUser->directorate_id)->pluck('title', 'id');
            } elseif (in_array(Role::PROJECT_USER, $roleIds)) {
                $projects = Auth::user()->projects()->pluck('title', 'id');
            }
        } else {
            $projects = Project::pluck('title', 'id');
        }

        return view('admin.users.edit', compact('user', 'roles', 'directorates', 'projects', 'isDirectorateOrProjectUser'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $authUser = Auth::user();
        $roleIds = $authUser->roles->pluck('id')->toArray();
        $isDirectorateOrProjectUser = in_array(3, $roleIds) || in_array(4, $roleIds);

        $validated = $request->validated();

        if ($isDirectorateOrProjectUser) {
            $validated['roles'] = [4];
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

        $user->load(['roles', 'directorate', 'projects']);

        return view('admin.users.show', compact('user'));
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if(Gate::denies('user_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user->delete();

        return back()->with('message', 'User deleted successfully.');
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

    /**
     * Load users belonging to a specific directorate
     */
    public function loadUsers($directorateId = null)
    {
        $query = User::query();

        if ($directorateId && $directorateId != 0) {
            $query->where('directorate_id', $directorateId);
        }

        $users = $query->select('id', 'name', 'employee_id', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    /**
     * Load projects belonging to a specific directorate
     */
    public function loadProjects($directorateId)
    {
        $projects = Project::where('directorate_id', $directorateId)
            ->with('status') // if you have status relation
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

    /**
     * Show the assignment page
     */
    public function assignUserToProject(): View | RedirectResponse
    {
        $currentUser = Auth::user();
        $roleIds = $currentUser->roles->pluck('id')->toArray();

        // Only SUPERADMIN can access this page
        if (!in_array(Role::SUPERADMIN, $roleIds)) {
            abort(403, 'Only superadmin can assign users across directorates.');
        }

        $directorates = Directorate::orderBy('title')->get(['id', 'title']);

        return view('admin.users.assignment', compact('directorates'));
    }

    /**
     * Handle the assignment form submission
     */
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
