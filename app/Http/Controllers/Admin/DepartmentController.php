<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use Illuminate\View\View;
use App\Models\Department;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;

class DepartmentController extends Controller
{
    public function index(): View
    {
        abort_if(Gate::denies('department_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $directorateColors = config('colors.directorate');

        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();

        $departmentsQuery = Department::query()->with('directorates')->latest();

        if (in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds)) {
            $departments = $departmentsQuery->get();
        } elseif (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            $directorateId = $user->directorate_id;
            if (!$directorateId) {
                $departments = collect();
            } else {
                $departments = $departmentsQuery->whereHas('directorates', function ($query) use ($directorateId) {
                    $query->where('directorates.id', $directorateId);
                })->get();
            }
        } else {
            $departments = collect();
        }

        $headers = [trans('global.department.fields.id'), trans('global.directorate.title_singular'), trans('global.department.fields.title')];
        $data = $departments->map(function ($department) use ($directorateColors) {
            $directorates = $department->directorates->map(function ($directorate) use ($directorateColors) {
                $directorateId = $directorate->id;
                $color = isset($directorateColors[$directorateId]) ? $directorateColors[$directorateId] : 'gray';

                return [
                    'title' => $directorate->title,
                    'color' => $color,
                ];
            })->all();

            return [
                'id' => $department->id,
                'directorates' => $directorates,
                'title' => $department->title,
            ];
        })->all();

        return view('admin.departments.index', [
            'headers' => $headers,
            'data' => $data,
            'departments' => $departments,
            'routePrefix' => 'admin.department',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this department?',
            'arrayColumnColor' => $directorateColors,
        ]);
    }

    public function create(): View
    {
        abort_if(Gate::denies('department_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.departments.create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('department_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        Department::create($request->validated());

        return redirect()->route(route: 'admin.department.index')
            ->with('message', 'Department created successfully.');
    }

    public function show(Department $department): View
    {
        abort_if(Gate::denies('department_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department): View
    {
        abort_if(Gate::denies('department_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.departments.edit', compact('department'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        abort_if(Gate::denies('department_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $department->update($request->validated());

        return redirect()->route(route: 'admin.department.index')
            ->with('message', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        abort_if(Gate::denies('department_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $department->delete();

        return back();
    }
}
