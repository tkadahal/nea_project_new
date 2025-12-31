<table class="min-w-full border-collapse bg-white dark:bg-gray-800">
    <thead class="bg-gray-100 dark:bg-gray-700">
        <tr>
            <th
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-center">
                Id</th>
            <th
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                Project</th>
            <th
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                {{ trans('global.budget.fields.government_loan') }}</th>
            <th
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                {{ trans('global.budget.fields.government_share') }}</th>
            <th
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                {{ trans('global.budget.fields.foreign_loan_budget') }}</th>
            <th
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                Source</th>
            <th
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                {{ trans('global.budget.fields.foreign_subsidy_budget') }}</th>
            <th
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                Source</th>
            <th
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                {{ trans('global.budget.fields.internal_budget') }}</th>
            <th
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                {{ trans('global.budget.fields.total_budget') }}</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
        @forelse ($projects as $index => $project)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td
                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200 font-medium">
                    {{ $loop->iteration }}
                </td>
                <td
                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm text-gray-700 dark:text-gray-200 font-medium">
                    {{ $project->title }}
                    <input type="hidden" name="project_id[{{ $project->id }}]" value="{{ $project->id }}">
                </td>
                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                    <input name="government_loan[{{ $project->id }}]" type="number" step="0.01" min="0"
                        value="{{ old('government_loan.' . $project->id, 0) }}"
                        class="w-full border-0 p-1 text-right government-loan-input excel-input tooltip-error bg-transparent"
                        data-project-id="{{ $project->id }}" placeholder="0.00" />
                </td>
                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                    <input name="government_share[{{ $project->id }}]" type="number" step="0.01" min="0"
                        value="{{ old('government_share.' . $project->id, 0) }}"
                        class="w-full border-0 p-1 text-right government-share-input excel-input tooltip-error bg-transparent"
                        data-project-id="{{ $project->id }}" placeholder="0.00" />
                </td>
                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                    <input name="foreign_loan_budget[{{ $project->id }}]" type="number" step="0.01" min="0"
                        value="{{ old('foreign_loan_budget.' . $project->id, 0) }}"
                        class="w-full border-0 p-1 text-right foreign-loan-budget-input excel-input tooltip-error bg-transparent"
                        data-project-id="{{ $project->id }}" placeholder="0.00" />
                </td>
                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                    <input name="foreign_loan_source[{{ $project->id }}]" type="text"
                        value="{{ old('foreign_loan_source.' . $project->id) }}"
                        class="w-full border-0 p-1 text-right foreign-loan-source-input excel-input tooltip-error bg-transparent"
                        data-project-id="{{ $project->id }}" placeholder="Source" />
                </td>
                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                    <input name="foreign_subsidy_budget[{{ $project->id }}]" type="number" step="0.01"
                        min="0" value="{{ old('foreign_subsidy_budget.' . $project->id, 0) }}"
                        class="w-full border-0 p-1 text-right foreign-subsidy-budget-input excel-input tooltip-error bg-transparent"
                        data-project-id="{{ $project->id }}" placeholder="0.00" />
                </td>
                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                    <input name="foreign_subsidy_source[{{ $project->id }}]" type="text"
                        value="{{ old('foreign_subsidy_source.' . $project->id) }}"
                        class="w-full border-0 p-1 text-right foreign-subsidy-source-input excel-input tooltip-error bg-transparent"
                        data-project-id="{{ $project->id }}" placeholder="Source" />
                </td>
                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                    <input name="internal_budget[{{ $project->id }}]" type="number" step="0.01" min="0"
                        value="{{ old('internal_budget.' . $project->id, 0) }}"
                        class="w-full border-0 p-1 text-right internal-budget-input excel-input tooltip-error bg-transparent"
                        data-project-id="{{ $project->id }}" placeholder="0.00" />
                </td>
                <td
                    class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right font-semibold bg-blue-50 dark:bg-blue-900/20">
                    <input name="total_budget[{{ $project->id }}]" type="number" step="0.01" min="0"
                        readonly value="{{ old('total_budget.' . $project->id, 0) }}"
                        class="w-full border-0 p-1 text-right total-budget-input excel-input bg-transparent font-semibold"
                        data-project-id="{{ $project->id }}" />
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10"
                    class="border border-gray-300 dark:border-gray-600 text-center py-12 text-gray-500 dark:text-gray-400">
                    {{ trans('global.noRecords') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
