<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.budgetHeading.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.create') }} {{ trans('global.budgetHeading.title_singular') }}
        </p>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6 p-6">
                <form class="max-w-3xl mx-auto" action="{{ route('admin.budgetHeading.store') }}" method="POST">
                    @csrf

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
                        class="mb-6 hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative">
                        <span id="error-text"></span>
                        <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-red-500" role="button"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path
                                    d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 010 1.698z" />
                            </svg>
                        </button>
                    </div>

                    <div
                        class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.budgetHeading.title_singular') }} {{ trans('global.information') }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-full">
                                <x-forms.input label="{{ trans('global.budgetHeading.fields.title') }}" name="title"
                                    type="text" :value="old('title')" :error="$errors->first('title')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.text-area label="{{ trans('global.budgetHeading.fields.description') }}"
                                    name="description" :value="old('description')" :error="$errors->first('description')" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <x-buttons.primary>
                            {{ trans('global.save') }}
                        </x-buttons.primary>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @vite(['resources/js/app.js', 'resources/js/formDependencies.js'])

    @push('scripts')
        <script>
            function waitForJQuery(callback, retries = 100) {
                if (typeof jQuery !== "undefined" && jQuery.fn.jquery && document.readyState === "complete") {
                    callback();
                } else if (retries > 0) {
                    console.warn("Permission Create Script: jQuery or DOM not ready, retrying... Retries left:", retries);
                    setTimeout(function() {
                        waitForJQuery(callback, retries - 1);
                    }, 200);
                } else {
                    console.error("Permission Create Script: Failed to load jQuery or DOM after maximum retries. Aborting.");
                }
            }

            waitForJQuery(function() {
                const $ = jQuery;
                console.log("Permission Create Script: jQuery ready.");

                $("#close-error").on("click", function() {
                    console.log("Permission Create Script: Close error button clicked.");
                    $("#error-message").addClass("hidden");
                    $("#error-text").text("");
                });
            });
        </script>
    @endpush
</x-layouts.app>
