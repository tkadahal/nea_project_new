<x-layouts.app>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-2">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Expenses for {{ $project->title }} - {{ $fiscalYear->title }}
            </h1>
            <a href="{{ route('admin.projectExpense.index') }}"
                class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                Back to Overview
            </a>
        </div>
        <p class="text-gray-600 dark:text-gray-400">
            Detailed breakdown of Expenses for Fiscal Year {{ $fiscalYear->title }}.
        </p>
        <div class="mt-2 flex items-center justify-between">
            <div class="flex flex-wrap gap-4 text-sm items-center">
                <div
                    class="bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 px-4 py-2 rounded-lg border border-green-200 dark:border-green-700">
                    Total Expense: {{ number_format($totalExpense, 2) }}
                </div>
                <div
                    class="bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 px-4 py-2 rounded-lg border border-blue-200 dark:border-blue-700">
                    Capital Expense: {{ number_format($capitalTotal, 2) }}
                </div>
                <div
                    class="bg-indigo-100 dark:bg-indigo-900/20 text-indigo-800 dark:text-indigo-200 px-4 py-2 rounded-lg border border-indigo-200 dark:border-indigo-700">
                    Recurrent Expense: {{ number_format($recurrentTotal, 2) }}
                </div>
            </div>
            <div class="flex gap-2 ml-4 items-center">
                <select id="quarter-select"
                    class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="1">Quarter 1</option>
                    <option value="2">Quarter 2</option>
                    <option value="3">Quarter 3</option>
                    <option value="4">Quarter 4</option>
                </select>
                <a id="download-link"
                    href="{{ route('admin.projectExpense.excel.download', [$project->id, $fiscalYear->id]) }}?quarter=1"
                    class="px-3 py-1.5 bg-green-500 text-white text-sm rounded-md hover:bg-green-600 whitespace-nowrap">
                    Download Excel
                </a>
            </div>
        </div>
    </div>

    <div class="space-y-8">
        <!-- Capital Expenses Section -->
        @if ($capitalActivities->isNotEmpty())
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-t-lg border-b border-gray-200 dark:border-gray-600">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        Capital Expenses ({{ number_format($capitalTotal, 2) }})
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                        <thead>
                            <tr class="bg-gray-200 dark:bg-gray-600 sticky top-0 z-10">
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12">
                                    #
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64">
                                    Activity/Program
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q1 Qty
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q1 Amt
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q2 Qty
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q2 Amt
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q3 Qty
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q3 Amt
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q4 Qty
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q4 Amt
                                </th>
                            </tr>
                        </thead>
                        <tbody id="capital-tbody">
                            @foreach ($capitalActivities as $rootIndex => $activity)
                                @php
                                    $rootNumber = $rootIndex + 1;
                                    $amounts = $activityAmounts[$activity->id] ?? [
                                        'q1_qty' => 0,
                                        'q1_amt' => 0,
                                        'q2_qty' => 0,
                                        'q2_amt' => 0,
                                        'q3_qty' => 0,
                                        'q3_amt' => 0,
                                        'q4_qty' => 0,
                                        'q4_amt' => 0,
                                    ];
                                    $bgClass = $groupedActivities->has($activity->id)
                                        ? 'bg-gray-50 dark:bg-gray-700/50'
                                        : '';
                                    $hasChildren = $groupedActivities->has($activity->id);
                                @endphp
                                <tr class="projectExpense-row {{ $bgClass }}" data-depth="0">
                                    <td
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
                                        {{ $rootNumber }}
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1"
                                        style="padding-left: {{ 0 * 20 }}px;">
                                        <span
                                            class="font-bold text-gray-900 dark:text-gray-100">{{ $activity->program ?? 'Untitled' }}</span>
                                    </td>
                                    @foreach ([1, 2, 3, 4] as $q)
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-sm text-gray-700 dark:text-gray-200">
                                            {{ number_format($amounts['q' . $q . '_qty'], 0) }}
                                        </td>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-sm text-gray-700 dark:text-gray-200">
                                            {{ number_format($amounts['q' . $q . '_amt'], 2) }}
                                        </td>
                                    @endforeach
                                </tr>
                                @if ($hasChildren)
                                    @include('admin.projectExpenses.partials.hierarchy-rows', [
                                        'group' => $groupedActivities,
                                        'parentId' => $activity->id,
                                        'depth' => 1,
                                        'numberPrefix' => $rootNumber,
                                        'activityAmounts' => $activityAmounts,
                                        'subtreeAmountTotals' => $subtreeAmountTotals,
                                        'subtreeQuantityTotals' => $subtreeQuantityTotals,
                                    ])
                                    @include('admin.projectExpenses.partials.totals-row', [
                                        'depth' => 0,
                                        'number' => $rootNumber,
                                        'activityId' => $activity->id,
                                        'activityAmounts' => $activityAmounts,
                                        'subtreeAmountTotals' => $subtreeAmountTotals,
                                        'subtreeQuantityTotals' => $subtreeQuantityTotals,
                                    ])
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center text-gray-500 dark:text-gray-400">
                No capital expenses found.
            </div>
        @endif

        <!-- Recurrent Expenses Section -->
        @if ($recurrentActivities->isNotEmpty())
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-t-lg border-b border-gray-200 dark:border-gray-600">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        Recurrent Expenses ({{ number_format($recurrentTotal, 2) }})
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                        <thead>
                            <tr class="bg-gray-200 dark:bg-gray-600 sticky top-0 z-10">
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12">
                                    #
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64">
                                    Activity/Program
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q1 Qty
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q1 Amt
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q2 Qty
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q2 Amt
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q3 Qty
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q3 Amt
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q4 Qty
                                </th>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center">
                                    Q4 Amt
                                </th>
                            </tr>
                        </thead>
                        <tbody id="recurrent-tbody">
                            @foreach ($recurrentActivities as $rootIndex => $activity)
                                @php
                                    $rootNumber = $rootIndex + 1;
                                    $amounts = $activityAmounts[$activity->id] ?? [
                                        'q1_qty' => 0,
                                        'q1_amt' => 0,
                                        'q2_qty' => 0,
                                        'q2_amt' => 0,
                                        'q3_qty' => 0,
                                        'q3_amt' => 0,
                                        'q4_qty' => 0,
                                        'q4_amt' => 0,
                                    ];
                                    $bgClass = $groupedActivities->has($activity->id)
                                        ? 'bg-gray-50 dark:bg-gray-700/50'
                                        : '';
                                    $hasChildren = $groupedActivities->has($activity->id);
                                @endphp
                                <tr class="projectExpense-row {{ $bgClass }}" data-depth="0">
                                    <td
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
                                        {{ $rootNumber }}
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1"
                                        style="padding-left: {{ 0 * 20 }}px;">
                                        <span
                                            class="font-bold text-gray-900 dark:text-gray-100">{{ $activity->program ?? 'Untitled' }}</span>
                                    </td>
                                    @foreach ([1, 2, 3, 4] as $q)
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-sm text-gray-700 dark:text-gray-200">
                                            {{ number_format($amounts['q' . $q . '_qty'], 0) }}
                                        </td>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-sm text-gray-700 dark:text-gray-200">
                                            {{ number_format($amounts['q' . $q . '_amt'], 2) }}
                                        </td>
                                    @endforeach
                                </tr>
                                @if ($hasChildren)
                                    @include('admin.projectExpenses.partials.hierarchy-rows', [
                                        'group' => $groupedActivities,
                                        'parentId' => $activity->id,
                                        'depth' => 1,
                                        'numberPrefix' => $rootNumber,
                                        'activityAmounts' => $activityAmounts,
                                        'subtreeAmountTotals' => $subtreeAmountTotals,
                                        'subtreeQuantityTotals' => $subtreeQuantityTotals,
                                    ])
                                    @include('admin.projectExpenses.partials.totals-row', [
                                        'depth' => 0,
                                        'number' => $rootNumber,
                                        'activityId' => $activity->id,
                                        'activityAmounts' => $activityAmounts,
                                        'subtreeAmountTotals' => $subtreeAmountTotals,
                                        'subtreeQuantityTotals' => $subtreeQuantityTotals,
                                    ])
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center text-gray-500 dark:text-gray-400">
                No recurrent expenses found.
            </div>
        @endif
    </div>

    <div class="mt-8 flex gap-4">
        {{-- @can('projectExpense_edit')
            <a href="{{ route('admin.projectExpense.edit', $project->id) }}"
                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                Edit Expense
            </a>
        @endcan --}}

        @can('projectExpense_delete')
            <form action="{{ route('admin.projectExpense.destroy', $project->id) }}" method="POST"
                onsubmit="return confirm('Are you sure you want to delete this expense?')" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 border-0 cursor-pointer">
                    Delete Expense
                </button>
            </form>
        @endcan
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quarterSelect = document.getElementById('quarter-select');
            const downloadLink = document.getElementById('download-link');
            const baseUrl = '{{ route('admin.projectExpense.excel.download', [$project->id, $fiscalYear->id]) }}';

            quarterSelect.addEventListener('change', function() {
                const quarter = this.value;
                downloadLink.href = baseUrl + '?quarter=' + quarter;
            });
        });
    </script>
</x-layouts.app>
