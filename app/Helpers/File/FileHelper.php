<?php

declare(strict_types=1);

namespace App\Helpers\File;

use App\Models\User;
use App\Models\File;
use App\Models\Project;
use App\Models\Contract;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

class FileHelper
{
    /**
     * Resolve model instance from type and ID.
     */
    public static function resolveModel(string $modelType, int $modelId): Model
    {
        return match ($modelType) {
            'project' => Project::findOrFail($modelId),
            'contract' => Contract::findOrFail($modelId),
            'task' => Task::findOrFail($modelId),
            default => throw new \InvalidArgumentException("Invalid model type: {$modelType}"),
        };
    }

    /**
     * Check if user can access the model instance.
     */
    public static function canAccessModel(User $user, Model $modelInstance): bool
    {
        $roleIds = $user->roles->pluck('id')->toArray();

        if (in_array(1, $roleIds)) {
            return true; // Super Admin
        }

        if (in_array(3, $roleIds)) {
            return self::canDirectorateUserAccessModel($user, $modelInstance);
        }

        return self::canProjectUserAccessModel($user, $modelInstance);
    }

    /**
     * Check if user can access a file.
     */
    public static function canAccessFile(User $user, File $file): bool
    {
        $roleIds = $user->roles->pluck('id')->toArray();

        if (in_array(1, $roleIds)) {
            return true; // Super Admin
        }

        if (in_array(3, $roleIds)) {
            return self::canDirectorateUserAccessFile($user, $file);
        }

        return self::canProjectUserAccessFile($user, $file);
    }

    /**
     * Check directorate user access to model.
     */
    private static function canDirectorateUserAccessModel(User $user, Model $modelInstance): bool
    {
        $directorateId = $user->directorate_id;

        if (!$directorateId) {
            return false;
        }

        return match (true) {
            $modelInstance instanceof Project =>
            $modelInstance->directorate_id === $directorateId,
            $modelInstance instanceof Contract =>
            $modelInstance->project->directorate_id === $directorateId,
            $modelInstance instanceof Task =>
            $modelInstance->project->directorate_id === $directorateId,
            default => false,
        };
    }

    /**
     * Check project user access to model.
     */
    private static function canProjectUserAccessModel(User $user, Model $modelInstance): bool
    {
        $projectIds = $user->projects()->pluck('projects.id');

        return match (true) {
            $modelInstance instanceof Project =>
            $projectIds->contains($modelInstance->id),
            $modelInstance instanceof Contract =>
            $projectIds->contains($modelInstance->project_id),
            $modelInstance instanceof Task =>
            $projectIds->contains($modelInstance->project_id) ||
                $modelInstance->users()->where('users.id', $user->id)->exists(),
            default => false,
        };
    }

    /**
     * Check directorate user access to file.
     */
    private static function canDirectorateUserAccessFile(User $user, File $file): bool
    {
        $directorateId = $user->directorate_id;

        if (!$directorateId) {
            return false;
        }

        return match ($file->fileable_type) {
            'App\Models\Project' =>
            $file->fileable->directorate_id === $directorateId,
            'App\Models\Contract' =>
            $file->fileable->project->directorate_id === $directorateId,
            'App\Models\Task' =>
            $file->fileable->project->directorate_id === $directorateId,
            default => false,
        };
    }

    /**
     * Check project user access to file.
     */
    private static function canProjectUserAccessFile(User $user, File $file): bool
    {
        $projectIds = $user->projects()->pluck('projects.id');

        return match ($file->fileable_type) {
            'App\Models\Project' =>
            $projectIds->contains($file->fileable_id),
            'App\Models\Contract' =>
            $projectIds->contains($file->fileable->project_id),
            'App\Models\Task' =>
            $projectIds->contains($file->fileable->project_id) ||
                $file->fileable->users()->where('users.id', $user->id)->exists(),
            default => false,
        };
    }
}
