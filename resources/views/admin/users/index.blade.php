<x-layouts.app>
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.user.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.user.title') }}
            </p>
        </div>

        @if (auth()->user()->hasRole(\App\Models\Role::PROJECT_USER) ? $projectManager : true)
            @can('user_create')
                <a href="{{ route('admin.user.create') }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                          dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-offset-gray-900">
                    {{ trans('global.add') }} {{ trans('global.new') }}
                </a>
            @endcan
        @endif
    </div>

    <div class="mb-6">
        <x-table.dataTables.users :headers="$headers" :data="$data" :routePrefix="$routePrefix" :actions="$actions"
            :deleteConfirmationMessage="$deleteConfirmationMessage" :arrayColumnColor="$arrayColumnColor" />
    </div>


</x-layouts.app>
