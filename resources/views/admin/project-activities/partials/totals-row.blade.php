@php
    // REFINED: Use parent's own values only (not subtree/children)
// This shows totals based solely on the parent row, ignoring descendants
// Quantities remain blank (0 or empty) as per request—no totals for quantities in subtotals

$parentPlan = $parentActivity->plans->first(); // Parent's FY plan

    // Amounts: Parent only
    $total_budget = $parentActivity->total_budget ?? 0; // From def (fixed)
    $total_expense = $parentPlan?->total_expense ?? 0; // From FY plan
    $planned = $parentPlan?->planned_budget ?? 0; // From FY plan
    $q_amount = $parentPlan?->{$currentQuarter . '_amount'} ?? 0; // From FY quarter

    // Quantities: Leave blank as per request
    $total_quantity = ''; // Or 0 if placeholder needed
    $completed_quantity = '';
    $planned_quantity = '';
    $q_quantity = '';
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
        {{-- Blank for quantity --}}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{ number_format($total_budget, 2) }}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{-- Blank for completed qty --}}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{ number_format($total_expense, 2) }}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{-- Blank for planned qty --}}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
        {{ number_format($planned, 2) }}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20">
        {{-- Blank for quarter qty --}}
    </td>
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20">
        {{ number_format($q_amount, 2) }}
    </td>
</tr>
