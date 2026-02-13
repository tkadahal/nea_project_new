<?php

declare(strict_types=1);

namespace App\DTOs\File;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class FileDTO
{
    public function __construct(
        public readonly ?UploadedFile $file = null,
        public readonly ?string $modelType = null,
        public readonly ?int $modelId = null,
        public readonly ?int $userId = null,
        public readonly ?array $roleIds = null,
        public readonly ?int $directorateId = null,
        public readonly ?Collection $projectIds = null
    ) {}

    /**
     * Create DTO for file upload.
     */
    public static function forUpload(UploadedFile $file, string $modelType, int $modelId, int $userId): self
    {
        return new self(
            file: $file,
            modelType: $modelType,
            modelId: $modelId,
            userId: $userId
        );
    }

    /**
     * Create DTO for user query.
     */
    public static function forUserQuery(int $userId, array $roleIds, ?int $directorateId, Collection $projectIds): self
    {
        return new self(
            userId: $userId,
            roleIds: $roleIds,
            directorateId: $directorateId,
            projectIds: $projectIds
        );
    }

    // File upload helpers
    public function getOriginalFileName(): string
    {
        return $this->file?->getClientOriginalName() ?? '';
    }

    public function getExtension(): string
    {
        return $this->file?->extension() ?? '';
    }

    public function getSize(): int
    {
        return $this->file?->getSize() ?? 0;
    }

    // Role check helpers
    public function isSuperAdmin(): bool
    {
        return in_array(1, $this->roleIds ?? []);
    }

    public function isDirectorateUser(): bool
    {
        return in_array(3, $this->roleIds ?? []);
    }

    public function isProjectUser(): bool
    {
        return !$this->isSuperAdmin() && !$this->isDirectorateUser();
    }

    public function hasDirectorate(): bool
    {
        return $this->directorateId !== null;
    }

    public function hasProjects(): bool
    {
        return $this->projectIds !== null && $this->projectIds->isNotEmpty();
    }
}
