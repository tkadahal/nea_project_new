<?php

declare(strict_types=1);

namespace App\Services\Project;

use App\Models\Project;
use App\DTOs\Project\ProjectDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Project\ProjectDataTransformer;
use App\Repositories\Project\ProjectRepository;
use App\Services\Project\ProjectFormDataServices\FileService;
use App\Services\Project\ProjectFormDataServices\ProjectFormDataService;

/**
 * SIMPLE VERSION - No AJAX complexity
 * Works with your current Blade view
 */
class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ProjectDataTransformer $transformer,
        private readonly ProjectFormDataService $formDataService,
        private readonly FileService $fileService
    ) {}

    /**
     * Get data for index page - SIMPLE VERSION
     * Returns ALL projects for client-side pagination
     */
    public function getIndexData(): array
    {
        try {
            // Get ALL projects (no server-side pagination)
            $projects = $this->projectRepository->getProjectsForUser(
                Auth::user(),
                withRelations: true,
                paginate: null // null = get all
            );

            // Transform for card view
            $cardData = $this->transformer->transformForCards($projects);

            // Transform for table view - pass the collection, not the transformed array
            $tableData = $this->transformer->transformForTable($projects);

            return [
                'data' => $cardData,
                'tableData' => $tableData,
                'projects' => $projects,
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
                'routePrefix' => 'admin.project',
                'actions' => ['view', 'edit', 'delete'],
                'deleteConfirmationMessage' => 'Are you sure you want to delete this project?',
                'arrayColumnColor' => $this->transformer->getColorConfig(),
                'tableHeaders' => $this->getTableHeaders(),
                'directorates' => collect(),
            ];
        }
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
        ]);

        $this->markCommentsAsRead($project);

        $latestBudget = $project->budgets->sortByDesc('id')->first();
        $totalBudget = $latestBudget ? (float) $latestBudget->total_budget : 0.0;
        $latestBudgetId = $latestBudget?->id;

        return compact('project', 'totalBudget', 'latestBudgetId');
    }

    public function createProject(array $data, ?array $files = null): array
    {
        try {
            DB::beginTransaction();

            $dto = ProjectDTO::fromArray($data);
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
