<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    public function index(): View
    {
        abort_if(Gate::denies('role_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $roles = Role::with('permissions')->latest()->get();

        $headers = [trans('global.role.fields.id'), trans('global.role.fields.title'), trans('global.role.fields.permissions')];
        $data = $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'title' => $role->title,
                'permissions' => $role->permissions->pluck('title')->toArray(),
            ];
        })->all();

        return view('admin.roles.index', [
            'headers' => $headers,
            'data' => $data,
            'roles' => $roles,
            'routePrefix' => 'admin.role',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this role?',
            'arrayColumnColor' => 'purple',
        ]);
    }

    public function create(): View
    {
        abort_if(Gate::denies('role_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $permissions = Permission::pluck('title', 'id');

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('role_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $role = Role::create($request->validated());

        $role->permissions()->sync($request->validated('permissions', []));

        return redirect()->route(route: 'admin.role.index')
            ->with('message', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        abort_if(Gate::denies('role_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $permissions = Permission::pluck('title', 'id');

        $role->load('permissions');

        return view('admin.roles.edit', compact('permissions', 'role'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        abort_if(Gate::denies('role_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $role->update($request->validated());

        $role->permissions()->sync($request->validated('permissions', []));

        return redirect()->route(route: 'admin.role.index')
            ->with('message', 'Role updated successfully.');
    }

    public function show(Role $role): View
    {
        abort_if(Gate::denies('role_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $role->load('permissions');

        return view('admin.roles.show', compact('role'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if(Gate::denies('role_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $role->delete();

        return back()->with('message', 'Role deleted successfully.');
    }
}
