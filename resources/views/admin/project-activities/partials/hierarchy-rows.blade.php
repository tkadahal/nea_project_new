{{-- resources/views/admin/project-activities/partials/hierarchy-rows.blade.php --}}
@foreach ($parentActivity->children as $childIndex => $activity)
    @php
        $childNumber = $childIndex + 1;
        $currentNumber = $numberPrefix . '.' . $childNumber;
        $actPlan = $activity->plans->first();
        $hasChildren = $activity->children->isNotEmpty();
        $bgClass = $hasChildren ? 'bg-gray-50 dark:bg-gray-700/50' : '';
        $fontClass = $depth === 1 ? 'font-medium' : '';
        $totalQuantity = $hasChildren ? 0 : $actPlan?->total_quantity ?? 0;
        $totalBudget = $hasChildren ? 0 : $actPlan?->total_budget ?? 0;
        $completedQuantity = $hasChildren ? 0 : $actPlan?->completed_quantity ?? 0;
        $totalExpense = $hasChildren ? 0 : $actPlan?->total_expense ?? 0;
        $plannedQuantity = $hasChildren ? 0 : $actPlan?->planned_quantity ?? 0;
        $planned = $hasChildren ? 0 : $actPlan?->planned_budget ?? 0;
        $qQuantity = $hasChildren ? 0 : $actPlan?->{$currentQuarter . '_quantity'} ?? 0;
        $qAmount = $hasChildren ? 0 : $actPlan?->{$currentQuarter . '_amount'} ?? 0;
    @endphp
    <tr class="projectActivity-row {{ $bgClass }}" data-depth="{{ $depth }}">
        <td
            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
            {{ $currentNumber }} </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1" style="padding-left: {{ $depth * 20 }}px;">
            <span class="{{ $fontClass }} text-gray-900 dark:text-gray-100">{{ $activity->program }}</span>
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
            {{ number_format($totalQuantity, 0) }} </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
            {{ number_format($totalBudget, 2) }} </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
            {{ number_format($completedQuantity, 0) }} </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
            {{ number_format($totalExpense, 2) }} </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
            {{ number_format($plannedQuantity, 0) }} </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200">
            {{ number_format($planned, 2) }} </td>
        <td
            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200 bg-blue-50 dark:bg-blue-900/20">
            {{ number_format($qQuantity, 0) }} </td>
        <td
            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-gray-700 dark:text-gray-200 bg-blue-50 dark:bg-blue-900/20">
            {{ number_format($qAmount, 2) }} </td>
    </tr>
    @if ($hasChildren)
        @include('admin.project-activities.partials.hierarchy-rows', [
            'parentActivity' => $activity,
            'depth' => $depth + 1,
            'numberPrefix' => $currentNumber,
            'currentQuarter' => $currentQuarter,
        ])
        @include('admin.project-activities.partials.totals-row', [
            'depth' => $depth,
            'number' => $currentNumber,
            'parentActivity' => $activity,
            'currentQuarter' => $currentQuarter,
        ])
    @endif
@endforeach
