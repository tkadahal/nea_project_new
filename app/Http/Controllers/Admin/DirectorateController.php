<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Directorate\StoreDirectorateRequest;
use App\Http\Requests\Directorate\UpdateDirectorateRequest;
use App\Models\Department;
use App\Models\Directorate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DirectorateController extends Controller
{
    public function index(): View
    {
        abort_if(Gate::denies('directorate_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $directorates = Directorate::with('departments:id,title')
            ->latest()
            ->get();

        $headers = [trans('global.directorate.fields.id'), trans('global.directorate.fields.title'), trans('global.directorate.fields.departments')];
        $data = $directorates->map(function ($directorate) {
            return [
                'id' => $directorate->id,
                'title' => $directorate->title,
                'departments' => $directorate->departments->pluck('title')->toArray(),
            ];
        })->all();

        return view('admin.directorates.index', [
            'headers' => $headers,
            'data' => $data,
            'directorates' => $directorates,
            'routePrefix' => 'admin.directorate',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this directorate?',
            'arrayColumnColor' => 'blue',
        ]);
    }

    public function create(): View
    {
        abort_if(Gate::denies('directorate_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $departments = Department::pluck('title', 'id');

        return view('admin.directorates.create', compact('departments'));
    }

    public function store(StoreDirectorateRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('directorate_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validated();

        $directorate = Directorate::create($validated);

        $directorate->departments()->sync($validated['departments'] ?? []);

        return redirect()->route(route: 'admin.directorate.index')
            ->with('message', 'Directorate created successfully.');
    }

    public function show(Directorate $directorate): View
    {
        abort_if(Gate::denies('directorate_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $directorate->load('departments');

        return view('admin.directorates.show', compact('directorate'));
    }

    public function edit(Directorate $directorate): View
    {
        abort_if(Gate::denies('directorate_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $directorate->load('departments');

        $departments = Department::pluck('title', 'id');

        return view('admin.directorates.edit', compact('directorate', 'departments'));
    }

    public function update(UpdateDirectorateRequest $request, Directorate $directorate): RedirectResponse
    {
        abort_if(Gate::denies('directorate_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validated();

        $directorate->update($validated);

        $directorate->departments()->sync($validated['departments'] ?? []);

        return redirect()->route(route: 'admin.directorate.index')
            ->with('message', 'Directorate updated successfully.');
    }

    public function destroy(Directorate $directorate): RedirectResponse
    {
        abort_if(Gate::denies('directorate_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $directorate->delete();

        return back();
    }
}
