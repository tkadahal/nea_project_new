<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.directorate.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.directorate.title') }}
            </p>
        </div>

        <div class="flex items-center space-x-4">
            <!-- View Toggle Icons -->
            <div class="flex space-x-2">
                <button id="cardViewBtn"
                    class="p-2 bg-blue-600 text-white rounded-md hover:bg-blue-700
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                           dark:bg-blue-600 dark:text-white dark:hover:bg-blue-700
                           active:bg-blue-600 active:text-white"
                    title="Card View">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                        </path>
                    </svg>
                </button>
                <button id="tableViewBtn"
                    class="p-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                           dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                    title="Table View">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M3 14h18m-9-4v8m-7 0h14A2 2 0 0022 16V8a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2z">
                        </path>
                    </svg>
                </button>
            </div>

            @can('directorate_create')
                <a href="{{ route('admin.directorate.create') }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700
                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                      dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-offset-gray-900">
                    {{ trans('global.add') }} {{ trans('global.new') }}
                </a>
            @endcan
        </div>
    </div>

    <!-- Table View -->
    <div id="tableView" class="mb-6 hidden">
        <x-table.dataTables.directorates :headers="$headers" :data="$data" :routePrefix="$routePrefix" :actions="$actions"
            :deleteConfirmationMessage="$deleteConfirmationMessage" :arrayColumnColor="$arrayColumnColor" />
    </div>

    <!-- Card View -->
    <div id="cardView" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($directorates as $directorate)
                <div
                    class="bg-gray-50 dark:bg-gray-700 rounded-lg shadow-md p-6 mb-4 border border-gray-300 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                        {{ $directorate->title }}
                    </h3>
                    <div class="mt-3">
                        <p class="text-gray-600 dark:text-purple-400 font-bold">Departments:</p>
                        <ul class="list-disc list-inside text-gray-600 dark:text-gray-400">
                            @foreach ($directorate->departments as $department)
                                <li>
                                    <a class="text-gray-600 dark:text-blue-400 font-medium"
                                        href="{{ route('admin.department.show', $department->id) }}">
                                        {{ $department->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="mt-4 flex flex-col space-y-2">
                        @can('directorate_access')
                            <div class="flex space-x-2">
                                @can('directorate_show')
                                    <a href="{{ route('admin.directorate.show', $directorate->id) }}"
                                        class="p-2 bg-blue-500 text-white rounded hover:bg-blue-600" title="View">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                            </path>
                                        </svg>
                                    </a>
                                @endcan

                                @can('directorate_edit')
                                    <a href="{{ route('admin.directorate.edit', $directorate->id) }}"
                                        class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                    </a>
                                @endcan

                                @can('directorate_delete')
                                    <form action="{{ route('admin.directorate.destroy', $directorate->id) }}" method="POST"
                                        onsubmit="return confirm('{{ $deleteConfirmationMessage }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 bg-red-500 text-white rounded hover:bg-red-600"
                                            title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4M7 7h10m-5 4v6">
                                                </path>
                                            </svg>
                                        </button>
                                    </form>
                                @endcan

                                @can('task_create')
                                    <a href="{{ route('admin.task.create') }}?directorate_id={{ $directorate->id }}"
                                        class="border border-blue-500 text-blue-500 px-4 py-2 rounded text-sm
                                          hover:bg-blue-500 hover:text-white dark:hover:bg-blue-500 dark:hover:text-white">
                                        Add Task
                                    </a>
                                @endcan
                            </div>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- JavaScript for View Toggle -->
    <script>
        document.getElementById('tableViewBtn').addEventListener('click', function() {
            document.getElementById('tableView').classList.remove('hidden');
            document.getElementById('cardView').classList.add('hidden');
            this.classList.add('bg-blue-600', 'text-white', 'dark:bg-blue-600', 'dark:text-white');
            this.classList.remove('bg-gray-200', 'text-gray-800', 'dark:bg-gray-700', 'dark:text-gray-200');
            document.getElementById('cardViewBtn').classList.remove('bg-blue-600', 'text-white', 'dark:bg-blue-600',
                'dark:text-white');
            document.getElementById('cardViewBtn').classList.add('bg-gray-200', 'text-gray-800', 'dark:bg-gray-700',
                'dark:text-gray-200');
        });

        document.getElementById('cardViewBtn').addEventListener('click', function() {
            document.getElementById('cardView').classList.remove('hidden');
            document.getElementById('tableView').classList.add('hidden');
            this.classList.add('bg-blue-600', 'text-white', 'dark:bg-blue-600', 'dark:text-white');
            this.classList.remove('bg-gray-200', 'text-gray-800', 'dark:bg-gray-700', 'dark:text-gray-200');
            document.getElementById('tableViewBtn').classList.remove('bg-blue-600', 'text-white',
                'dark:bg-blue-600', 'dark:text-white');
            document.getElementById('tableViewBtn').classList.add('bg-gray-200', 'text-gray-800',
                'dark:bg-gray-700', 'dark:text-gray-200');
        });
    </script>
</x-layouts.app>
