@php
    $type = $type ?? 'plans';

    $depth = $activity->depth ?? 0;
    $parentId = $activity->parent_id ?? '';
    $sortIndex = $activity->sort_index ?? '';

    $valueOrZero = function ($value, $decimals = 0) {
        return number_format((float) ($value ?? 0), $decimals, '.', ',');
    };

    $hasError = function ($field) use ($type, $activity, $errors) {
        return $errors->has("{$type}.{$activity->id}.{$field}") ? 'ring-2 ring-red-500 bg-red-50' : '';
    };
@endphp

<tr class="activity-row" data-id="{{ $activity->id }}" data-depth="{{ $depth }}" data-parent-id="{{ $parentId }}"
    data-sort-index="{{ $sortIndex }}">

    {{-- Hidden hierarchy --}}
    <input type="hidden" name="{{ $type }}[{{ $activity->id }}][sort_index]" value="{{ $sortIndex }}">
    <input type="hidden" name="{{ $type }}[{{ $activity->id }}][parent_id]" value="{{ $parentId }}">
    <input type="hidden" name="{{ $type }}[{{ $activity->id }}][depth]" value="{{ $depth }}">

    {{-- Index --}}
    <td class="border px-2 py-1 text-center w-12 sticky left-0 z-30 bg-white dark:bg-gray-800">
        {{ $sortIndex }}
    </td>

    {{-- Program --}}
    <td class="border px-2 py-1 w-80 sticky left-12 z-30 bg-white dark:bg-gray-800"
        style="padding-left: {{ $depth * 20 + 8 }}px;">
        <input name="{{ $type }}[{{ $activity->id }}][program]" type="text"
            value="{{ old($type . '.' . $activity->id . '.program', $activity->program ?? '') }}"
            class="w-full border-0 p-1 program-input rounded font-medium {{ $hasError('program') }}">
    </td>

    {{-- Totals --}}
    <td class="border px-2 py-1 text-right">
        <input name="{{ $type }}[{{ $activity->id }}][total_budget_quantity]"
            value="{{ old($type . '.' . $activity->id . '.total_budget_quantity', $valueOrZero($activity->total_quantity)) }}"
            class="w-full border-0 text-right numeric-input {{ $hasError('total_budget_quantity') }}">
    </td>

    <td class="border px-2 py-1 text-right">
        <input name="{{ $type }}[{{ $activity->id }}][total_budget]"
            value="{{ old($type . '.' . $activity->id . '.total_budget', $valueOrZero($activity->total_budget, 2)) }}"
            class="w-full border-0 text-right numeric-input {{ $hasError('total_budget') }}">
    </td>

    <td class="border px-2 py-1 text-right">
        <input name="{{ $type }}[{{ $activity->id }}][total_expense_quantity]"
            value="{{ old($type . '.' . $activity->id . '.total_expense_quantity', $valueOrZero($activity->previous_completed_quantity)) }}"
            class="w-full border-0 text-right numeric-input {{ $hasError('total_expense_quantity') }}">
    </td>

    <td class="border px-2 py-1 text-right">
        <input name="{{ $type }}[{{ $activity->id }}][total_expense]"
            value="{{ old($type . '.' . $activity->id . '.total_expense', $valueOrZero($activity->previous_total_expense, 2)) }}"
            class="w-full border-0 text-right numeric-input {{ $hasError('total_expense') }}">
    </td>

    {{-- Planning --}}
    <td class="border px-2 py-1 text-right">
        <input name="{{ $type }}[{{ $activity->id }}][planned_budget_quantity]"
            value="{{ old($type . '.' . $activity->id . '.planned_budget_quantity', 0) }}"
            class="w-full border-0 text-right numeric-input {{ $hasError('planned_budget_quantity') }}">
    </td>

    <td class="border px-2 py-1 text-right">
        <input name="{{ $type }}[{{ $activity->id }}][planned_budget]"
            value="{{ old($type . '.' . $activity->id . '.planned_budget', 0) }}"
            class="w-full border-0 text-right numeric-input {{ $hasError('planned_budget') }}">
    </td>

    {{-- Quarters --}}
    @foreach (['q1_quantity', 'q1', 'q2_quantity', 'q2', 'q3_quantity', 'q3', 'q4_quantity', 'q4'] as $q)
        <td class="border px-2 py-1 text-right">
            <input name="{{ $type }}[{{ $activity->id }}][{{ $q }}]"
                value="{{ old($type . '.' . $activity->id . '.' . $q, 0) }}"
                class="w-full border-0 text-right numeric-input {{ $hasError($q) }}">
        </td>
    @endforeach

    {{-- Actions --}}
    <td class="border px-2 py-1 text-center sticky right-0 z-30 bg-white dark:bg-gray-800">
        <div class="flex justify-center space-x-2">
            @if ($depth < 2)
                <button type="button" class="add-subrow text-blue-600 text-xl font-bold"
                    data-parent-id="{{ $activity->id }}">+</button>
            @endif

            <button type="button" class="delete-row text-red-500" data-id="{{ $activity->id }}">
                🗑
            </button>
        </div>
    </td>
</tr>
