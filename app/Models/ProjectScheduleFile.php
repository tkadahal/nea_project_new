<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class ProjectScheduleFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'schedule_id',
        'file_name',
        'file_path',
        'file_type',
        'original_name',
        'file_size',
        'mime_type',
        'description',
        'uploaded_by',
    ];

    // ────────────────────────────────────────────────
    // Relationships
    // ────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ProjectActivitySchedule::class, 'schedule_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ────────────────────────────────────────────────
    // Accessors & Mutators
    // ────────────────────────────────────────────────

    /**
     * Get human-readable file size
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Get file icon based on type
     */
    public function getFileIconAttribute(): string
    {
        return match ($this->file_type) {
            'pdf' => 'fa-file-pdf text-red-500',
            'xer' => 'fa-file-code text-blue-500',
            'mpp' => 'fa-project-diagram text-green-500',
            default => 'fa-file text-gray-500'
        };
    }

    /**
     * Get file type label
     */
    public function getFileTypeLabelAttribute(): string
    {
        return match ($this->file_type) {
            'pdf' => 'PDF Document',
            'xer' => 'Primavera P6 Export',
            'mpp' => 'MS Project File',
            default => 'Unknown'
        };
    }

    // ────────────────────────────────────────────────
    // Helper Methods
    // ────────────────────────────────────────────────

    /**
     * Check if file exists
     */
    public function fileExists(): bool
    {
        return Storage::disk('public')->exists($this->file_path);
    }

    /**
     * Get full file URL
     */
    public function getFileUrl(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(): bool
    {
        if ($this->fileExists()) {
            return Storage::disk('public')->delete($this->file_path);
        }
        return true;
    }

    /**
     * Boot method to auto-delete files
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($file) {
            $file->deleteFile();
        });
    }
}
