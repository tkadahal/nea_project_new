<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Services\Project\ProjectService;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Project\StoreProjectRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Http\Requests\Project\UpdateProjectRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Services\Project\ProjectFormDataServices\DirectorateService;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly DirectorateService $directorateService
    ) {}

    public function index(): View|JsonResponse
    {
        abort_if(Gate::denies('project_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // Check if this is an AJAX request
        if (request()->wantsJson() || request()->ajax()) {
            return $this->getProjectsJson();
        }

        $data = $this->projectService->getIndexData();

        return view('admin.projects.index', $data);
    }

    /**
     * Get projects as JSON for AJAX pagination
     */
    private function getProjectsJson(): JsonResponse
    {
        try {
            $perPage = (int) request('per_page', 12);
            $view = request('view', 'card');
            $directorateId = request('directorate_id');
            $search = request('search');

            $data = $this->projectService->getFilteredProjectsData(
                perPage: $perPage,
                directorateId: $directorateId,
                search: $search,
                view: $view
            );

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error loading projects: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to load projects',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function create(): View
    {
        abort_if(Gate::denies('project_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $this->projectService->getCreateData();

        return view('admin.projects.create', $data);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('project_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $result = $this->projectService->createProject($request->validated(), $request->file('files'));

        if ($result['success']) {
            return redirect()
                ->route('admin.project.index')
                ->with('message', $result['message']);
        }

        return redirect()
            ->back()
            ->withErrors(['error' => $result['message']])
            ->withInput();
    }

    public function show(Project $project): View
    {
        abort_if(Gate::denies('project_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $this->projectService->getShowData($project);

        return view('admin.projects.show', $data);
    }

    public function edit(Project $project): View
    {
        abort_if(Gate::denies('project_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $this->projectService->getEditData($project);

        return view('admin.projects.edit', $data);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        abort_if(Gate::denies('project_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $result = $this->projectService->updateProject(
            $project,
            $request->validated(),
            $request->file('files')
        );

        if ($result['success']) {
            return redirect()
                ->route('admin.project.index')
                ->with('message', $result['message']);
        }

        return redirect()
            ->back()
            ->withErrors(['error' => $result['message']])
            ->withInput();
    }

    public function destroy(Project $project): RedirectResponse
    {
        abort_if(Gate::denies('project_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $result = $this->projectService->deleteProject($project);

        if ($result['success']) {
            return redirect()
                ->route('admin.project.index')
                ->with('message', $result['message']);
        }

        return redirect()
            ->back()
            ->withErrors(['error' => $result['message']]);
    }

    public function getDepartments(int $directorate_id): JsonResponse
    {
        try {
            $departments = $this->directorateService->getDepartmentsByDirectorate($directorate_id);
            return response()->json($departments);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch departments.'], 500);
        }
    }

    public function getUsers(int $directorate_id): JsonResponse
    {
        try {
            $users = $this->directorateService->getUsersByDirectorate($directorate_id);
            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch users.'], 500);
        }
    }

    public function progressChart(Project $project): View
    {
        $project->load(['tasks', 'contracts', 'expenses']);
        return view('admin.expenses.progress_chart', compact('project'));
    }
}
