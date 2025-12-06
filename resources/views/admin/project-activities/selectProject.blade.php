<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.project_activity.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.select') }} {{ trans('global.project.title_singular') }} {{ trans('global.and') }}
            {{ trans('global.fiscal_year.title_singular') }}
        </p>
    </div>

    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">
        <form method="GET" action="{{ route('admin.projectActivity.create') }}">
            @csrf

            @if ($errors->any())
                <div
                    class="mb-6 p-4 bg-red-100 text-red-800 border border-red-300 rounded-lg dark:bg-red-900 dark:text-red-200 dark:border-red-700">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-6">
                <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        {{ trans('global.selection') }}
                    </h3>
                    <div class="space-y-6">
                        <x-forms.select label="{{ trans('global.project.fields.title') }}" name="project_id"
                            id="project_id" :options="$projectOptions" :selected="old('project_id')"
                            placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('project_id')" class="js-single-select"
                            required />

                        <x-forms.select label="{{ trans('global.budget.fields.fiscal_year_id') }}"
                            name="fiscal_year_id" id="fiscal_year_id" :options="$fiscalYears" :selected="collect($fiscalYears)->firstWhere('selected', true)['value'] ?? ''"
                            placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('fiscal_year_id')" class="js-single-select"
                            required />
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <x-buttons.primary type="submit">
                    {{ trans('global.continue') }}
                </x-buttons.primary>
            </div>
        </form>
    </div>
</x-layouts.app>
