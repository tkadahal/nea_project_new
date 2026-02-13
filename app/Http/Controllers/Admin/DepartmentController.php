<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use Illuminate\View\View;
use App\Models\Department;
use App\Models\Directorate;
use App\Trait\RoleBasedAccess;
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
        $accessibleDirectorateIds = RoleBasedAccess::getAccessibleDirectorateIds();

        $departments = Department::query()
            ->with('directorates')
            ->latest()
            ->whereHas('directorates', fn($q) => $q->whereIn('directorates.id', $accessibleDirectorateIds))
            ->get();

        $headers = [
            trans('global.department.fields.id'),
            trans('global.directorate.title_singular'),
            trans('global.department.fields.title'),
        ];

        $data = $departments->map(function ($department) use ($directorateColors) {
            $directorates = $department->directorates->map(function ($directorate) use ($directorateColors) {
                $color = $directorateColors[$directorate->id] ?? 'gray';
                return ['title' => $directorate->title, 'color' => $color];
            })->all();

            return [
                'id'          => $department->id,
                'directorates' => $directorates,
                'title'       => $department->title,
            ];
        })->all();

        return view('admin.departments.index', [
            'headers'                   => $headers,
            'data'                      => $data,
            'departments'               => $departments,
            'routePrefix'               => 'admin.department',
            'actions'                   => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this department?',
            'arrayColumnColor'          => $directorateColors,
        ]);
    }

    public function create(): View
    {
        abort_if(Gate::denies('department_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user     = Auth::user();
        $roleIds  = $user->roles->pluck('id')->toArray();

        $isAdminOrSuperAdmin = in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds);

        $directorates = $isAdminOrSuperAdmin
            ? Directorate::orderBy('title')->pluck('title', 'id')
            : collect();

        return view('admin.departments.create', compact('directorates', 'isAdminOrSuperAdmin'));
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = Department::create($request->validated());

        $this->syncDirectorateForDepartment($department);

        return redirect()->route('admin.department.index')
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

        $user     = Auth::user();
        $roleIds  = $user->roles->pluck('id')->toArray();

        $isAdminOrSuperAdmin = in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds);

        $directorates = $isAdminOrSuperAdmin
            ? Directorate::orderBy('title')->pluck('title', 'id')
            : collect();

        $selectedDirectorateId = $department->directorates->first()?->id ?? null;

        return view('admin.departments.edit', compact(
            'department',
            'directorates',
            'isAdminOrSuperAdmin',
            'selectedDirectorateId'
        ));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        $this->syncDirectorateForDepartment($department);

        return redirect()->route('admin.department.index')
            ->with('message', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        abort_if(Gate::denies('department_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $department->delete();

        return back()->with('message', 'Department deleted successfully.');
    }

    // ────────────────────────────────────────────────
    //  Private helper – used by both store & update
    // ────────────────────────────────────────────────

    private function syncDirectorateForDepartment(Department $department): void
    {
        $user    = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();

        $isDirectorateUser   = in_array(Role::DIRECTORATE_USER, $roleIds);
        $isAdminOrSuperAdmin = in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds);

        if ($isDirectorateUser) {
            if ($directorate = $user->directorate) {
                $department->directorates()->sync([$directorate->id]);
            }
            return;
        }

        if ($isAdminOrSuperAdmin) {
            $directorateId = request()->input('directorate_id');

            if (is_numeric($directorateId)) {
                $department->directorates()->sync([$directorateId]);
            }
        }
    }
}
