<x-layouts.app>
    <div class="mb-6">
        <div class="flex justify-between items-start mb-2">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.projectActivity.title') }} - {{ $project->title }} -
                {{ $fiscalYear->title ?? $fiscalYear->id }}
            </h1>

            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.projectActivity.downloadAcitivites', [$projectId, $fiscalYearId]) }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600">
                    <svg class="h-4 w-4 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Download Excel with Weighted Progress
                </a>
                <a href="{{ route('admin.projectActivity.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600">
                    {{ trans('global.back_to_list') }}
                </a>
            </div>
        </div>
        <p class="text-gray-600 dark:text-gray-400">
            Detailed breakdown of Annual Program for Fiscal Year {{ $fiscalYear->title }}.
        </p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">

        @include('admin.project-activities.partials.activity-table', [
            'activities' => $capitalPlans,
            'header' => trans('global.projectActivity.headers.capital'),
            'sums' => $capitalSums,
        ])

        @include('admin.project-activities.partials.activity-table', [
            'activities' => $recurrentPlans,
            'header' => trans('global.projectActivity.headers.recurrent'),
            'sums' => $recurrentSums,
        ])

        <div class="mt-8 flex space-x-3">
            <a href="{{ route('admin.projectActivity.edit', [$projectId, $fiscalYearId]) }}"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                {{ trans('global.edit') }}
            </a>
            <a href="{{ route('admin.projectActivity.destroy', [$projectId, $fiscalYearId]) }}"
                class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600"
                onclick="return confirm('{{ trans('global.areYouSure') }}')">
                {{ trans('global.delete') }}
            </a>
        </div>
    </div>

    @push('styles')
        <style>
            .projectActivity-row[data-depth="1"] td:nth-child(2) {
                padding-left: 20px;
            }

            .projectActivity-row[data-depth="2"] td:nth-child(2) {
                padding-left: 40px;
            }

            .projectActivity-table {
                table-layout: fixed;
            }

            /* Fixed columns: Sticky left */
            .projectActivity-table th:nth-child(1),
            .projectActivity-table td:nth-child(1) {
                /* # */
                position: sticky;
                left: 0;
                z-index: 30;
                background-color: inherit;
                border-right: 1px solid #d1d5db;
                width: 3rem;
            }

            .projectActivity-table th:nth-child(2),
            .projectActivity-table td:nth-child(2) {
                /* Program */
                position: sticky;
                left: 3rem;
                z-index: 30;
                background-color: inherit;
                border-right: 1px solid #d1d5db;
                width: 20rem;
            }

            .projectActivity-table th:nth-child(3),
            .projectActivity-table td:nth-child(3) {
                /* Total Qty */
                position: sticky;
                left: 23rem;
                z-index: 30;
                background-color: inherit;
                border-right: 1px solid #d1d5db;
                width: 8rem;
                text-align: right;
            }

            .projectActivity-table th:nth-child(4),
            .projectActivity-table td:nth-child(4) {
                /* Total Budget */
                position: sticky;
                left: 31rem;
                z-index: 30;
                background-color: inherit;
                border-right: 1px solid #d1d5db;
                width: 8rem;
                text-align: right;
            }

            .projectActivity-table th:nth-child(5),
            .projectActivity-table td:nth-child(5) {
                /* Completed Qty */
                position: sticky;
                left: 39rem;
                z-index: 30;
                background-color: inherit;
                border-right: 1px solid #d1d5db;
                width: 8rem;
                text-align: right;
            }

            .projectActivity-table th:nth-child(6),
            .projectActivity-table td:nth-child(6) {
                /* Expenses */
                position: sticky;
                left: 47rem;
                z-index: 30;
                background-color: inherit;
                border-right: 1px solid #d1d5db;
                width: 8rem;
                text-align: right;
            }

            .projectActivity-table th:nth-child(7),
            .projectActivity-table td:nth-child(7) {
                /* Planned Qty */
                position: sticky;
                left: 55rem;
                z-index: 30;
                background-color: inherit;
                border-right: 1px solid #d1d5db;
                width: 8rem;
                text-align: right;
            }

            .projectActivity-table th:nth-child(8),
            .projectActivity-table td:nth-child(8) {
                /* Planned Budget */
                position: sticky;
                left: 63rem;
                z-index: 30;
                background-color: inherit;
                border-right: 2px solid #d1d5db;
                /* Thicker separator for scrollable */
                width: 8rem;
                text-align: right;
            }

            /* Scrollable columns start at nth-child(9): Progress, Qty, Amount */
            .projectActivity-table th:nth-child(9),
            .projectActivity-table td:nth-child(9),
            .projectActivity-table th:nth-child(10),
            .projectActivity-table td:nth-child(10),
            .projectActivity-table th:nth-child(11),
            .projectActivity-table td:nth-child(11) {
                width: 8rem;
                text-align: right;
            }

            .dark .projectActivity-table th:nth-child(n),
            .dark .projectActivity-table td:nth-child(n) {
                border-right-color: #4b5563;
            }

            /* Backgrounds for fixed cols on special rows */
            .projectActivity-total-row td:nth-child(-n+8),
            .projectActivity-row.bg-gray-50 td:nth-child(-n+8) {
                background-color: inherit;
            }

            .dark .projectActivity-total-row td:nth-child(-n+8),
            .dark .projectActivity-row.bg-gray-700\/50 td:nth-child(-n+8) {
                background-color: inherit;
            }

            /* Header sticky top */
            .projectActivity-table thead th {
                position: sticky;
                top: 0;
                z-index: 40;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .dark .projectActivity-table thead th {
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            }

            .projectActivity-table thead th:nth-child(-n+8) {
                background-color: #e5e7eb;
                z-index: 50;
                /* Higher for fixed header overlap */
            }

            .dark .projectActivity-table thead th:nth-child(-n+8) {
                background-color: #374151;
            }

            .projectActivity-table tbody tr {
                max-height: 3rem;
            }

            .projectActivity-table td,
            .projectActivity-table th {
                vertical-align: middle;
            }

            /* Quarter tab active state */
            .quarter-tab-active {
                background-color: #3b82f6;
                color: white;
            }
        </style>
    @endpush
</x-layouts.app>
