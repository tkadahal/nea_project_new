{{-- resources/views/admin/project-activities/partials/activity-table.blade.php --}}
<div class="mb-8">
    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
        <h3
            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
            {{ $header }}
        </h3>

        {{-- Quarter Tabs --}}
        <div class="flex space-x-2 mb-4 border-b border-gray-200 dark:border-gray-600 pb-2">
            @php $currentQuarter = request()->get('quarter', 'q1'); @endphp
            @foreach (['q1' => 'Q1', 'q2' => 'Q2', 'q3' => 'Q3', 'q4' => 'Q4'] as $qKey => $qLabel)
                <a href="{{ request()->fullUrlWithQuery(['quarter' => $qKey]) }}"
                    class="px-4 py-2 text-sm font-medium rounded-t-lg {{ $currentQuarter == $qKey ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-500' }}">
                    {{ $qLabel }}
                </a>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            @if ($activities->isEmpty())
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No activities for this fiscal year.
                    <a href="{{ route('admin.projectActivity.edit', [$projectId ?? '', $fiscalYearId ?? '']) }}"
                        class="text-blue-600 hover:underline dark:text-blue-400">
                        Add plans
                    </a>.
                </div>
            @else
                <table
                    class="min-w-full border-collapse border border-gray-300 dark:border-gray-600 projectActivity-table">
                    <thead>
                        <tr class="bg-gray-200 dark:bg-gray-600">
                            {{-- Fixed 8 columns --}}
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                #
                            </th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ trans('global.projectActivity.fields.program') }}
                            </th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-right">
                                Total Quantity
                            </th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-right">
                                Total Budget
                            </th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-right">
                                Completed Quantity
                            </th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-right">
                                Expenses Till Date
                            </th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-right">
                                Planned Quantity of this F/Y
                            </th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-right">
                                Planned Budget of this F/Y
                            </th>
                            {{-- Scrollable 2 columns for selected quarter --}}
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-right bg-blue-50 dark:bg-blue-900/20">
                                {{ strtoupper($currentQuarter) }} Quantity
                            </th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-right bg-blue-50 dark:bg-blue-900/20">
                                {{ strtoupper($currentQuarter) }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $topLevel = 0; @endphp
                        @foreach ($activities as $topActivity)
                            @php
                                $topLevel++;
                                $topPlan = $topActivity->plans->first(); // Assumes repo loads correct FY plan; add ->firstWhere('fiscal_year_id', $fiscalYearId ?? '') if needed
                                $hasTopChildren = $topActivity->children->isNotEmpty();
                                $topBgClass = $hasTopChildren ? 'bg-gray-50 dark:bg-gray-700/50' : '';

                                // FIXED: Use Definition for fixed fields, Plan for FY-specific
                                $topTotalQuantity = $hasTopChildren ? 0 : $topActivity->total_quantity ?? 0; // From Definition
                                $topTotalBudget = $hasTopChildren ? 0 : $topActivity->total_budget ?? 0; // From Definition
                                $topCompletedQuantity = $hasTopChildren ? 0 : $topPlan?->completed_quantity ?? 0; // From Plan
                                $topTotalExpense = $hasTopChildren ? 0 : $topPlan?->total_expense ?? 0; // From Plan
                                $topPlannedQuantity = $hasTopChildren ? 0 : $topPlan?->planned_quantity ?? 0; // From Plan
                                $topPlanned = $hasTopChildren ? 0 : $topPlan?->planned_budget ?? 0; // From Plan
                                $topQQuantity = $hasTopChildren ? 0 : $topPlan?->{$currentQuarter . '_quantity'} ?? 0; // From Plan
                                $topQAmount = $hasTopChildren ? 0 : $topPlan?->{$currentQuarter . '_amount'} ?? 0; // From Plan
                            @endphp
                            <tr class="projectActivity-row {{ $topBgClass }}" data-depth="0">
                                <td
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
                                    {{ $topLevel }}
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-2 py-1"
                                    style="padding-left: 0px;">
                                    <span
                                        class="font-bold text-gray-900 dark:text-gray-100">{{ $topActivity->program ?? '' }}</span>
                                </td>
                                <td
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
                                    {{ number_format($topTotalQuantity, 0) }}
                                </td>
                                <td
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
                                    {{ number_format($topTotalBudget, 2) }}
                                </td>
                                <td
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
                                    {{ number_format($topCompletedQuantity, 0) }}
                                </td>
                                <td
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
                                    {{ number_format($topTotalExpense, 2) }}
                                </td>
                                <td
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
                                    {{ number_format($topPlannedQuantity, 0) }}
                                </td>
                                <td
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
                                    {{ number_format($topPlanned, 2) }}
                                </td>
                                {{-- Scrollable --}}
                                <td
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200 bg-blue-50 dark:bg-blue-900/20">
                                    {{ number_format($topQQuantity, 0) }}
                                </td>
                                <td
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200 bg-blue-50 dark:bg-blue-900/20">
                                    {{ number_format($topQAmount, 2) }}
                                </td>
                            </tr>
                            @if ($hasTopChildren)
                                @include('admin.project-activities.partials.hierarchy-rows', [
                                    'parentActivity' => $topActivity,
                                    'depth' => 1,
                                    'numberPrefix' => $topLevel,
                                    'currentQuarter' => $currentQuarter,
                                    'fiscalYearId' => $fiscalYearId ?? '', // Pass for plan filtering if needed
                                ])
                                @include('admin.project-activities.partials.totals-row', [
                                    'depth' => 0,
                                    'number' => $topLevel,
                                    'parentActivity' => $topActivity,
                                    'currentQuarter' => $currentQuarter,
                                    'fiscalYearId' => $fiscalYearId ?? '', // Pass for plan filtering
                                ])
                            @endif
                        @endforeach

                        {{-- Grand Total --}}
                        @php
                            $grandQQuantity = $sums[$currentQuarter . '_quantity'] ?? 0;
                            $grandQAmount = $sums[$currentQuarter . '_amount'] ?? 0;
                        @endphp
                        <tr
                            class="projectActivity-total-row bg-yellow-50 dark:bg-yellow-900/30 border-t-2 border-yellow-300 dark:border-yellow-600 font-bold">
                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm"></td>
                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-left text-gray-900 dark:text-gray-100"
                                style="padding-left: 0px;">
                                Total {{ $header }}
                            </td>
                            <td
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-900 dark:text-gray-100">
                            </td>
                            <td
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-900 dark:text-gray-100">
                                {{ number_format($sums['total_budget'] ?? 0, 2) }}
                            </td>
                            <td
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-900 dark:text-gray-100">
                            </td>
                            <td
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-900 dark:text-gray-100">
                                {{ number_format($sums['total_expense'] ?? 0, 2) }}
                            </td>
                            <td
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-900 dark:text-gray-100">
                            </td>
                            <td
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-900 dark:text-gray-100">
                                {{ number_format($sums['planned_budget'] ?? 0, 2) }}
                            </td>
                            <td
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-900 dark:text-gray-100 bg-yellow-50 dark:bg-yellow-900/30">
                                {{ number_format($grandQQuantity, 0) }}
                            </td>
                            <td
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-900 dark:text-gray-100 bg-yellow-50 dark:bg-yellow-900/30">
                                {{ number_format($grandQAmount, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
