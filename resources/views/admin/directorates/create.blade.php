<x-layouts.app>
    <!-- Page Title -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.directorate.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.create') }} {{ trans('global.directorate.title_singular') }}
        </p>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6 p-6">
                <form class="max-w-3xl mx-auto" action="{{ route('admin.directorate.store') }}" method="POST">
                    @csrf

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
                        class="mb-6 hidden bg-gray-100 border-b border-gray-400 text-gray-800 px-4 py-3 rounded-lg relative dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        <span id="error-text"></span>
                        <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-gray-500 dark:text-gray-400" role="button"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path
                                    d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0 L10 8.183 7.349 5.152a1.2 1.2 0 1 1-5.394 5.394l2.758 3.152-2.758 2.758a1.2 1.2 0 0 1 0 1.697z" />
                            </svg>
                        </button>
                    </div>

                    <div
                        class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-6 pb-3 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.directorate.title_singular') }} {{ trans('global.information') }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-full">
                                <x-forms.input label="{{ trans('global.directorate.fields.title') }}" name="title"
                                    type="text" :value="old('title')" placeholder="Enter directorate name"
                                    :error="$errors->first('title')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.text-area label="{{ trans('global.directorate.fields.description') }}"
                                    name="description" :value="old('description')" placeholder="Enter directorate description"
                                    :error="$errors->first('description')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.multi-select label="{{ trans('global.directorate.fields.departments') }}"
                                    name="departments[]" :options="collect($departments)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('departments', [])" multiple
                                    placeholder="Select departments" :error="$errors->first('departments')" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-buttons.primary>
                            {{ trans('global.save') }}
                        </x-buttons.primary>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
