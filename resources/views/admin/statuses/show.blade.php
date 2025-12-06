<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.status.title') }} {{ trans('global.details') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.details_for') }} :
                <span class="font-semibold">
                    {{ $status->title }}
                </span>
            </p>
        </div>

        @can('status_access')
            <a href="{{ route('admin.status.index') }}"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300
                  focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2
                  dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-900">
                {{ trans('global.back_to_list') }}
            </a>
        @endcan
    </div>

    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.status.fields.title') }}
                </p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $status->title }}
                </p>
            </div>

            <div>
                <div class="mt-1 flex flex-wrap gap-2">
                    <x-forms.color-picker label="{{ trans('global.status.fields.color') }}" name="color"
                        value="{{ old('color', isset($status) ? $status->color : '') }}" />
                </div>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.status.fields.created_at') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $status->created_at->format('M d, Y H:i A') }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.status.fields.updated_at') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $status->updated_at->format('M d, Y H:i A') }}
                </p>
            </div>
        </div>

        <div class="mt-6 flex space-x-3">
            @can('status_edit')
                <a href="{{ route('admin.status.edit', $status) }}"
                    class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600
                      focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2
                      dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-offset-gray-900">
                    {{ trans('global.edit') }} {{ trans('global.status.title_singular') }}
                </a>
            @endcan

            @can('status_delete')
                <form action="{{ route('admin.status.destroy', $status) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this status? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600
                               focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2
                               dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-offset-gray-900">
                        {{ trans('global.delete') }} {{ trans('global.status.title_singular') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>
</x-layouts.app>
