{{-- resources/views/admin/project-activities/partials/totals-row.blade.php --}}
@php
    $total_budget = 0;
    $total_expense = 0;
    $planned = 0;
    $total_quantity = 0;
    $completed_quantity = 0;
    $planned_quantity = 0;
    $q_amount = 0;
    $q_quantity = 0;

    foreach ($parentActivity->children as $child) {
        // Existing budget/expense/planned sums (recursive)
        $child_total_budget = $child->children->isNotEmpty()
            ? $child->children->sum(fn($grand) => $grand->plans->first()?->total_budget ?? 0)
            : $child->plans->first()?->total_budget ?? 0;
        $child_total_expense = $child->children->isNotEmpty()
            ? $child->children->sum(fn($grand) => $grand->plans->first()?->total_expense ?? 0)
            : $child->plans->first()?->total_expense ?? 0;
        $child_planned = $child->children->isNotEmpty()
            ? $child->children->sum(fn($grand) => $grand->plans->first()?->planned_budget ?? 0)
            : $child->plans->first()?->planned_budget ?? 0;

        // Quantity sums
        $child_total_quantity = $child->children->isNotEmpty()
            ? $child->children->sum(fn($grand) => $grand->plans->first()?->total_quantity ?? 0)
            : $child->plans->first()?->total_quantity ?? 0;
        $child_completed_quantity = $child->children->isNotEmpty()
            ? $child->children->sum(fn($grand) => $grand->plans->first()?->completed_quantity ?? 0)
            : $child->plans->first()?->completed_quantity ?? 0;
        $child_planned_quantity = $child->children->isNotEmpty()
            ? $child->children->sum(fn($grand) => $grand->plans->first()?->planned_quantity ?? 0)
            : $child->plans->first()?->planned_quantity ?? 0;

        // Quarter sums
        $child_q_amount = $child->children->isNotEmpty()
            ? $child->children->sum(fn($grand) => $grand->plans->first()?->{$currentQuarter . '_amount'} ?? 0)
            : $child->plans->first()?->{$currentQuarter . '_amount'} ?? 0;
        $child_q_quantity = $child->children->isNotEmpty()
            ? $child->children->sum(fn($grand) => $grand->plans->first()?->{$currentQuarter . '_quantity'} ?? 0)
            : $child->plans->first()?->{$currentQuarter . '_quantity'} ?? 0;

        $total_budget += $child_total_budget;
        $total_expense += $child_total_expense;
        $planned += $child_planned;
        $total_quantity += $child_total_quantity;
        $completed_quantity += $child_completed_quantity;
        $planned_quantity += $child_planned_quantity;
        $q_amount += $child_q_amount;
        $q_quantity += $child_q_quantity;
    }

@endphp
<tr class="projectActivity-total-row bg-blue-50 dark:bg-blue-900/30 border-t-2 border-blue-300 dark:border-blue-600"
    data-depth="{{ $depth }}">
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm font-bold text-blue-700 dark:text-blue-300">
    </td>
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 font-bold text-blue-700 dark:text-blue-300"
        style="padding-left: {{ $depth * 20 }}px;"> Total of {{ $number }} </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{ number_format($total_budget, 2) }} </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{ number_format($total_expense, 2) }} </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{ number_format($planned, 2) }} </td>

    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20">
        {{ number_format($q_quantity, 0) }} </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20">
        {{ number_format($q_amount, 2) }} </td>
</tr>
