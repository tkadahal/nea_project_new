<?php

declare(strict_types=1);

namespace App\Services\Project;

use App\DTOs\Project\ProjectDTO;
use App\Helpers\Project\ProjectDataTransformer;
use App\Models\Project;
use App\Models\Role;
use App\Repositories\Project\ProjectRepository;
use App\Services\Project\ProjectFormDataServices\FileService;
use App\Services\Project\ProjectFormDataServices\ProjectFormDataService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ProjectDataTransformer $transformer,
        private readonly ProjectFormDataService $formDataService,
        private readonly FileService $fileService
    ) {}

    public function getIndexData(): array
    {
        try {
            return [
                'data' => [],
                'tableData' => [],
                'projects' => collect(),
                'routePrefix' => 'admin.project',
                'actions' => ['view', 'edit', 'delete'],
                'deleteConfirmationMessage' => 'Are you sure you want to delete this project?',
                'arrayColumnColor' => $this->transformer->getColorConfig(),
                'tableHeaders' => $this->getTableHeaders(),
                'directorates' => $this->projectRepository->getDirectoratesForDropdown(),
            ];
        } catch (\Exception $e) {
            report($e);

            return [
                'error' => 'Unable to load projects due to an error.',
                'data' => [],
                'tableData' => [],
                'projects' => collect(),
                'directorates' => collect(),
            ];
        }
    }

    /**
     * Get lightweight list of projects for dropdown
     */
    public function getProjectsForDropdown(): array
    {
        $projects = $this->projectRepository->getProjectsForUser(
            Auth::user(),
            withRelations: false,
            paginate: null
        );

        return $projects->map(function ($project) {
            return [
                'id' => $project->id,
                'title' => $project->title,
                'directorate_id' => $project->directorate_id,
            ];
        })->toArray();
    }

    /**
     * Get filtered projects with pagination for AJAX requests
     */
    public function getFilteredProjectsData(
        int $perPage = 12,
        ?string $directorateId = null,
        ?string $projectId = null,
        ?string $statusId = null,
        ?string $search = null,
        string $view = 'card'
    ): array {
        try {
            $projects = $this->projectRepository->getFilteredProjects(
                user: Auth::user(),
                perPage: $perPage,
                directorateId: $directorateId,
                projectId: $projectId,
                statusId: $statusId,
                search: $search
            );

            // Eager load contracts + schedules so calculateContractProgressFromLoaded()
            // works without extra queries per project
            $projects->getCollection()->load([
                'contracts.activitySchedules' => function ($q) {
                    $q->withPivot(['progress', 'status'])
                        ->withCount('children');
                },
            ]);

            if ($view === 'card') {
                $transformedData = $projects->getCollection()->map(function ($project) {
                    return $this->transformer->transformProjectForCard($project);
                })->values()->toArray();
            } else {
                $transformedData = $this->transformForTable($projects->getCollection());
            }

            return [
                'data' => $transformedData,
                'tableData' => $view === 'list' ? $transformedData : [],
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ];
        } catch (\Exception $e) {
            report($e);
            throw $e;
        }
    }

    /**
     * Transform projects collection for table view.
     * Uses calculatePhysicalProgress() which averages contract schedule progress,
     * weighted by contract_amount.
     */
    public function transformForTable($projects): array
    {
        $collection = $projects instanceof \Illuminate\Support\Collection
            ? $projects
            : collect($projects);

        return $collection->map(function ($project) {
            $directorateTitle = is_array($project)
                ? ($project['directorate']['title'] ?? 'N/A')
                : ($project->directorate?->title ?? 'N/A');

            $directorateId = is_array($project)
                ? ($project['directorate']['id'] ?? null)
                : ($project->directorate?->id ?? null);

            $directorateColor = config("colors.directorate.{$directorateId}", 'gray');

            $priorityValue = is_array($project)
                ? ($project['priority']['title'] ?? 'N/A')
                : ($project->priority?->title ?? 'N/A');

            $priorityColor = config("colors.priority.{$priorityValue}", '#6B7280');

            $projectId    = is_array($project) ? $project['id'] : $project->id;
            $projectTitle = is_array($project) ? $project['title'] : $project->title;

            $startDate = is_array($project) ? ($project['start_date'] ?? null) : $project->start_date;
            $endDate   = is_array($project) ? ($project['end_date'] ?? null)   : $project->end_date;

            $startDateFormatted = $startDate
                ? (is_string($startDate) ? $startDate : $startDate->format('Y-m-d'))
                : 'N/A';

            $endDateFormatted = $endDate
                ? (is_string($endDate) ? $endDate : $endDate->format('Y-m-d'))
                : 'N/A';

            $projectManagerName = is_array($project)
                ? ($project['projectManager']['name'] ?? 'N/A')
                : ($project->projectManager?->name ?? 'N/A');

            // Use schedule-based physical progress instead of the raw DB column
            $progress = is_array($project)
                ? ($project['progress'] ?? 0)          // fallback for plain arrays
                : $project->calculatePhysicalProgress(); // live calculation from schedules

            return [
                'id'    => $projectId,
                'title' => $projectTitle,
                'directorate' => [['title' => $directorateTitle, 'color' => $directorateColor]],
                'fields' => [
                    ['title' => trans('global.project.fields.start_date') . ': ' . $startDateFormatted],
                    ['title' => trans('global.project.fields.end_date')   . ': ' . $endDateFormatted],
                    [
                        'title' => trans('global.project.fields.physical_progress') . ': ' .
                            (is_numeric($progress) ? round($progress, 2) . '%' : 'N/A'),
                    ],
                    ['title' => trans('global.project.fields.project_manager') . ': ' . $projectManagerName],
                ],
            ];
        })->toArray();
    }

    public function getCreateData(): array
    {
        return $this->formDataService->getFormData();
    }

    public function getEditData(Project $project): array
    {
        $project->load(['budgets', 'files']);

        return $this->formDataService->getFormData($project);
    }

    public function getShowData(Project $project): array
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
            // Load contracts + their schedules so physical progress can be
            // calculated from loaded data without additional queries
            'contracts.activitySchedules' => function ($q) {
                $q->withPivot(['progress', 'status'])
                    ->withCount('children');
            },
        ]);

        $this->markCommentsAsRead($project);

        $latestBudget   = $project->budgets->sortByDesc('id')->first();
        $totalBudget    = $latestBudget ? (float) $latestBudget->total_budget : 0.0;
        $latestBudgetId = $latestBudget?->id;

        // Derive physical progress from the already-loaded schedules
        $physicalProgress = $project->calculatePhysicalProgress();

        return compact('project', 'totalBudget', 'latestBudgetId', 'physicalProgress');
    }

    public function createProject(array $data, ?array $files = null): array
    {
        $user    = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();
        $isAdmin = in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds);

        if (! $isAdmin) {
            $data['directorate_id'] = $user->directorate_id;
        }

        try {
            DB::beginTransaction();

            $dto     = ProjectDTO::fromArray($data);
            $project = $this->projectRepository->create($dto->toArray());

            if ($files) {
                $this->fileService->attachFiles($project, $files);
            }

            $this->fileService->cleanupTempFiles();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Project created successfully.',
                'project' => $project,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return [
                'success' => false,
                'message' => 'Failed to create project. Please try again.',
            ];
        }
    }

    public function updateProject(Project $project, array $data, ?array $files = null): array
    {
        try {
            DB::beginTransaction();

            $dto = ProjectDTO::fromArray($data);
            $this->projectRepository->update($project, $dto->toArray());

            if ($files) {
                $this->fileService->attachFiles($project, $files);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Project updated successfully.',
                'project' => $project,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return [
                'success' => false,
                'message' => 'Failed to update project. Please try again.',
            ];
        }
    }

    public function deleteProject(Project $project): array
    {
        try {
            DB::beginTransaction();

            $this->fileService->deleteProjectFiles($project);
            $this->projectRepository->delete($project);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Project deleted successfully.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return [
                'success' => false,
                'message' => 'Failed to delete project. Please try again.',
            ];
        }
    }

    // ────────────────────────────────────────────────
    // Private Helpers
    // ────────────────────────────────────────────────

    private function markCommentsAsRead(Project $project): void
    {
        $user = Auth::user();

        $commentIds = $user->comments()
            ->where('commentable_type', 'App\Models\Project')
            ->where('commentable_id', $project->id)
            ->whereNull('comment_user.read_at')
            ->pluck('comments.id');

        foreach ($commentIds as $commentId) {
            $user->comments()->updateExistingPivot($commentId, ['read_at' => now()]);
        }
    }

    private function getTableHeaders(): array
    {
        return [
            trans('global.project.fields.id'),
            trans('global.project.fields.title'),
            trans('global.project.fields.directorate_id'),
            trans('global.details'),
        ];
    }
}
