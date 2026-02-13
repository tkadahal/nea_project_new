<!-- Grid View -->
<div id="grid-view" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @forelse ($groupedFiles as $key => $group)
        @php
            $file = $group->first();
            $folderName = match ($file->fileable_type) {
                \App\Models\Project::class => trans('global.project.title_singular') . ' : ' . $file->fileable->title,
                \App\Models\Contract::class => trans('global.contract.title_singular') . ' : ' . $file->fileable->title,
                \App\Models\Task::class => trans('global.task.title_singular') . ' : ' . $file->fileable->title,
                default => 'Unknown: ' . $file->fileable_type,
            };
            $folderId = str_replace('\\', '_', $file->fileable_type) . '_' . $file->fileable_id;
        @endphp

        <!-- Folder Card -->
        <div class="folder border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex flex-col items-center text-center bg-white dark:bg-gray-800 cursor-pointer hover:shadow-lg transition-shadow"
            data-folder-id="{{ $folderId }}" role="region" aria-label="Folder {{ $folderName }}">
            <div class="mb-2">
                <svg class="w-16 h-16 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M20 6h-8l-2-2H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2zm0 12H4V6h5.17l2 2H20v10z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate w-full">
                {{ $folderName }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $group->count() }} {{ trans('global.file.title') }}
            </p>
        </div>

        <!-- Files inside Folder (hidden by default) -->
        <div id="files-{{ $folderId }}"
            class="hidden col-span-full border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
            <div class="mb-4 flex justify-between items-center">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">{{ $folderName }}</h3>
                <button class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                    onclick="document.getElementById('files-{{ $folderId }}').classList.add('hidden')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach ($group as $file)
                    <div class="file-item border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800 flex flex-col shadow-sm hover:shadow-md transition-shadow"
                        role="region" aria-label="File {{ $file->filename }}">

                        <div
                            class="relative h-48 bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center group">
                            @if (in_array(strtolower($file->file_type ?? ''), ['mp4', 'mkv', 'webm', 'ogg', 'mov', 'avi']) ||
                                    str_contains(strtolower($file->filename ?? ''), '.mp4') ||
                                    str_contains(strtolower($file->filename ?? ''), '.mkv') ||
                                    str_contains(strtolower($file->filename ?? ''), '.webm') ||
                                    str_contains(strtolower($file->filename ?? ''), '.ogg'))
                                <div class="text-center">
                                    <svg class="w-28 h-28 text-blue-500 mx-auto opacity-90 group-hover:opacity-100 group-hover:scale-105 transition-all duration-300"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zM15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z" />
                                    </svg>
                                    <span class="mt-3 block text-base font-semibold text-blue-400">Video</span>
                                </div>

                                <span
                                    class="absolute top-4 left-4 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow-md uppercase tracking-wide">
                                    VIDEO
                                </span>

                                <span
                                    class="absolute bottom-4 left-4 right-4 text-center text-xs text-gray-300 bg-black/60 py-2 rounded-lg">
                                    Download to play
                                </span>
                            @elseif (in_array(strtolower($file->file_type ?? ''), ['jpg', 'jpeg', 'png', 'gif']) ||
                                    str_contains(strtolower($file->filename ?? ''), '.jpg') ||
                                    str_contains(strtolower($file->filename ?? ''), '.png') ||
                                    str_contains(strtolower($file->filename ?? ''), '.gif'))
                                <img src="{{ Storage::url($file->path) }}" alt="{{ $file->filename }}"
                                    class="w-full h-full object-cover">
                            @elseif (strtolower($file->file_type ?? '') === 'pdf' || str_contains(strtolower($file->filename ?? ''), '.pdf'))
                                <div class="flex flex-col items-center justify-center h-full text-red-400">
                                    <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M20 2H8a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V4a2 2 0 00-2-2zm-1 14H9V4h10v12z" />
                                    </svg>
                                    <span class="mt-4 text-lg font-semibold">PDF Document</span>
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center h-full text-gray-400">
                                    <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm4 18H6V4h7v5h5v11z" />
                                    </svg>
                                    <span class="mt-4 text-lg font-semibold uppercase">
                                        {{ $file->file_type ?: 'FILE' }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="p-4 flex flex-col flex-grow">
                            <p class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate mb-2"
                                title="{{ $file->filename }}">
                                {{ $file->filename }}
                            </p>

                            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1 mb-4">
                                <div class="flex justify-between">
                                    <span>Type:</span>
                                    <span class="font-medium">{{ strtoupper($file->file_type ?: '—') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Size:</span>
                                    <span class="font-medium">{{ round($file->file_size / 1024 / 1024, 2) }} MB</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Uploaded by:</span>
                                    <span class="font-medium">{{ $file->user->name ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Date:</span>
                                    <span class="font-medium">{{ $file->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>

                            <div
                                class="mt-auto flex justify-center gap-8 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <a href="{{ route('admin.files.download', $file) }}"
                                    class="flex items-center gap-2 text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download
                                </a>

                                @can('delete', $file)
                                    <form action="{{ route('admin.files.destroy', $file) }}" method="POST"
                                        onsubmit="return confirm('{{ __('Are you sure you want to delete this file?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="flex items-center gap-2 text-red-600 hover:text-red-800 font-medium transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400 text-lg">
            {{ trans('global.noRecords') }}
        </p>
    @endforelse
</div>

<!-- External Videos Section - Manual videos from storage/app/public/files/videos/ -->
<div class="mt-16 border-t border-gray-200 dark:border-gray-700 pt-12">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-8">
        Annual Program & Progress Videos
    </h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @php
            $externalVideos = [
                ['filename' => 'annualprogram-2026-02-04_19.00.32.mkv', 'title' => 'Annual Program 2026 - Overview'],
                [
                    'filename' => 'createing_in_an_annualprogram_2026-02-05_18.01.20.mkv',
                    'title' => 'Creating Annual Program 2026 - Part 1',
                ],
                [
                    'filename' => 'createing_in_an_annualprogram_2026-02-05_18.08.25.mkv',
                    'title' => 'Creating Annual Program 2026 - Part 2',
                ],
                ['filename' => 'overview-2026-02-04_18.54.04.mkv', 'title' => 'Annual Program 2026 - Summary'],
                ['filename' => 'progress-2026-02-07_15.47.56.mkv', 'title' => 'Progress Update - February 2026'],
                ['filename' => 'progress-2026-02-07_15.51.31.mkv', 'title' => 'Progress Update - February 2026 (2)'],
            ];
        @endphp

        @foreach ($externalVideos as $video)
            <div
                class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-800 shadow hover:shadow-xl transition-all duration-200">
                <!-- Video Preview Area -->
                <div class="h-48 bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center relative">
                    <svg class="w-28 h-28 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zM15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z" />
                    </svg>

                    <span
                        class="absolute top-4 left-4 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow-md uppercase tracking-wide">
                        VIDEO
                    </span>

                    <span
                        class="absolute bottom-4 inset-x-4 text-center text-xs text-gray-300 bg-black/60 py-2 rounded-lg">
                        Download to play locally
                    </span>
                </div>

                <!-- Info & Download -->
                <div class="p-5">
                    <h3 class="font-semibold text-lg mb-3 line-clamp-2">
                        {{ $video['title'] }}
                    </h3>

                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-5 space-y-1">
                        <div>File: <span class="font-medium">{{ $video['filename'] }}</span></div>
                        <div>Format: <span class="font-medium">MKV</span></div>
                    </div>

                    <a href="{{ asset('storage/files/videos/' . $video['filename']) }}" download
                        class="flex items-center justify-center gap-2 w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download Video
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- List View (only for grouped database files - no change) -->
<div id="list-view" class="hidden overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
                <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ trans('global.file.fields.name') }}
                </th>
                <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ trans('global.file.fields.type') }}
                </th>
                <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ trans('global.file.fields.size') }}
                </th>
                <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ trans('global.file.fields.uploaded_by') }}
                </th>
                <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ trans('global.file.fields.date') }}
                </th>
                <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ __('Actions') }}
                </th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($groupedFiles as $key => $group)
                @php
                    $file = $group->first();
                    $folderName = match ($file->fileable_type) {
                        \App\Models\Project::class => trans('global.project.title_singular') .
                            ' : ' .
                            $file->fileable->title,
                        \App\Models\Contract::class => trans('global.contract.title_singular') .
                            ' : ' .
                            $file->fileable->title,
                        \App\Models\Task::class => trans('global.task.title_singular') . ' : ' . $file->fileable->title,
                        default => 'Unknown: ' . $file->fileable_type,
                    };
                    $folderId = str_replace('\\', '_', $file->fileable_type) . '_' . $file->fileable_id;
                @endphp

                <!-- Folder Row -->
                <tr class="folder cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                    data-folder-id="{{ $folderId }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-yellow-500 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M20 6h-8l-2-2H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2zm0 12H4V6h5.17l2 2H20v10z" />
                            </svg>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $folderName }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        Folder
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $group->count() }} files
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap"></td>
                    <td class="px-6 py-4 whitespace-nowrap"></td>
                    <td class="px-6 py-4 whitespace-nowrap"></td>
                </tr>

                <!-- File Rows -->
                @foreach ($group as $file)
                    <tr class="hidden file-row-{{ $folderId }} hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center pl-8">
                                @if (in_array(strtolower($file->file_type ?? ''), ['mp4', 'mkv', 'webm', 'ogg', 'mov']) ||
                                        str_contains(strtolower($file->filename ?? ''), '.mp4') ||
                                        str_contains(strtolower($file->filename ?? ''), '.mkv') ||
                                        str_contains(strtolower($file->filename ?? ''), '.webm'))
                                    <svg class="w-5 h-5 text-blue-600 mr-3 flex-shrink-0" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                @elseif (in_array(strtolower($file->file_type ?? ''), ['jpg', 'jpeg', 'png', 'gif']) ||
                                        str_contains(strtolower($file->filename ?? ''), '.jpg') ||
                                        str_contains(strtolower($file->filename ?? ''), '.png'))
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z" />
                                    </svg>
                                @elseif (strtolower($file->file_type ?? '') === 'pdf' || str_contains(strtolower($file->filename ?? ''), '.pdf'))
                                    <svg class="w-5 h-5 text-red-500 mr-3 flex-shrink-0" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path d="M20 2H8a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V4a2 2 0 00-2-2z" />
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-500 mr-3 flex-shrink-0" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z" />
                                    </svg>
                                @endif
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                    {{ $file->filename }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 uppercase">
                            {{ $file->file_type }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ round($file->file_size / 1024, 2) }} KB
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $file->user->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $file->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-4">
                                <a href="{{ route('admin.files.download', $file) }}"
                                    class="text-blue-600 hover:text-blue-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </a>

                                @can('delete', $file)
                                    <form action="{{ route('admin.files.destroy', $file) }}" method="POST"
                                        onsubmit="return confirm('{{ __('Are you sure you want to delete this file?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                        {{ trans('global.noRecords') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
