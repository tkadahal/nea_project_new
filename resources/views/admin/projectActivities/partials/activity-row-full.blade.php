@php
    $depth = $activity->depth ?? 0;
    $parentId = $activity->parent_id ?? '';
    $sortIndex = $activity->sort_index ?? '';
    $displayIndex = $sortIndex;
    $isPreloaded = $isPreloaded ?? true;

    // Helper for editable numeric fields (supports old() and cleans commas)
    $valueOrEmpty = function ($oldKey, $modelValue, $decimals = 0) {
        $val = old($oldKey, $modelValue ?? 0);

        if (is_string($val)) {
            $val = str_replace(',', '', $val);
        }

        $val = (float) $val;

        return $val > 0.0001 ? number_format($val, $decimals, '.', ',') : '';
    };
@endphp

<tr class="activity-row" data-id="{{ $activity->id }}" data-depth="{{ $depth }}" data-parent-id="{{ $parentId }}"
    data-sort-index="{{ $sortIndex }}" data-is-preloaded="{{ $isPreloaded ? 'true' : 'false' }}">

    <!-- Hidden inputs for hierarchy -->
    <input type="hidden" name="{{ $type }}[{{ $activity->id }}][sort_index]" value="{{ $sortIndex }}">
    <input type="hidden" name="{{ $type }}[{{ $activity->id }}][parent_id]" value="{{ $parentId }}">
    <input type="hidden" name="{{ $type }}[{{ $activity->id }}][depth]" value="{{ $depth }}">

    <!-- Index Column -->
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-30 bg-white dark:bg-gray-800 left-sticky">
        {{ $displayIndex }}
    </td>

    <!-- Program Column - NOW EDITABLE -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 w-80 sticky left-12 z-30 bg-white dark:bg-gray-800 left-sticky"
        style="padding-left: {{ $depth * 20 + 8 }}px;">
        <input name="{{ $type }}[{{ $activity->id }}][program]" type="text"
            value="{{ old($type . '.' . $activity->id . '.program', $activity->program ?? '') }}"
            class="w-full border-0 p-1 program-input tooltip-error focus:ring-2 focus:ring-blue-500 rounded font-medium text-gray-800 dark:text-gray-200"
            placeholder="Program name" data-id="{{ $activity->id }}">
    </td>

    <!-- Total Budget Quantity - NOW EDITABLE -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][total_budget_quantity]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.total_budget_quantity', $activity->total_quantity ?? 0, 0) }}"
            class="w-full border-0 p-1 text-right total-budget-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded font-medium text-gray-800 dark:text-gray-200"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Total Budget - NOW EDITABLE -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
        <input name="{{ $type }}[{{ $activity->id }}][total_budget]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.total_budget', $activity->total_budget ?? 0, 2) }}"
            class="w-full border-0 p-1 text-right total-budget-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded font-medium text-gray-800 dark:text-gray-200"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Total Expense Quantity - Editable -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][total_expense_quantity]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.total_expense_quantity', $activity->total_expense_quantity ?? 0, 0) }}"
            class="w-full border-0 p-1 text-right total-expense-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Total Expense - Editable -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
        <input name="{{ $type }}[{{ $activity->id }}][total_expense]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.total_expense', $activity->total_expense ?? 0, 2) }}"
            class="w-full border-0 p-1 text-right expenses-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Planned Budget Quantity - Editable -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][planned_budget_quantity]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.planned_budget_quantity', $activity->planned_budget_quantity ?? 0, 0) }}"
            class="w-full border-0 p-1 text-right planned-budget-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Planned Budget - Editable -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
        <input name="{{ $type }}[{{ $activity->id }}][planned_budget]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.planned_budget', $activity->planned_budget ?? 0, 2) }}"
            class="w-full border-0 p-1 text-right planned-budget-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Q1 Quantity -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
        <input name="{{ $type }}[{{ $activity->id }}][q1_quantity]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.q1_quantity', $activity->q1_quantity ?? 0, 0) }}"
            class="w-full border-0 p-1 text-right q1-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Q1 Amount -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][q1]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.q1', $activity->q1 ?? 0, 2) }}"
            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Q2 Quantity -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
        <input name="{{ $type }}[{{ $activity->id }}][q2_quantity]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.q2_quantity', $activity->q2_quantity ?? 0, 0) }}"
            class="w-full border-0 p-1 text-right q2-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Q2 Amount -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][q2]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.q2', $activity->q2 ?? 0, 2) }}"
            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Q3 Quantity -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
        <input name="{{ $type }}[{{ $activity->id }}][q3_quantity]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.q3_quantity', $activity->q3_quantity ?? 0, 0) }}"
            class="w-full border-0 p-1 text-right q3-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Q3 Amount -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][q3]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.q3', $activity->q3 ?? 0, 2) }}"
            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Q4 Quantity -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
        <input name="{{ $type }}[{{ $activity->id }}][q4_quantity]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.q4_quantity', $activity->q4_quantity ?? 0, 0) }}"
            class="w-full border-0 p-1 text-right q4-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Q4 Amount -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][q4]" type="text"
            value="{{ $valueOrEmpty($type . '.' . $activity->id . '.q4', $activity->q4 ?? 0, 2) }}"
            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Actions Column -->
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center w-28 sticky right-0 z-30 bg-white dark:bg-gray-800 right-sticky">
        <div class="flex items-center justify-center space-x-2">
            @if ($depth < 2)
                <button type="button"
                    class="add-subrow cursor-pointer text-2xl text-blue-500 hover:text-blue-700 font-bold"
                    data-parent-id="{{ $activity->id }}" title="Add sub-row">
                    +
                </button>
            @endif

            <button type="button" class="delete-row cursor-pointer text-red-500 hover:text-red-700"
                data-id="{{ $activity->id }}" title="Delete row and sub-rows">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    </td>
</tr>
