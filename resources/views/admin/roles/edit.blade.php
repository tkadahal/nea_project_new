<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.role.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.edit') }} {{ trans('global.role.title_singular') }}
            </p>
        </div>

        <a href="{{ route('admin.role.index') }}"
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300
                  focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2
                  dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-900">
            {{ trans('global.back_to_list') }}
        </a>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6 p-6">
                <form class="max-w-3xl mx-auto" action="{{ route('admin.role.update', $role->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-red-100 text-red-800 border border-red-300 rounded-lg">
                            <ul class="list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div id="error-message"
                        class="mb-6 hidden bg-gray-100 border border-gray-400 text-gray-700 px-4 py-3 rounded-lg relative dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        <span id="error-text"></span>
                        <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-gray-500 dark:text-gray-400" role="button"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path
                                    d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                            </svg>
                        </button>
                    </div>

                    <div
                        class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.role.title_singular') }} {{ trans('global.information') }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-full">
                                <x-forms.input label="{{ trans('global.role.fields.title') }}" name="title"
                                    type="text" :value="old('title', $role->title)" :error="$errors->first('title')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.multi-select label="{{ trans('global.role.fields.permissions') }}"
                                    name="permissions[]" :options="collect($permissions)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old(
                                        'permissions',
                                        $role->permissions->pluck('id')->map(fn($id) => (string) $id)->toArray(),
                                    )" multiple
                                    placeholder="Select permissions" :error="$errors->first('permissions')" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <x-buttons.primary>
                            {{ trans('global.save') }}
                        </x-buttons.primary>
                        <a href="{{ route('admin.role.index') }}"
                            class="px-4 py-2 text-sm text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500 ml-2">
                            {{ trans('global.cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
