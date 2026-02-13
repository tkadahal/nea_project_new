@php
    $depth = $activity->depth ?? 0;
    $parentId = $activity->parent_id ?? '';
    $sortIndex = $activity->sort_index ?? '';
    $displayIndex = $sortIndex;

    // Numeric helper (NO commas – CRITICAL)
    $valueOrZero = function ($modelValue, $decimals = 0) {
        return number_format((float) ($modelValue ?? 0), $decimals, '.', '');
    };

    // Error detection helper
    $hasError = function ($field) use ($type, $activity, $errors) {
        return $errors->has("{$type}.{$activity->id}.{$field}") ? 'ring-2 ring-red-500 bg-red-50' : '';
    };
@endphp

<tr class="activity-row" data-id="{{ $activity->id }}" data-depth="{{ $depth }}" data-parent-id="{{ $parentId }}"
    data-sort-index="{{ $sortIndex }}">

    {{-- Hidden Metadata --}}
    <input type="hidden" name="{{ $type }}[{{ $activity->id }}][sort_index]" value="{{ $sortIndex }}">
    <input type="hidden" name="{{ $type }}[{{ $activity->id }}][parent_id]" value="{{ $parentId }}">
    <input type="hidden" name="{{ $type }}[{{ $activity->id }}][depth]" value="{{ $depth }}">

    {{-- Index --}}
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-30 bg-white dark:bg-gray-800 left-sticky">
        {{ $displayIndex }}
    </td>

    {{-- Program --}}
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 w-80 sticky left-12 z-30 bg-white dark:bg-gray-800 left-sticky"
        style="padding-left: {{ $depth * 20 + 8 }}px;">
        <input name="{{ $type }}[{{ $activity->id }}][program]" type="text"
            value="{{ old($type . '.' . $activity->id . '.program', $activity->program ?? '') }}"
            class="w-full border-0 p-1 program-input focus:ring-2 focus:ring-blue-500 rounded font-medium bg-transparent {{ $hasError('program') }}">
    </td>

    {{-- Total Budget Quantity --}}
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24 bg-white dark:bg-gray-800">
        <input name="{{ $type }}[{{ $activity->id }}][total_budget_quantity]" type="text"
            value="{{ old($type . '.' . $activity->id . '.total_budget_quantity', $valueOrZero($activity->total_quantity ?? 0, 0)) }}"
            class="w-full border-0 p-1 text-right numeric-input rounded bg-transparent {{ $hasError('total_budget_quantity') }}">
    </td>

    {{-- Total Budget Amount --}}
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28 bg-white dark:bg-gray-800">
        <input name="{{ $type }}[{{ $activity->id }}][total_budget]" type="text"
            value="{{ old($type . '.' . $activity->id . '.total_budget', $valueOrZero($activity->total_budget ?? 0, 2)) }}"
            class="w-full border-0 p-1 text-right numeric-input rounded bg-transparent {{ $hasError('total_budget') }}">
    </td>

    {{-- Total Expense Quantity --}}
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24 bg-white dark:bg-gray-800">
        <input name="{{ $type }}[{{ $activity->id }}][total_expense_quantity]" type="text"
            value="{{ old($type . '.' . $activity->id . '.total_expense_quantity', $valueOrZero($activity->total_expense_quantity ?? 0, 0)) }}"
            class="w-full border-0 p-1 text-right numeric-input rounded bg-transparent {{ $hasError('total_expense_quantity') }}">
    </td>

    {{-- Total Expense Amount --}}
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28 bg-white dark:bg-gray-800">
        <input name="{{ $type }}[{{ $activity->id }}][total_expense]" type="text"
            value="{{ old($type . '.' . $activity->id . '.total_expense', $valueOrZero($activity->total_expense ?? 0, 2)) }}"
            class="w-full border-0 p-1 text-right numeric-input rounded bg-transparent {{ $hasError('total_expense') }}">
    </td>

    {{-- Planned Budget Quantity --}}
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
        <input name="{{ $type }}[{{ $activity->id }}][planned_budget_quantity]" type="text"
            value="{{ old($type . '.' . $activity->id . '.planned_budget_quantity', $valueOrZero($activity->planned_budget_quantity ?? 0, 0)) }}"
            class="w-full border-0 p-1 text-right numeric-input rounded bg-transparent {{ $hasError('planned_budget_quantity') }}">
    </td>

    {{-- Planned Budget Amount --}}
    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
        <input name="{{ $type }}[{{ $activity->id }}][planned_budget]" type="text"
            value="{{ old($type . '.' . $activity->id . '.planned_budget', $valueOrZero($activity->planned_budget ?? 0, 2)) }}"
            class="w-full border-0 p-1 text-right numeric-input rounded bg-transparent {{ $hasError('planned_budget') }}">
    </td>

    @php
        $quarters = [
            ['q1_quantity', 'w-20'],
            ['q1', 'w-24'],
            ['q2_quantity', 'w-20'],
            ['q2', 'w-24'],
            ['q3_quantity', 'w-20'],
            ['q3', 'w-24'],
            ['q4_quantity', 'w-20'],
            ['q4', 'w-24'],
        ];
    @endphp

    {{-- Quarterly Inputs --}}
    @foreach ($quarters as $q)
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right {{ $q[1] }}">
            <input name="{{ $type }}[{{ $activity->id }}][{{ $q[0] }}]" type="text"
                value="{{ old($type . '.' . $activity->id . '.' . $q[0], $valueOrZero($activity->{$q[0]} ?? 0, 2)) }}"
                class="w-full border-0 p-1 text-right numeric-input rounded bg-transparent {{ $hasError($q[0]) }}">
        </td>
    @endforeach

    {{-- Actions --}}
    <td
        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center w-28 sticky right-0 z-30 bg-white dark:bg-gray-800 right-sticky">
        <div class="flex items-center justify-center space-x-2">
            @if ($depth < 2)
                <button type="button" class="add-subrow text-2xl text-blue-500 hover:text-blue-700 font-bold"
                    data-parent-id="{{ $activity->id }}">
                    +
                </button>
            @endif

            <button type="button" class="delete-row text-red-500 hover:text-red-700" data-id="{{ $activity->id }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    </td>
</tr>
