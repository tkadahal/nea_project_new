{{-- admin/projectExpenses/partials/totals-row.blade.php --}}
@php
    $subtreeAmounts = $subtreeAmountTotals[$activityId] ?? ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
    $subtreeQuantities = $subtreeQuantityTotals[$activityId] ?? ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
    $subtotal = array_sum($subtreeAmounts);
@endphp
<tr class="projectExpense-row bg-yellow-50 dark:bg-yellow-900/20" data-depth="{{ $depth }}"
    style="font-weight: bold;">
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">

    </td>
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1" style="padding-left: {{ $depth * 20 }}px;">
        <span class="text-gray-800 dark:text-gray-200">Subtotal</span>
    </td>
    @foreach ([1, 2, 3, 4] as $q)
        <td
            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-sm text-gray-700 dark:text-gray-200">
            {{ number_format($subtreeQuantities['q' . $q], 0) }}
        </td>
        <td
            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-sm text-gray-700 dark:text-gray-200">
            {{ number_format($subtreeAmounts['q' . $q], 2) }}
        </td>
    @endforeach
</tr>
