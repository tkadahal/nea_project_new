<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.contract.title') }} {{ trans('global.details') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.details_for') }} :
                <span class="font-semibold">
                    {{ $contract->title }}
                </span>
            </p>
        </div>

        @can('contract_access')
            <a href="{{ route('admin.contract.index') }}"
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
                    {{ trans('global.contract.fields.title') }}
                </p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $contract->title }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.directorate_id') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $contract->directorate->title ?? 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.project_id') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $contract->project->title ?? 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.contractor') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $contract->contractor ?? 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.contract_amount') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ number_format($contract->contract_amount, 2) }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.contract_variation_amount') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ number_format($contract->contract_variation_amount, 2) }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.initial_contract_period') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $contract->initial_contract_period ?? 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.progress') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $contract->progress ?? 'N/A' }}%
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.status_id') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $contract->status->title ?? 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.priority_id') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $contract->priority->title ?? 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.contract_agreement_date') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $contract->contract_agreement_date ? $contract->contract_agreement_date->format('M d, Y') : 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.agreement_effective_date') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $contract->agreement_effective_date ? $contract->agreement_effective_date->format('M d, Y') : 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.agreement_effective_date') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $contract->agreement_completion_date ? $contract->agreement_completion_date->format('M d, Y') : 'N/A' }}
                </p>
            </div>

            <div class="col-span-full">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.description') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $contract->description ?? 'N/A' }}

                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.created_at') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $contract->created_at->format('M d, Y H:i A') }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.contract.fields.updated_at') }}
                </p>
                <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                    {{ $contract->updated_at->format('M d, Y H:i A') }}
                </p>
            </div>
        </div>

        <div class="mt-6 flex space-x-3">
            @can('contract_edit')
                <a href="{{ route('admin.contract.edit', $contract) }}"
                    class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600
                      focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2
                      dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-offset-gray-900">
                    {{ trans('global.edit') }} {{ trans('global.contract.title_singular') }}
                </a>
            @endcan

            @can('contract_delete')
                <form action="{{ route('admin.contract.destroy', $contract) }}" method="POST"
                    onsubmit="return confirm('{{ __('Are you sure you want to delete this contract? This action cannot be undone.') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600
                               focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2
                               dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-offset-gray-900">
                        {{ trans('global.delete') }} {{ trans('global.contract.title_singular') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>
</x-layouts.app>
