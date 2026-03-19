<x-layouts.app>
    <!-- Page Title -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            Project Types
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.edit') }} Project Type
        </p>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6 p-6">
                <form class="max-w-3xl mx-auto" action="{{ route('admin.projectType.update', $projectType->id) }}"
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
                            Project Type Information
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-full">
                                <x-forms.input label="Project Type Name" name="name" type="text" :value="old('name', $projectType->name)"
                                    placeholder="Enter project type name" :error="$errors->first('name')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.input label="Project Type Code" name="code" type="text" :value="old('code', $projectType->code)"
                                    placeholder="Enter project type code (e.g. RD, INFRA)" :error="$errors->first('code')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.text-area label="Description" name="description" :value="old('description', $projectType->description)"
                                    placeholder="Enter project type description" :error="$errors->first('description')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.select label="Is Active" name="is_active" :options="[['value' => '1', 'label' => 'Yes'], ['value' => '0', 'label' => 'No']]" :selected="old('is_active', $projectType->is_active)"
                                    :error="$errors->first('is_active')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.input label="Sort Order" name="sort_order" type="number" :value="old('sort_order', $projectType->sort_order)"
                                    placeholder="Enter sort order (lower = higher priority)" :error="$errors->first('sort_order')" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <x-buttons.primary>
                            {{ trans('global.save') }}
                        </x-buttons.primary>
                        <a href="{{ route('admin.projectType.index') }}"
                            class="px-4 py-2 text-sm text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            {{ trans('global.cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
