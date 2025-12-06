<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.role.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.create') }} {{ trans('global.role.title_singular') }}
        </p>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6 p-6">
                <form id="role-form" class="max-w-3xl mx-auto" action="{{ route('admin.role.store') }}" method="POST">
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
                                    type="text" :value="old('title')" :error="$errors->first('title')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.multi-select label="{{ trans('global.role.fields.permissions') }}"
                                    name="permissions[]" :options="collect($permissions)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('permissions', [])" multiple
                                    placeholder="Select permissions" :error="$errors->first('permissions')" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <x-buttons.primary id="submit-button" type="submit" :disabled="false">
                            {{ trans('global.save') }}
                        </x-buttons.primary>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function waitForJQuery(callback, retries = 50, interval = 100) {
                if (typeof jQuery !== "undefined" && jQuery.fn.jquery && document.readyState !== "loading") {
                    console.log("jQuery and DOM ready. jQuery version:", jQuery.fn.jquery, "DOM state:", document.readyState);
                    callback();
                } else if (retries > 0) {
                    console.warn("jQuery or DOM not ready, retrying... jQuery:", typeof jQuery !== "undefined" ? jQuery.fn
                        .jquery : "undefined", "DOM:", document.readyState, "Retries left:", retries);
                    setTimeout(function() {
                        waitForJQuery(callback, retries - 1, interval);
                    }, interval);
                } else {
                    console.error("Failed to load jQuery or DOM after maximum retries.");
                    $("#error-message").removeClass("hidden").find("#error-text").text(
                        "{{ trans('global.role.errors.form_init_failed') }}");
                }
            }

            waitForJQuery(function() {
                const $ = jQuery;

                const $form = $('#role-form');
                const $submitButton = $('#submit-button');
                const $errorMessage = $('#error-message');
                const $errorText = $('#error-text');
                const $closeError = $('#close-error');

                function showError(message) {
                    $errorText.text(message);
                    $errorMessage.removeClass('hidden');
                }

                function hideError() {
                    $errorMessage.addClass('hidden');
                    $errorText.text('');
                }

                $closeError.on('click', hideError);

                $form.on('submit', function(e) {
                    e.preventDefault();
                    console.log('Form submit attempted');

                    if ($submitButton.prop('disabled')) {
                        console.log('Form submission prevented: button is disabled');
                        return;
                    }

                    const title = $('input[name="title"]').val();
                    if (!title) {
                        showError('{{ trans('global.role.errors.missing_title') }}');
                        console.log('Form submission prevented: title is required');
                        return;
                    }

                    $submitButton
                        .prop('disabled', true)
                        .addClass('opacity-50 cursor-not-allowed')
                        .text('{{ trans('global.saving') }}...');
                    console.log('Submit button disabled');

                    $.ajax({
                        url: $form.attr('action'),
                        method: 'POST',
                        data: $form.serialize(),
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(response) {
                            console.log('Form submission success:', response);
                            hideError();
                            window.location.href = '{{ route('admin.role.index') }}';
                        },
                        error: function(xhr) {
                            console.error('Form submission error:', xhr.status, xhr.responseJSON);
                            $submitButton
                                .prop('disabled', false)
                                .removeClass('opacity-50 cursor-not-allowed')
                                .text('{{ trans('global.save') }}');
                            showError(
                                xhr.responseJSON?.message ||
                                '{{ trans('global.role.errors.create_failed') }}'
                            );
                        }
                    });
                });
            });
        </script>
    @endpush
</x-layouts.app>
