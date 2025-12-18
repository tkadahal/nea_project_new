<?php

declare(strict_types=1);

namespace App\Services\Project;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;

class ProjectFileService
{
    public static function handle(Project $project, Request $request): void
    {
        if (! $request->hasFile('files')) {
            self::clearTempFiles();
            return;
        }

        foreach ($request->file('files') as $file) {
            $path = $file->store('projects', 'public');

            $project->files()->create([
                'filename'  => $file->getClientOriginalName(),
                'path'      => $path,
                'file_type' => $file->extension(),
                'file_size' => $file->getSize(),
                'user_id'   => Auth::id(),
            ]);
        }

        self::clearTempFiles();
    }

    private static function clearTempFiles(): void
    {
        if (! Session::has('temp_files')) {
            return;
        }

        foreach (Session::get('temp_files', []) as $tempPath) {
            Storage::disk('public')->delete($tempPath);
        }

        Session::forget('temp_files');
    }
}
