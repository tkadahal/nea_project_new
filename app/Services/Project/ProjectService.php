<?php

declare(strict_types=1);

namespace App\Services\Project;

use App\Models\User;
use App\Models\Status;
use App\Models\Project;
use App\Models\FiscalYear;
use App\Models\Directorate;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\DTOs\Project\ProjectCardDTO;
use Illuminate\Support\Facades\Auth;
use App\DTOs\Project\ProjectTableDTO;
use App\Queries\Project\ProjectIndexQuery;
use App\ViewModels\Project\ProjectFormData;

class ProjectService
{
    public function indexData(): array
    {
        $projects = ProjectIndexQuery::forUser(Auth::user())->get();

        return [
            'projects' => $projects,
            'directorates' => Directorate::pluck('title', 'id'),
            'fiscalYears'  => FiscalYear::pluck('title', 'id'),
            'statuses'     => Status::pluck('title', 'id'),
            'data' => ProjectCardDTO::collection($projects),
            'tableData' => ProjectTableDTO::collection($projects),
            'tableHeaders' => $this->tableHeaders(),
            'routePrefix' => 'admin.project',
            'actions' => ['view', 'edit', 'delete'],
        ];
    }

    public function createData(): array
    {
        return ProjectFormData::create();
    }

    public function store(Request $request): Project
    {
        $data = Arr::except($request->validated(), ['files']);

        $project = Project::create($data);

        ProjectFileService::handle($project, $request);

        return $project;
    }

    public function update(Project $project, Request $request): Project
    {
        $data = Arr::except($request->validated(), ['files']);

        $project->update($data);

        ProjectFileService::handle($project, $request);

        return $project;
    }

    public function showData(Project $project): array
    {
        $project->load([
            'directorate',
            'department',
            'status',
            'priority',
            'projectManager',
            'budgets',
            'comments.user',
            'comments.replies.user',
        ]);

        return compact('project');
    }

    public function editData(Project $project): array
    {
        return ProjectFormData::edit($project);
    }

    public function getDepartments(int $directorateId): array
    {
        return Directorate::findOrFail($directorateId)
            ->departments
            ->map(fn($d) => [
                'value' => (string) $d->id,
                'label' => $d->title,
            ])
            ->toArray();
    }

    public function getUsers(int $directorateId): array
    {
        return User::where('directorate_id', $directorateId)
            ->get(['id', 'name'])
            ->map(fn($u) => [
                'value' => (string) $u->id,
                'label' => $u->name,
            ])
            ->toArray();
    }

    private function tableHeaders(): array
    {
        return [
            trans('global.project.fields.id'),
            trans('global.project.fields.title'),
            trans('global.project.fields.directorate_id'),
            trans('global.details'),
        ];
    }
}
