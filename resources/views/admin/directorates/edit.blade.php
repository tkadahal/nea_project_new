<x-layouts.app>
    <!-- Page Title -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.directorate.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.edit') }} {{ trans('global.directorate.title_singular') }}
            </p>
        </div>

        @can('directorate_access')
            <a href="{{ route('admin.directorate.index') }}"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300
                  focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2
                  dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-900">
                {{ trans('global.back_to_list') }}
            </a>
        @endcan
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6 p-6">
                <form class="max-w-3xl mx-auto" action="{{ route('admin.directorate.update', $directorate) }}"
                    method="POST">
                    @csrf
                    @method('PUT')

                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-red-400 text-white border border-red-500 rounded-lg">
                            <ul class="list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div id="error-message"
                        class="mb-6 hidden bg-gray-100 border border-gray-400 text-gray-800 px-4 py-3 rounded-lg relative dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        <span id="error-text"></span>
                        <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-gray-500 dark:text-gray-400" role="button"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path
                                    d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15z" />
                            </svg>
                        </button>
                    </div>

                    <div class="mb-6 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-400 mb-3 pb-3 border-b">
                            {{ trans('global.directorate.title_singular') }} {{ trans('global.information') }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-full">
                                <x-forms.input label="{{ trans('global.directorate.fields.title') }}" name="title"
                                    type="text" :value="old('title', $directorate->title)" placeholder="Enter directorate name"
                                    :error="$errors->first('title')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.text-area label="{{ trans('global.directorate.fields.description') }}"
                                    name="description" :value="old('description', $directorate->description)" placeholder="Enter directorate description"
                                    :error="$errors->first('description')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.multi-select label="{{ trans('global.directorate.fields.departments') }}"
                                    name="departments[]" :options="collect($departments)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old(
                                        'departments',
                                        $directorate->departments->pluck('id')->map(fn($id) => (string) $id)->toArray(),
                                    )" multiple
                                    placeholder="Select departments" :error="$errors->first('departments')" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <x-buttons.primary>
                            {{ trans('global.save') }}
                        </x-buttons.primary>
                        <a href="{{ route('admin.directorate.index') }}"
                            class="px-4 py-2 text-sm text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            {{ trans('global.cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
