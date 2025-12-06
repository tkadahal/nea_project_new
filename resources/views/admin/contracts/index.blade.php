<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.contract.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.contract.title') }}
            </p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <button id="listViewButton"
                    class="p-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-500 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <button id="gridViewButton"
                    class="p-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-500 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h6v6H4V6zm10 0h6v6h-6V6zm-10 10h6v6H4v-6zm10 0h6v6h-6v-6z"></path>
                    </svg>
                </button>
            </div>

            @can('contract_create')
                <a href="{{ route('admin.contract.create') }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                          dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-offset-gray-900">
                    {{ trans('global.add') }} {{ trans('global.new') }}
                </a>
            @endcan

        </div>
    </div>

    <div class="mb-6">
        <!-- List View -->
        <div id="listView" class="hidden">
            <x-table.dataTables.contracts :headers="$headers" :data="$tableData" :routePrefix="$routePrefix" :actions="$actions"
                :deleteConfirmationMessage="$deleteConfirmationMessage" :arrayColumnColor="$arrayColumnColor" />
        </div>
        <!-- Grid View -->
        <div id="gridView" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($data as $item)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ $item['title'] }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $item['description'] }}</p>
                    @foreach ($item['fields'] as $field)
                        @if ($field['key'] === 'directorate')
                            <p class="text-gray-600 dark:text-gray-400 mt-1">
                                <span class="font-semibold">Directorate:</span>
                                <x-forms.badge :title="$field['value']" :color="$field['color'] ?? 'gray'" />
                            </p>
                        @else
                            <p class="text-gray-600 dark:text-gray-400 mt-1">
                                <span class="font-semibold">{{ $field['label'] }}:</span>
                                <span style="color: {{ $field['color'] ?? 'gray' }}">{{ $field['value'] }}</span>
                            </p>
                        @endif
                    @endforeach
                    <div class="mt-4 flex space-x-2">
                        @can('add_contract_extension')
                            <a href="{{ route('admin.contract.extensions.create', $item['id']) }}"
                                class="border border-blue-500 text-blue-500 px-2 py-1 rounded text-xs hover:bg-blue-500 hover:text-white dark:hover:bg-blue-500 dark:hover:text-white">
                                Add Extension
                            </a>
                        @endcan
                        @can('contract_show')
                            <a href="{{ route($routePrefix . '.show', $item['id']) }}"
                                class="border border-blue-500 text-blue-500 px-2 py-1 rounded text-xs hover:bg-blue-500 hover:text-white dark:hover:bg-blue-500 dark:hover:text-white">
                                {{ trans('global.view') }}
                            </a>
                        @endcan
                        @can('contract_edit')
                            <a href="{{ route($routePrefix . '.edit', $item['id']) }}"
                                class="border border-blue-500 text-blue-500 px-2 py-1 rounded text-xs hover:bg-blue-500 hover:text-white dark:hover:bg-blue-500 dark:hover:text-white">
                                {{ trans('global.edit') }}
                            </a>
                        @endcan
                        @can('contract_delete')
                            <form action="{{ route($routePrefix . '.destroy', $item['id']) }}" method="POST"
                                onsubmit="return confirm('{{ $deleteConfirmationMessage }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700">
                                    {{ trans('global.delete') }}
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const listViewButton = document.getElementById('listViewButton');
            const gridViewButton = document.getElementById('gridViewButton');
            const listView = document.getElementById('listView');
            const gridView = document.getElementById('gridView');

            // Set default view to list
            listView.classList.remove('hidden');
            listViewButton.classList.add('text-blue-600', 'dark:text-blue-500');

            listViewButton.addEventListener('click', () => {
                listView.classList.remove('hidden');
                gridView.classList.add('hidden');
                listViewButton.classList.add('text-blue-600', 'dark:text-blue-500');
                gridViewButton.classList.remove('text-blue-600', 'dark:text-blue-500');
                gridViewButton.classList.add('text-gray-600', 'dark:text-gray-400');
            });

            gridViewButton.addEventListener('click', () => {
                gridView.classList.remove('hidden');
                listView.classList.add('hidden');
                gridViewButton.classList.add('text-blue-600', 'dark:text-blue-500');
                listViewButton.classList.remove('text-blue-600', 'dark:text-blue-500');
                listViewButton.classList.add('text-gray-600', 'dark:text-gray-400');
            });
        });
    </script>
</x-layouts.app>
