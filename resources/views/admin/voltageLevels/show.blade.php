<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Voltage Levels {{ trans('global.details') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.details_for') }} :
                <span class="font-semibold">
                    {{ $voltageLevel->level }}
                </span>
            </p>
        </div>

        <a href="{{ route('admin.voltageLevel.index') }}"
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300
                 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2
                 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-900">
            {{ trans('global.back_to_list') }}
        </a>
    </div>

    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Voltage Level Name
                </p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $voltageLevel->level }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Voltage Value in kV
                </p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $voltageLevel->value_kv ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Is Active
                </p>
                <p class="mt-1">
                    @if ($voltageLevel->is_active)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-300">
                            Yes
                        </span>
                    @else
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-300">
                            No
                        </span>
                    @endif
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Sort Order
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $voltageLevel->sort_order ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Created At
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $voltageLevel->created_at?->format('M d, Y H:i A') ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Last Updated
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $voltageLevel->updated_at?->format('M d, Y H:i A') ?? '—' }}
                </p>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap gap-4">
            @can('voltageLevel_edit')
                <a href="{{ route('admin.voltageLevel.edit', $voltageLevel) }}"
                    class="px-5 py-2.5 bg-green-600 text-white font-medium rounded-md hover:bg-green-700
                         focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2
                         dark:bg-green-700 dark:hover:bg-green-800 dark:focus:ring-offset-gray-900 transition-colors">
                    {{ trans('global.edit') }} contract Type
                </a>
            @endcan

            @can('voltageLevel_delete')
                <form action="{{ route('admin.voltageLevel.destroy', $voltageLevel) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this contract type? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-5 py-2.5 bg-red-600 text-white font-medium rounded-md hover:bg-red-700
                                  focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2
                                  dark:bg-red-700 dark:hover:bg-red-800 dark:focus:ring-offset-gray-900 transition-colors">
                        {{ trans('global.delete') }} contract Type
                    </button>
                </form>
            @endcan
        </div>
    </div>
</x-layouts.app>
