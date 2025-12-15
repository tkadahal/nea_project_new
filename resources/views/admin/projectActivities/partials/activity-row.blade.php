@php
    $depth = $activity->depth ?? 0;
    $parentId = $activity->parent_id ?? '';
    $sortIndex = $activity->sort_index ?? '';
    $parts = explode('.', $sortIndex);
    $displayIndex = $sortIndex ? end($parts) : '';
@endphp

<tr class="activity-row" data-id="{{ $activity->id }}" data-depth="{{ $depth }}" data-parent-id="{{ $parentId }}"
    data-sort-index="{{ $sortIndex }}">

    <!-- Hidden inputs for submit (hierarchy & order) -->
    <input type="hidden" name="{{ $type }}[{{ $activity->id }}][sort_index]" value="{{ $sortIndex }}">
    <input type="hidden" name="{{ $type }}[{{ $activity->id }}][parent_id]" value="{{ $parentId }}">
    <input type="hidden" name="{{ $type }}[{{ $activity->id }}][depth]" value="{{ $depth }}">

    <!-- Index Column (display last part) -->
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-30 bg-white dark:bg-gray-800 left-sticky">
        {{ $displayIndex }}
    </td>

    <!-- Program Column (with indent) -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 w-80 sticky left-12 z-30 bg-white dark:bg-gray-800 left-sticky"
        style="padding-left: {{ $depth * 20 + 8 }}px;">
        <input name="{{ $type }}[{{ $activity->id }}][program]" type="text"
            value="{{ $activity->program ?? '' }}"
            class="w-full border-0 p-1 program-input tooltip-error focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="Enter program name" data-id="{{ $activity->id }}">
    </td>

    <!-- Total Budget Quantity -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][total_budget_quantity]" type="text" value="0"
            class="w-full border-0 p-1 text-right total-budget-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Total Budget -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
        <input name="{{ $type }}[{{ $activity->id }}][total_budget]" type="text" value="0.00"
            class="w-full border-0 p-1 text-right total-budget-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Total Expense Quantity -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][total_expense_quantity]" type="text" value="0"
            class="w-full border-0 p-1 text-right total-expense-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Total Expense -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
        <input name="{{ $type }}[{{ $activity->id }}][total_expense]" type="text" value="0.00"
            class="w-full border-0 p-1 text-right expenses-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Planned Budget Quantity -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][planned_budget_quantity]" type="text" value="0"
            class="w-full border-0 p-1 text-right planned-budget-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Planned Budget -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
        <input name="{{ $type }}[{{ $activity->id }}][planned_budget]" type="text" value="0.00"
            class="w-full border-0 p-1 text-right planned-budget-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Q1 Quantity -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
        <input name="{{ $type }}[{{ $activity->id }}][q1_quantity]" type="text" value="0"
            class="w-full border-0 p-1 text-right q1-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Q1 Amount -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][q1]" type="text" value="0.00"
            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Q2 Quantity -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
        <input name="{{ $type }}[{{ $activity->id }}][q2_quantity]" type="text" value="0"
            class="w-full border-0 p-1 text-right q2-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Q2 Amount -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][q2]" type="text" value="0.00"
            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Q3 Quantity -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
        <input name="{{ $type }}[{{ $activity->id }}][q3_quantity]" type="text" value="0"
            class="w-full border-0 p-1 text-right q3-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Q3 Amount -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][q3]" type="text" value="0.00"
            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Q4 Quantity -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
        <input name="{{ $type }}[{{ $activity->id }}][q4_quantity]" type="text" value="0"
            class="w-full border-0 p-1 text-right q4-quantity-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0" data-id="{{ $activity->id }}">
    </td>

    <!-- Q4 Amount -->
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][q4]" type="text" value="0.00"
            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input focus:ring-2 focus:ring-blue-500 rounded"
            placeholder="0.00" data-id="{{ $activity->id }}">
    </td>

    <!-- Actions Column -->
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center w-28 sticky right-0 z-30 bg-white dark:bg-gray-800 right-sticky">
        <div class="flex items-center justify-center space-x-2">
            @if ($depth < 2)
                <span class="add-subrow cursor-pointer text-2xl text-blue-500 hover:text-blue-700"
                    data-parent-id="{{ $activity->id }}" title="Add sub-row">
                    +
                </span>
            @endif
            <span class="delete-row cursor-pointer text-2xl text-red-500 hover:text-red-700"
                data-id="{{ $activity->id }}" title="Delete row and sub-rows">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </span>
        </div>
    </td>
</tr>
