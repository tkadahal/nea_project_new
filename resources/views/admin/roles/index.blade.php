<x-layouts.app>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.role.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.role.title') }}
            </p>
        </div>
        <a href="{{ route('admin.role.create') }}"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700
                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                  dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-offset-gray-900">
            {{ trans('global.add') }} {{ trans('global.new') }}
        </a>
    </div>

    <div class="mb-6">
        <x-table.dataTable :headers="$headers" :data="$data" :routePrefix="$routePrefix" :actions="$actions" :deleteConfirmationMessage="$deleteConfirmationMessage"
            :arrayColumnColor="$arrayColumnColor" />
    </div>

</x-layouts.app>
