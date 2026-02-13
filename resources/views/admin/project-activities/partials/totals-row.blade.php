{{-- resources/views/admin/project-activities/partials/totals-row.blade.php --}}
@php
    // ✅ USE SUBTREE SUMS (Repository already computes these!)
    // These properties are set by computeSubtreeSums() in the repository
    $total_budget = $parentActivity->subtree_total_budget ?? 0;
    $total_quantity = $parentActivity->subtree_total_quantity ?? 0;
    $total_expense = $parentActivity->subtree_total_expense ?? 0;
    $completed_quantity = $parentActivity->subtree_completed_quantity ?? 0;
    $planned = $parentActivity->subtree_planned_budget ?? 0;
    $planned_quantity = $parentActivity->subtree_planned_quantity ?? 0;
    $q_amount = $parentActivity->{'subtree_' . $currentQuarter . '_amount'} ?? 0;
    $q_quantity = $parentActivity->{'subtree_' . $currentQuarter . '_quantity'} ?? 0;
@endphp

<tr class="projectActivity-total-row bg-blue-50 dark:bg-blue-900/30 border-t-2 border-blue-300 dark:border-blue-600"
    data-depth="{{ $depth }}">
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm font-bold text-blue-700 dark:text-blue-300">
    </td>
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 font-bold text-blue-700 dark:text-blue-300"
        style="padding-left: {{ $depth * 20 }}px;">
        Total of {{ $number }}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{ number_format($total_quantity, 0) }}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{ number_format($total_budget, 2) }}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{ number_format($completed_quantity, 0) }}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{ number_format($total_expense, 2) }}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{ number_format($planned_quantity, 0) }}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{ number_format($planned, 2) }}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20">
        {{ number_format($q_quantity, 0) }}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20">
        {{ number_format($q_amount, 2) }}
    </td>
</tr>
