<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\File\FileDTO;
use App\Helpers\File\FileHelper;
use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\File\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FileController extends Controller
{
    public function __construct(
        private readonly FileService $fileService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();

        $directorateId = $request->input('directorate_id');
        $projectId = $request->input('project_id');
        $search = $request->input('search');
        $view = $request->input('view', 'grid');
        $perPage = $request->input('per_page', 20);

        $files = $this->fileService->getFilesForUser($user, [
            'directorate_id' => $directorateId,
            'project_id' => $projectId,
            'search' => $search,
            'paginate' => true,
            'per_page' => $perPage,
        ]);

        $groupedFiles = collect($files->items())->groupBy(function ($file) {
            return $file->fileable_type.'|'.$file->fileable_id;
        });

        if ($request->ajax() || $request->input('ajax')) {
            $html = view('admin.files.components.files-grid', compact('groupedFiles'))->render();
            $paginationHtml = $files->links('admin.files.components.pagination')->render();

            return response()->json([
                'html' => $html,
                'pagination' => $paginationHtml,
                'count' => $files->total(),
                'current_page' => $files->currentPage(),
                'last_page' => $files->lastPage(),
            ]);
        }

        $directorates = $this->fileService->getDirectoratesForUser($user);
        $projects = $this->fileService->getProjectsForUser($user);

        return view('admin.files.index', compact('files', 'groupedFiles', 'directorates', 'projects'));
    }

    public function store(Request $request, string $model, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip',
        ]);

        try {
            $modelInstance = FileHelper::resolveModel($model, $id);

            $dto = FileDTO::forUpload(
                file: $validated['file'],
                modelType: $model,
                modelId: $id,
                userId: Auth::id()
            );

            $this->fileService->uploadFile($dto, $modelInstance, Auth::user());

            return redirect()->back()->with('success', 'File uploaded successfully.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to upload file.');
        }
    }

    public function download(File $file): BinaryFileResponse
    {
        try {
            $filePath = $this->fileService->downloadFile($file, Auth::user());

            return response()->download($filePath, $file->filename);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }
    }

    public function destroy(File $file): RedirectResponse
    {
        try {
            $this->fileService->deleteFile($file, Auth::user());

            return redirect()->back()->with('success', 'File deleted successfully.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
