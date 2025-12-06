<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\ProjectActivityDefinition;

class DefinitionController extends Controller
{
    public function index(): View
    {
        $definitions = ProjectActivityDefinition::all();

        return view('admin.definitions.index', compact('definitions'));
    }

    public function create(Request $request): View
    {
        $user = Auth::user();
        $projects = $user->projects;

        $selectedProjectId = $request->input('project_id') ?? $projects->first()?->id;
        $selectedProject = $projects->find($selectedProjectId) ?? $projects->first();

        $projectOptions = $projects->map(fn(Project $project) => [
            'value' => $project->id,
            'label' => $project->title,
            'selected' => $project->id === $selectedProjectId,
        ])->toArray();

        $capitalDefinitions = collect();
        $recurrentDefinitions = collect();
        $capitalRows = [];
        $recurrentRows = [];

        return view('admin.definitions.create', compact('projectOptions', 'capitalRows', 'recurrentRows'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectActivityDefinition $projectActivityDefinition)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectActivityDefinition $projectActivityDefinition)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectActivityDefinition $projectActivityDefinition)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectActivityDefinition $projectActivityDefinition)
    {
        //
    }
}
