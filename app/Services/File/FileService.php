<?php

declare(strict_types=1);

namespace App\Services\File;

use App\Models\File;
use App\Models\User;
use App\DTOs\File\FileDTO;
use App\Repositories\File\FileRepository;
use App\Helpers\File\FileHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FileService
{
    public function __construct(
        private readonly FileRepository $repository
    ) {}

    /**
     * Get all files accessible by the user based on their role.
     */
    public function getFilesForUser(User $user, array $filters = []): Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $queryDTO = new FileDTO(
                userId: $user->id,
                roleIds: $user->roles->pluck('id')->toArray(),
                directorateId: $user->directorate_id,
                projectIds: $user->projects()->pluck('projects.id')
            );

            $files = $this->repository->getFilesForUser($queryDTO, $filters);

            Log::info('Files retrieved for user', [
                'user_id' => $user->id,
                'count' => $files instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $files->total() : $files->count(),
                'filters' => $filters,
            ]);

            return $files;
        } catch (\Exception $e) {
            Log::error('Error fetching files', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return collect();
        }
    }

    /**
     * Get directorates accessible by the user.
     */
    public function getDirectoratesForUser(User $user): Collection
    {
        $roleIds = $user->roles->pluck('id')->toArray();

        if (in_array(1, $roleIds)) {
            // Super Admin - all directorates
            return \App\Models\Directorate::orderBy('title')->get();
        }

        if (in_array(3, $roleIds)) {
            // Directorate User - their directorate only
            return \App\Models\Directorate::where('id', $user->directorate_id)->get();
        }

        // Project User - directorates from their projects
        return \App\Models\Directorate::whereHas('projects', function ($query) use ($user) {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        })->orderBy('title')->get();
    }

    /**
     * Get projects accessible by the user.
     */
    public function getProjectsForUser(User $user): Collection
    {
        $roleIds = $user->roles->pluck('id')->toArray();

        if (in_array(1, $roleIds)) {
            // Super Admin - all projects
            return \App\Models\Project::orderBy('title')->get();
        }

        if (in_array(3, $roleIds)) {
            // Directorate User - their directorate's projects
            return \App\Models\Project::where('directorate_id', $user->directorate_id)
                ->orderBy('title')
                ->get();
        }

        // Project User - their assigned projects
        return $user->projects()->orderBy('title')->get();
    }

    /**
     * Upload and store a new file.
     */
    public function uploadFile(FileDTO $dto, Model $modelInstance, User $user): File
    {
        if (!FileHelper::canAccessModel($user, $modelInstance)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized to upload files.');
        }

        // Generate unique filename and store
        $uniqueName = uniqid() . '.' . $dto->getExtension();
        $path = $dto->file->storeAs('files', $uniqueName, 'public');

        // Create file record
        $fileRecord = $modelInstance->files()->create([
            'filename' => $dto->getOriginalFileName(),
            'path' => $path,
            'file_type' => $dto->getExtension(),
            'file_size' => $dto->getSize(),
            'user_id' => $dto->userId,
        ]);

        Log::info('File uploaded', [
            'user_id' => $dto->userId,
            'file_id' => $fileRecord->id,
            'filename' => $dto->getOriginalFileName(),
        ]);

        return $fileRecord;
    }

    /**
     * Download a file.
     */
    public function downloadFile(File $file, User $user): string
    {
        if (!FileHelper::canAccessFile($user, $file)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized to download file.');
        }

        if (!$this->repository->exists($file->path)) {
            Log::error('File not found for download', [
                'file_id' => $file->id,
                'path' => $file->path
            ]);
            throw new \Exception('File not found.');
        }

        Log::info('File downloaded', [
            'file_id' => $file->id,
            'filename' => $file->filename,
            'user_id' => $user->id
        ]);

        return $this->repository->getPath($file->path);
    }

    /**
     * Delete a file.
     */
    public function deleteFile(File $file, User $user): void
    {
        if (!FileHelper::canAccessFile($user, $file)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized to delete file.');
        }

        try {
            $this->repository->delete($file);

            Log::info('File deleted', [
                'file_id' => $file->id,
                'filename' => $file->filename,
                'user_id' => $user->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete file', [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to delete file.');
        }
    }
}
