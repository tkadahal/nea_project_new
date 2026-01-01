<x-layouts.app>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.projectActivity.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.projectActivity.info.summaryInfo') }}
            </p>
        </div>

        @can('projectActivity_access')
            @if ($canCreate)
                <a href="{{ route('admin.projectActivity.create') }}"
                    class="inline-flex items-center px-5 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700
                       shadow-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                       dark:bg-blue-700 dark:hover:bg-blue-800">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ trans('global.add') }} {{ trans('global.projectActivity.title_singular') }}
                </a>
            @else
                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>
                        @if (!$currentFiscalYear)
                            No active fiscal year at this time.
                        @elseif($hasPlanForCurrentYear)
                            Annual program for <strong>{{ $currentFiscalYear->title }}</strong> already exists.
                        @else
                            Creation temporarily unavailable.
                        @endif
                    </span>
                </div>
            @endif
        @endcan
    </div>

    @if (session('success'))
        <div
            class="mb-6 p-4 bg-green-100 text-green-800 border border-green-300 rounded-lg
                    dark:bg-green-900/30 dark:text-green-200 dark:border-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Status
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Fiscal Year
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Project
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Version
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Total Budget
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Capital
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Recurrent
                        </th>
                        <th
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Status & Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($activities as $activity)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition">
                            <td class="whitespace-nowrap">
                                <a href="{{ route('admin.projectActivity.log', [$activity->project_id, $activity->fiscal_year_id]) }}"
                                    class="inline-flex items-center px-4 py-2 rounded-full text-xs font-semibold cursor-pointer transition hover:opacity-90 hover:shadow-md
                                        border border-transparent hover:border-current">

                                    @php
                                        $statusConfig = [
                                            'draft' => [
                                                'bg' => 'bg-gray-100 dark:bg-gray-700',
                                                'text' => 'text-gray-800 dark:text-gray-200',
                                                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                                                'label' => 'Draft',
                                            ],
                                            'under_review' => [
                                                'bg' => 'bg-yellow-100 dark:bg-yellow-900/30',
                                                'text' => 'text-yellow-800 dark:text-yellow-300',
                                                'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                                                'label' => $activity->reviewed_at ? 'Reviewed' : 'Under Review',
                                            ],
                                            'approved' => [
                                                'bg' => 'bg-green-100 dark:bg-green-900/30',
                                                'text' => 'text-green-800 dark:text-green-300',
                                                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                                                'label' => 'Approved',
                                            ],
                                        ];

                                        $cfg = $statusConfig[$activity->status] ?? $statusConfig['draft'];
                                    @endphp

                                    <span
                                        class="{{ $cfg['bg'] }} {{ $cfg['text'] }} inline-flex items-center px-3 py-1.5 rounded-full">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="{{ $cfg['icon'] }}" />
                                        </svg>
                                        {{ $cfg['label'] }}
                                    </span>
                                </a>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $activity->fiscalYear->title ?? 'N/A' }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $activity->project_title ?? 'N/A' }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                v{{ $activity->current_version ?? '1' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                                {{ number_format($activity->total_budget ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                                {{ number_format($activity->capital_budget ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                                {{ number_format($activity->recurrent_budget ?? 0, 2) }}
                            </td>

                            <td class="px-6 py-4 text-center text-sm font-medium">
                                <div class="flex flex-wrap items-center justify-center gap-2">

                                    <!-- View Button (goes directly to current version) -->
                                    @can('projectActivity_show')
                                        <a href="{{ route('admin.projectActivity.show', [
                                            $activity->project_id,
                                            $activity->fiscal_year_id,
                                            $activity->current_version,
                                        ]) }}"
                                            class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </a>
                                    @endcan

                                    <!-- Edit + Send for Review (Project User - Draft only) -->
                                    @if ($activity->status === 'draft' && auth()->user()->roles->pluck('id')->contains(\App\Models\Role::PROJECT_USER))
                                        @can('projectActivity_edit')
                                            <a href="{{ route('admin.projectActivity.edit', [$activity->project_id, $activity->fiscal_year_id]) }}"
                                                class="px-3 py-1.5 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700">
                                                Edit
                                            </a>
                                        @endcan

                                        <form method="POST"
                                            action="{{ route('admin.projectActivity.sendForReview', [$activity->project_id, $activity->fiscal_year_id]) }}"
                                            class="inline"
                                            onsubmit="return confirm('Send for review? Editing will be locked.')">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1.5 bg-yellow-600 text-white text-xs rounded hover:bg-yellow-700">
                                                Send for Review
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Mark Reviewed -->
                                    @if (
                                        $activity->status === 'under_review' &&
                                            is_null($activity->reviewed_at) &&
                                            auth()->user()->roles->pluck('id')->contains(\App\Models\Role::DIRECTORATE_USER))
                                        <form method="POST"
                                            action="{{ route('admin.projectActivity.review', [$activity->project_id, $activity->fiscal_year_id]) }}"
                                            class="inline" onsubmit="return confirm('Mark as reviewed?')">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1.5 bg-purple-600 text-white text-xs rounded hover:bg-purple-700">
                                                Mark Reviewed
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Approve -->
                                    @if (
                                        $activity->status === 'under_review' &&
                                            $activity->reviewed_at &&
                                            auth()->user()->roles->pluck('id')->intersect([\App\Models\Role::ADMIN, \App\Models\Role::SUPERADMIN])->isNotEmpty())
                                        <form method="POST"
                                            action="{{ route('admin.projectActivity.approve', [$activity->project_id, $activity->fiscal_year_id]) }}"
                                            class="inline"
                                            onsubmit="return confirm('Approve permanently? This cannot be undone.')">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1.5 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                                                Approve
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Reject Button -->
                                    @if (
                                        $activity->status === 'under_review' &&
                                            // Case 1: Not yet reviewed → only Directorate User can reject
                                            ((is_null($activity->reviewed_at) &&
                                                auth()->user()->roles->pluck('id')->contains(\App\Models\Role::DIRECTORATE_USER)) ||
                                                // Case 2: Already reviewed → only Admin/Superadmin can reject
                                                ($activity->reviewed_at &&
                                                    auth()->user()->roles->pluck('id')->intersect([\App\Models\Role::ADMIN, \App\Models\Role::SUPERADMIN])->isNotEmpty())))
                                        <button type="button"
                                            onclick="openRejectModal({{ $activity->project_id }}, {{ $activity->fiscal_year_id }})"
                                            class="px-3 py-1.5 bg-red-600 text-white text-xs rounded hover:bg-red-700">
                                            Reject
                                        </button>
                                    @endif

                                    <!-- Rejection Modal -->
                                    @if (
                                        $activity->status === 'under_review' &&
                                            ((is_null($activity->reviewed_at) &&
                                                auth()->user()->roles->pluck('id')->contains(\App\Models\Role::DIRECTORATE_USER)) ||
                                                ($activity->reviewed_at &&
                                                    auth()->user()->roles->pluck('id')->intersect([\App\Models\Role::ADMIN, \App\Models\Role::SUPERADMIN])->isNotEmpty())))
                                        <div id="reject-modal-{{ $activity->project_id }}-{{ $activity->fiscal_year_id }}"
                                            class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
                                            <div
                                                class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-md w-full p-6">
                                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                                                    Reject Annual Program
                                                </h3>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                                                    This will return the program to <strong>Draft</strong> status.
                                                </p>

                                                <form method="POST"
                                                    action="{{ route('admin.projectActivity.reject', [$activity->project_id, $activity->fiscal_year_id]) }}">
                                                    @csrf
                                                    <div class="mb-6">
                                                        <label
                                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                            Reason for Rejection <span class="text-red-500">*</span>
                                                        </label>
                                                        <textarea name="rejection_reason" rows="4" required placeholder="Provide a clear reason..."
                                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"></textarea>
                                                    </div>

                                                    <div class="flex justify-end gap-3">
                                                        <button type="button"
                                                            onclick="closeRejectModal({{ $activity->project_id }}, {{ $activity->fiscal_year_id }})"
                                                            class="px-5 py-2 text-gray-700 bg-gray-200 dark:bg-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300">
                                                            Cancel
                                                        </button>
                                                        <button type="submit"
                                                            class="px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                                            Confirm Reject
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($activity->status === 'approved' && auth()->user()->roles->pluck('id')->contains(\App\Models\Role::SUPERADMIN))
                                        <form method="POST"
                                            action="{{ route('admin.projectActivity.returnToDraft', [$activity->project_id, $activity->fiscal_year_id]) }}"
                                            class="inline"
                                            onsubmit="return confirm('Return this approved program to draft? The Project User will be able to edit it again.')">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1.5 bg-orange-600 text-white text-xs rounded hover:bg-orange-700 transition">
                                                Return to Draft
                                            </button>
                                        </form>
                                    @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                {{ trans('global.noRecords') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal JavaScript -->
    <script>
        function openRejectModal(projectId, fiscalYearId) {
            document.getElementById(`reject-modal-${projectId}-${fiscalYearId}`).classList.remove('hidden');
        }

        function closeRejectModal(projectId, fiscalYearId) {
            document.getElementById(`reject-modal-${projectId}-${fiscalYearId}`).classList.add('hidden');
            document.querySelector(`#reject-modal-${projectId}-${fiscalYearId} textarea`).value = '';
        }

        // Close on outside click
        document.querySelectorAll('[id^="reject-modal-"]').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    const match = modal.id.match(/reject-modal-(\d+)-(\d+)/);
                    if (match) closeRejectModal(match[1], match[2]);
                }
            });
        });
    </script>

</x-layouts.app>
