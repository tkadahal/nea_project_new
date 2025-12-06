<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.user.title_singular') }} {{ trans('global.details') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.details_for') }} :
                <span class="font-semibold">
                    {{ $user->name }}
                </span>
            </p>
        </div>
        @can('user_access')
            <a href="{{ route('admin.user.index') }}"
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
                    {{ trans('global.user.fields.name') }} :
                </p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $user->name }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.user.fields.projects') }} :
                </p>
                <div class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    @forelse ($user->projects as $project)
                        <x-forms.badge :title="$project->title" color="green" />
                    @empty
                        <p class="text-lg text-gray-900 dark:text-gray-100">
                            {{ trans('global.noRecords') }}
                        </p>
                    @endforelse
                </div>

            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.user.fields.created_at') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $user->created_at->format('M d, Y H:i A') }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.user.fields.updated_at') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $user->updated_at->format('M d, Y H:i A') }}
                </p>
            </div>

            <div class="md:col-span-2">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.user.fields.directorate_id') }}
                </p>
                <x-forms.badge :title="$user->directorate->title ?? ''" color="purple" />
            </div>
        </div>

        <div class="mt-6 flex space-x-3">
            @can('user_edit')
                <a href="{{ route('admin.user.edit', $user) }}"
                    class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600
                      focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2
                      dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-offset-gray-900">
                    {{ trans('global.edit') }} {{ trans('global.user.title_singular') }}
                </a>
            @endcan

            @can('user_delete')
                <form action="{{ route('admin.user.destroy', $user) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600
                           focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2
                           dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-offset-gray-900">
                        {{ trans('global.delete') }} {{ trans('global.user.title_singular') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>
</x-layouts.app>
