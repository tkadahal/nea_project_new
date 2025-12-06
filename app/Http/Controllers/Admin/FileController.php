<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\File;
use App\Models\Project;
use App\Models\Contract;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FileController extends Controller
{
    /**
     * Display a listing of files based on user role.
     *
     * @return View
     */
    public function index(): View
    {
        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();
        $fileQuery = File::with(['fileable', 'user'])->latest();

        // Role-based filtering
        try {
            if (!in_array(1, $roleIds)) { // Not Super Admin
                if (in_array(3, $roleIds)) {
                    // Directorate User: Files for all projects/contracts/tasks in their directorate
                    $directorateId = $user->directorate_id;
                    if (!$directorateId) {
                        Log::warning('No directorate_id assigned to user', ['user_id' => $user->id]);
                        $files = collect();
                    } else {
                        // Get all project IDs for the directorate
                        $projectIds = Project::where('directorate_id', $directorateId)->pluck('id');
                        Log::info('Directorate project IDs', [
                            'user_id' => $user->id,
                            'directorate_id' => $directorateId,
                            'project_ids' => $projectIds->toArray(),
                        ]);

                        if ($projectIds->isEmpty()) {
                            Log::warning('No projects found for directorate', [
                                'user_id' => $user->id,
                                'directorate_id' => $directorateId,
                            ]);
                            $files = collect();
                        } else {
                            $fileQuery->where(function ($query) use ($projectIds) {
                                // Project files
                                $query->where('fileable_type', 'App\Models\Project')
                                    ->whereIn('fileable_id', $projectIds)
                                    // Contract files
                                    ->orWhere('fileable_type', 'App\Models\Contract')
                                    ->whereIn('fileable_id', function ($subQuery) use ($projectIds) {
                                        $subQuery->select('id')->from('contracts')
                                            ->whereIn('project_id', $projectIds);
                                    })
                                    // Task files
                                    ->orWhere('fileable_type', 'App\Models\Task')
                                    ->whereIn('fileable_id', function ($subQuery) use ($projectIds) {
                                        $subQuery->select('task_id')->from('project_task')
                                            ->whereIn('project_id', $projectIds);
                                    });
                            });

                            Log::info('Directorate file query', [
                                'user_id' => $user->id,
                                'directorate_id' => $directorateId,
                                'sql' => $fileQuery->toSql(),
                                'bindings' => $fileQuery->getBindings(),
                            ]);
                        }
                    }
                } else {
                    // Project User: Files for their projects (manager or team member)
                    $projectIds = $user->projects()->pluck('projects.id');
                    Log::info('Project user project IDs', [
                        'user_id' => $user->id,
                        'project_ids' => $projectIds->toArray(),
                    ]);

                    if ($projectIds->isEmpty()) {
                        Log::warning('No projects assigned to user', ['user_id' => $user->id]);
                    }

                    $fileQuery->where(function ($query) use ($projectIds, $user) {
                        $query->where('fileable_type', 'App\Models\Project')
                            ->whereIn('fileable_id', $projectIds)
                            ->orWhere('fileable_type', 'App\Models\Contract')
                            ->whereIn('fileable_id', function ($subQuery) use ($projectIds) {
                                $subQuery->select('id')->from('contracts')
                                    ->whereIn('project_id', $projectIds);
                            })
                            ->orWhere('fileable_type', 'App\Models\Task')
                            ->whereIn('fileable_id', function ($subQuery) use ($projectIds, $user) {
                                $subQuery->select('task_id')->from('project_task')
                                    ->whereIn('project_id', $projectIds)
                                    ->orWhereIn('task_id', function ($subSubQuery) use ($user) {
                                        $subSubQuery->select('task_id')->from('task_user')
                                            ->where('user_id', $user->id);
                                    });
                            });
                    });

                    Log::info('Project user file query', [
                        'user_id' => $user->id,
                        'sql' => $fileQuery->toSql(),
                        'bindings' => $fileQuery->getBindings(),
                    ]);
                }
            }

            $files = $fileQuery->get();
            Log::info('Files retrieved for user', [
                'user_id' => $user->id,
                'count' => $files->count(),
                'files' => $files->map(fn($file) => [
                    'id' => $file->id,
                    'filename' => $file->filename,
                    'fileable_type' => $file->fileable_type,
                    'fileable_id' => $file->fileable_id,
                ])->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching files', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $files = collect();
        }

        return view('admin.files.index', compact('files'));
    }

    /**
     * Store a newly uploaded file.
     *
     * @param Request $request
     * @param string $model
     * @param int $id
     * @return RedirectResponse
     */
    public function store(Request $request, string $model, int $id): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip', // 10MB max
        ]);

        $modelInstance = match ($model) {
            'project' => Project::findOrFail($id),
            'contract' => Contract::findOrFail($id),
            'task' => Task::findOrFail($id),
            default => abort(404),
        };

        // Inline authorization check
        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();
        $allowed = false;

        if (in_array(1, $roleIds)) {
            $allowed = true; // Super Admin
        } elseif (in_array(3, $roleIds)) {
            // Directorate User
            $directorateId = $user->directorate_id;
            if ($modelInstance instanceof Project) {
                $allowed = $directorateId && $modelInstance->directorate_id === $directorateId;
            } elseif ($modelInstance instanceof Contract) {
                $allowed = $directorateId && $modelInstance->project->directorate_id === $directorateId;
            } elseif ($modelInstance instanceof Task) {
                $allowed = $directorateId && $modelInstance->project->directorate_id === $directorateId;
            }
        } else {
            // Project User
            $projectIds = $user->projects()->pluck('projects.id');
            if ($modelInstance instanceof Project) {
                $allowed = $projectIds->contains($modelInstance->id);
            } elseif ($modelInstance instanceof Contract) {
                $allowed = $projectIds->contains($modelInstance->project_id);
            } elseif ($modelInstance instanceof Task) {
                $allowed = $projectIds->contains($modelInstance->project_id) ||
                    $modelInstance->users()->where('users.id', $user->id)->exists();
            }
        }

        if (!$allowed) {
            abort(403, 'Unauthorized to upload files.');
        }

        $file = $request->file('file');
        $uniqueName = uniqid() . '.' . $file->extension();
        $path = $file->storeAs('files', $uniqueName, 'public');
        $fileRecord = $modelInstance->files()->create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'file_type' => $file->extension(),
            'file_size' => $file->getSize(),
            'user_id' => Auth::id(),
        ]);

        Log::info('File uploaded', [
            'user_id' => Auth::id(),
            'file_id' => $fileRecord->id,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
        ]);

        return redirect()->back()->with('success', 'File uploaded successfully.');
    }

    /**
     * Download a file from storage.
     *
     * @param File $file
     * @return BinaryFileResponse
     */
    public function download(File $file): BinaryFileResponse
    {
        // Inline authorization check
        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();
        $allowed = false;

        if (in_array(1, $roleIds)) {
            $allowed = true; // Super Admin
        } elseif (in_array(3, $roleIds)) {
            // Directorate User
            $directorateId = $user->directorate_id;
            if ($file->fileable_type === 'App\Models\Project') {
                $allowed = $directorateId && $file->fileable->directorate_id === $directorateId;
            } elseif ($file->fileable_type === 'App\Models\Contract') {
                $allowed = $directorateId && $file->fileable->project->directorate_id === $directorateId;
            } elseif ($file->fileable_type === 'App\Models\Task') {
                $allowed = $directorateId && $file->fileable->project->directorate_id === $directorateId;
            }
        } else {
            // Project User
            $projectIds = $user->projects()->pluck('projects.id');
            if ($file->fileable_type === 'App\Models\Project') {
                $allowed = $projectIds->contains($file->fileable_id);
            } elseif ($file->fileable_type === 'App\Models\Contract') {
                $allowed = $projectIds->contains($file->fileable->project_id);
            } elseif ($file->fileable_type === 'App\Models\Task') {
                $allowed = $projectIds->contains($file->fileable->project_id) ||
                    $file->fileable->users()->where('users.id', $user->id)->exists();
            }
        }

        if (!$allowed) {
            abort(403, 'Unauthorized to download file.');
        }

        if (!Storage::disk('public')->exists($file->path)) {
            Log::error('File not found for download', ['file_id' => $file->id, 'path' => $file->path]);
            abort(404, 'File not found.');
        }

        Log::info('File downloaded', ['file_id' => $file->id, 'filename' => $file->filename]);
        return response()->download(Storage::disk('public')->path($file->path), $file->filename);
    }

    /**
     * Delete a file from storage and database.
     *
     * @param File $file
     * @return RedirectResponse
     */
    public function destroy(File $file): RedirectResponse
    {
        // Inline authorization check
        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();
        $allowed = false;

        if (in_array(1, $roleIds)) {
            $allowed = true; // Super Admin
        } elseif (in_array(3, $roleIds)) {
            // Directorate User
            $directorateId = $user->directorate_id;
            if ($file->fileable_type === 'App\Models\Project') {
                $allowed = $directorateId && $file->fileable->directorate_id === $directorateId;
            } elseif ($file->fileable_type === 'App\Models\Contract') {
                $allowed = $directorateId && $file->fileable->project->directorate_id === $directorateId;
            } elseif ($file->fileable_type === 'App\Models\Task') {
                $allowed = $directorateId && $file->fileable->project->directorate_id === $directorateId;
            }
        } else {
            // Project User
            $projectIds = $user->projects()->pluck('projects.id');
            if ($file->fileable_type === 'App\Models\Project') {
                $allowed = $projectIds->contains($file->fileable_id);
            } elseif ($file->fileable_type === 'App\Models\Contract') {
                $allowed = $projectIds->contains($file->fileable->project_id);
            } elseif ($file->fileable_type === 'App\Models\Task') {
                $allowed = $projectIds->contains($file->fileable->project_id) ||
                    $file->fileable->users()->where('users.id', $user->id)->exists();
            }
        }

        if (!$allowed) {
            abort(403, 'Unauthorized to delete file.');
        }

        try {
            Storage::disk('public')->delete($file->path);
            $file->delete();
            Log::info('File deleted', ['file_id' => $file->id, 'filename' => $file->filename]);
            return redirect()->back()->with('success', 'File deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete file', ['file_id' => $file->id, 'user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete file.');
        }
    }
}
