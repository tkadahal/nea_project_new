<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectTypeController extends Controller
{
    public function index(): View
    {
        $projectTypes = ProjectType::all();

        return view('admin.projectTypes.index', compact('projectTypes'));
    }

    public function create(): View
    {
        return view('admin.projectTypes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:project_types,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        ProjectType::create($validated);

        return redirect()->route('admin.projectType.index')->with('success', 'Project type created successfully.');
    }

    public function show(ProjectType $projectType): View
    {
        return view('admin.projectTypes.show', compact('projectType'));
    }

    public function edit(ProjectType $projectType): View
    {
        return view('admin.projectTypes.edit', compact('projectType'));
    }

    public function update(Request $request, ProjectType $projectType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:project_types,code,' . $projectType->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $projectType->update($validated);

        return redirect()->route('admin.projectType.index')->with('success', 'Project type updated successfully.');
    }

    public function destroy(ProjectType $projectType): RedirectResponse
    {
        $projectType->delete();

        return redirect()->route('admin.projectType.index')->with('success', 'Project type deleted successfully.');
    }
}
