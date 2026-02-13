<?php

declare(strict_types=1);

namespace App\Repositories\File;

use App\Models\File;
use App\Models\Project;
use App\DTOs\File\FileDTO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FileRepository
{
    /**
     * Get files based on user role and permissions.
     */
    public function getFilesForUser(FileDTO $dto, array $filters = []): Collection|LengthAwarePaginator
    {
        $fileQuery = File::with(['fileable', 'user'])->latest();

        // Apply filters first
        $this->applyFilters($fileQuery, $filters);

        if ($dto->isSuperAdmin()) {
            return $this->paginateIfNeeded($fileQuery, $filters);
        }

        if ($dto->isDirectorateUser()) {
            return $this->getFilesForDirectorate($fileQuery, $dto, $filters);
        }

        return $this->getFilesForProjectUser($fileQuery, $dto, $filters);
    }

    /**
     * Get files for directorate users.
     */
    private function getFilesForDirectorate(Builder $fileQuery, FileDTO $dto, array $filters = []): Collection|LengthAwarePaginator
    {
        if (!$dto->hasDirectorate()) {
            Log::warning('No directorate_id assigned to user', ['user_id' => $dto->userId]);
            return collect();
        }

        $projectIds = Project::where('directorate_id', $dto->directorateId)->pluck('id');

        if ($projectIds->isEmpty()) {
            Log::warning('No projects found for directorate', [
                'user_id' => $dto->userId,
                'directorate_id' => $dto->directorateId,
            ]);
            return collect();
        }

        $fileQuery->where(function ($query) use ($projectIds) {
            $query->where('fileable_type', 'App\Models\Project')
                ->whereIn('fileable_id', $projectIds)
                ->orWhere('fileable_type', 'App\Models\Contract')
                ->whereIn('fileable_id', function ($subQuery) use ($projectIds) {
                    $subQuery->select('id')->from('contracts')
                        ->whereIn('project_id', $projectIds);
                })
                ->orWhere('fileable_type', 'App\Models\Task')
                ->whereIn('fileable_id', function ($subQuery) use ($projectIds) {
                    $subQuery->select('task_id')->from('project_task')
                        ->whereIn('project_id', $projectIds);
                });
        });

        Log::info('Directorate file query', [
            'user_id' => $dto->userId,
            'directorate_id' => $dto->directorateId,
        ]);

        return $this->paginateIfNeeded($fileQuery, $filters);
    }

    /**
     * Get files for project users.
     */
    private function getFilesForProjectUser(Builder $fileQuery, FileDTO $dto, array $filters = []): Collection|LengthAwarePaginator
    {
        if (!$dto->hasProjects()) {
            Log::warning('No projects assigned to user', ['user_id' => $dto->userId]);
            return collect();
        }

        $fileQuery->where(function ($query) use ($dto) {
            $query->where('fileable_type', 'App\Models\Project')
                ->whereIn('fileable_id', $dto->projectIds)
                ->orWhere('fileable_type', 'App\Models\Contract')
                ->whereIn('fileable_id', function ($subQuery) use ($dto) {
                    $subQuery->select('id')->from('contracts')
                        ->whereIn('project_id', $dto->projectIds);
                })
                ->orWhere('fileable_type', 'App\Models\Task')
                ->whereIn('fileable_id', function ($subQuery) use ($dto) {
                    $subQuery->select('task_id')->from('project_task')
                        ->whereIn('project_id', $dto->projectIds)
                        ->orWhereIn('task_id', function ($subSubQuery) use ($dto) {
                            $subSubQuery->select('task_id')->from('task_user')
                                ->where('user_id', $dto->userId);
                        });
                });
        });

        Log::info('Project user file query', ['user_id' => $dto->userId]);

        return $this->paginateIfNeeded($fileQuery, $filters);
    }

    /**
     * Paginate query if pagination is requested.
     */
    private function paginateIfNeeded(Builder $query, array $filters): Collection|LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 20;

        if (isset($filters['paginate']) && $filters['paginate']) {
            return $query->paginate($perPage)->appends(request()->except('page'));
        }

        return $query->get();
    }

    /**
     * Apply search and filter criteria.
     */
    private function applyFilters(Builder $fileQuery, array $filters): void
    {
        // Filter by directorate
        if (!empty($filters['directorate_id'])) {
            $projectIds = Project::where('directorate_id', $filters['directorate_id'])->pluck('id');

            $fileQuery->where(function ($query) use ($projectIds) {
                $query->where('fileable_type', 'App\Models\Project')
                    ->whereIn('fileable_id', $projectIds)
                    ->orWhere('fileable_type', 'App\Models\Contract')
                    ->whereIn('fileable_id', function ($subQuery) use ($projectIds) {
                        $subQuery->select('id')->from('contracts')
                            ->whereIn('project_id', $projectIds);
                    })
                    ->orWhere('fileable_type', 'App\Models\Task')
                    ->whereIn('fileable_id', function ($subQuery) use ($projectIds) {
                        $subQuery->select('task_id')->from('project_task')
                            ->whereIn('project_id', $projectIds);
                    });
            });
        }

        // Filter by project
        if (!empty($filters['project_id'])) {
            $projectId = $filters['project_id'];

            $fileQuery->where(function ($query) use ($projectId) {
                $query->where(function ($q) use ($projectId) {
                    $q->where('fileable_type', 'App\Models\Project')
                        ->where('fileable_id', $projectId);
                })
                    ->orWhere(function ($q) use ($projectId) {
                        $q->where('fileable_type', 'App\Models\Contract')
                            ->whereIn('fileable_id', function ($subQuery) use ($projectId) {
                                $subQuery->select('id')->from('contracts')
                                    ->where('project_id', $projectId);
                            });
                    })
                    ->orWhere(function ($q) use ($projectId) {
                        $q->where('fileable_type', 'App\Models\Task')
                            ->whereIn('fileable_id', function ($subQuery) use ($projectId) {
                                $subQuery->select('task_id')->from('project_task')
                                    ->where('project_id', $projectId);
                            });
                    });
            });
        }

        // Search by filename
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $fileQuery->where('filename', 'LIKE', "%{$search}%");
        }
    }

    /**
     * Create a new file record.
     */
    public function create(array $data): File
    {
        return File::create($data);
    }

    /**
     * Delete a file record and its physical file.
     */
    public function delete(File $file): void
    {
        Storage::disk('public')->delete($file->path);
        $file->delete();
    }

    /**
     * Check if file exists in storage.
     */
    public function exists(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }

    /**
     * Get storage path for file.
     */
    public function getPath(string $path): string
    {
        return Storage::disk('public')->path($path);
    }
}
