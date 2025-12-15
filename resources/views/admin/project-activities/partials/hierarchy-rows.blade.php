{{-- resources/views/admin/project-activities/partials/hierarchy-rows.blade.php --}}
@foreach ($parentActivity->children as $childIndex => $activity)
    @php
        $childNumber = $childIndex + 1;
        $currentNumber = $numberPrefix . '.' . $childNumber;
        $actPlan = $activity->plans->first(); // Assumes repo loads correct FY plan; add ->firstWhere('fiscal_year_id', $fiscalYearId ?? '') if multiple plans loaded
        $hasChildren = $activity->children->isNotEmpty();
        $bgClass = $hasChildren ? 'bg-gray-50 dark:bg-gray-700/50' : '';
        $fontClass = $depth === 1 ? 'font-medium' : '';

        // FIXED: Use Definition for fixed fields, Plan for FY-specific (per-row, not subtree here)
        $totalQuantity = $hasChildren ? 0 : $activity->total_quantity ?? 0; // From Definition (leaf only)
        $totalBudget = $hasChildren ? 0 : $activity->total_budget ?? 0; // From Definition
        $completedQuantity = $hasChildren ? 0 : $actPlan?->completed_quantity ?? 0; // From Plan
        $totalExpense = $hasChildren ? 0 : $actPlan?->total_expense ?? 0; // From Plan
        $plannedQuantity = $hasChildren ? 0 : $actPlan?->planned_quantity ?? 0; // From Plan
        $planned = $hasChildren ? 0 : $actPlan?->planned_budget ?? 0; // From Plan
        $qQuantity = $hasChildren ? 0 : $actPlan?->{$currentQuarter . '_quantity'} ?? 0; // From Plan
        $qAmount = $hasChildren ? 0 : $actPlan?->{$currentQuarter . '_amount'} ?? 0; // From Plan

        // OPTIONAL: If you want subtree sums for parent rows here (instead of 0), uncomment:
        // $totalQuantity = $activity->subtree_total_quantity ?? 0;
        // $totalBudget = $activity->subtree_total_budget ?? 0;
        // $completedQuantity = $activity->subtree_completed_quantity ?? 0;
        // ... etc. (from repo pre-compute)

    @endphp
    <tr class="projectActivity-row {{ $bgClass }}" data-depth="{{ $depth }}">
        <td
            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
            {{ $currentNumber }}
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1" style="padding-left: {{ $depth * 20 }}px;">
            <span class="{{ $fontClass }} text-gray-900 dark:text-gray-100">{{ $activity->program ?? '' }}</span>
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
            {{ number_format($totalQuantity, 0) }}
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
            {{ number_format($totalBudget, 2) }}
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
            {{ number_format($completedQuantity, 0) }}
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
            {{ number_format($totalExpense, 2) }}
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
            {{ number_format($plannedQuantity, 0) }}
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
            {{ number_format($planned, 2) }}
        </td>
        <td
            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200 bg-blue-50 dark:bg-blue-900/20">
            {{ number_format($qQuantity, 0) }}
        </td>
        <td
            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200 bg-blue-50 dark:bg-blue-900/20">
            {{ number_format($qAmount, 2) }}
        </td>
    </tr>
    @if ($hasChildren)
        @include('admin.project-activities.partials.hierarchy-rows', [
            'parentActivity' => $activity,
            'depth' => $depth + 1,
            'numberPrefix' => $currentNumber,
            'currentQuarter' => $currentQuarter,
            'fiscalYearId' => $fiscalYearId ?? '', // Pass for plan filtering if needed
        ])
        @include('admin.project-activities.partials.totals-row', [
            'depth' => $depth,
            'number' => $currentNumber,
            'parentActivity' => $activity,
            'currentQuarter' => $currentQuarter,
            'fiscalYearId' => $fiscalYearId ?? '', // Pass for consistency
        ])
    @endif
@endforeach
