<?php

declare(strict_types=1);

namespace App\Services\Project\ProjectFormDataServices;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

/**
 * Service for handling file operations
 */
class FileService
{
    public function attachFiles(Project $project, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store('projects', 'public');
            $project->files()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'file_type' => $file->extension(),
                'file_size' => $file->getSize(),
                'user_id' => Auth::id(),
            ]);
        }
    }

    public function cleanupTempFiles(): void
    {
        if (Session::has('temp_files')) {
            foreach (Session::get('temp_files', []) as $tempPath) {
                Storage::disk('public')->delete($tempPath);
            }
            Session::forget('temp_files');
        }
    }

    public function deleteProjectFiles(Project $project): void
    {
        foreach ($project->files as $file) {
            Storage::disk('public')->delete($file->path);
            $file->delete();
        }
    }
}
