<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Storage;

class TempUpload extends Model
{
    use HasUuids, Prunable;

    protected $fillable = [
        'path',
        'original_name',
        'mime',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * 🔥 SINGLE SOURCE OF FILE DELETION
     */
    protected static function booted(): void
    {
        static::deleting(function (TempUpload $temp) {
            if (Storage::disk('local')->exists($temp->path)) {
                Storage::disk('local')->delete($temp->path);
            }
        });
    }

    /**
     * Auto-prune expired uploads
     */
    public function prunable()
    {
        return static::where('expires_at', '<', now());
    }

    public function getFullPath(): string
    {
        return storage_path('app/' . $this->path);
    }

    public function toUploadedFile(): \Illuminate\Http\UploadedFile
    {
        $fullPath = $this->getFullPath();

        if (!file_exists($fullPath)) {
            throw new \RuntimeException(
                'Temporary file no longer exists. Please upload again.'
            );
        }

        return new \Illuminate\Http\UploadedFile(
            $fullPath,
            $this->original_name,
            $this->mime,
            null,
            true // already on disk
        );
    }
}
