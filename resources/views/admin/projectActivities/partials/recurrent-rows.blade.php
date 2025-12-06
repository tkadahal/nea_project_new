@if (!empty($recurrentRows))
    @foreach ($recurrentRows as $rowData)
        <tr class="projectActivity-row" data-depth="{{ $rowData['depth'] }}" data-index="{{ $rowData['index'] }}"
            {{ isset($rowData['parent_index']) ? 'data-parent="' . $rowData['parent_index'] . '"' : '' }}>
            <td
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-30 bg-white dark:bg-gray-800 left-sticky">
                {{ $rowData['number'] }}
            </td>
            <td
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 w-80 sticky left-12 z-30 bg-white dark:bg-gray-800 left-sticky {{ $rowData['depth'] > 0 ? 'pl-' . $rowData['depth'] * 16 : '' }}">
                @if (isset($rowData['parent_index']))
                    <input type="hidden" name="recurrent[{{ $rowData['index'] }}][parent_id]"
                        value="{{ $rowData['parent_index'] }}">
                @endif
                <input name="recurrent[{{ $rowData['index'] }}][program]" type="text"
                    value="{{ old('recurrent.' . $rowData['index'] . '.program', $rowData['program']) }}"
                    class="w-full border-0 p-1 tooltip-error" {{ $rowData['depth'] > 0 ? 'readonly' : '' }} />
            </td>
            {{-- FIXED Total Budget Quantity (pre-filled from definition, readonly) --}}
            <td class="border dark:border-gray-600 px-2 py-1 text-right w-24">
                <input name="recurrent[{{ $rowData['index'] }}][total_budget_quantity]" type="text" min="0"
                    step="1"
                    value="{{ old('recurrent.' . $rowData['index'] . '.total_budget_quantity', number_format($rowData['total_budget_quantity'] ?? 0, 2)) }}"
                    placeholder="0"
                    class="w-full border-0 p-1 text-right total-budget-quantity-input tooltip-error numeric-input"
                    readonly title="Fixed total quantity from activity definition" />
            </td>
            {{-- FIXED Total Budget (pre-filled from definition, readonly) --}}
            <td class="border  dark:border-gray-600 px-2 py-1 text-right w-28">
                <input name="recurrent[{{ $rowData['index'] }}][total_budget]" type="text"
                    pattern="[0-9]+(\.[0-9]{1,2})?"
                    value="{{ old('recurrent.' . $rowData['index'] . '.total_budget', number_format($rowData['total_budget'] ?? 0, 2)) }}"
                    placeholder="0.00"
                    class="w-full border-0 p-1 text-right total-budget-input tooltip-error numeric-input " readonly
                    title="Fixed total budget from activity definition" />
            </td>
            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                <input name="recurrent[{{ $rowData['index'] }}][total_expense_quantity]" type="text"
                    value="{{ old('recurrent.' . $rowData['index'] . '.total_expense_quantity', $rowData['total_expense_quantity'] ?? '') }}"
                    placeholder="0"
                    class="w-full border-0 p-1 text-right total-expense-quantity-input tooltip-error numeric-input" />
            </td>
            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                <input name="recurrent[{{ $rowData['index'] }}][total_expense]" type="text"
                    pattern="[0-9]+(\.[0-9]{1,2})?"
                    value="{{ old('recurrent.' . $rowData['index'] . '.total_expense', $rowData['total_expense'] ?? '') }}"
                    placeholder="0.00"
                    class="w-full border-0 p-1 text-right expenses-input tooltip-error numeric-input" />
            </td>
            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                <input name="recurrent[{{ $rowData['index'] }}][planned_budget_quantity]" type="text"
                    value="{{ old('recurrent.' . $rowData['index'] . '.planned_budget_quantity', $rowData['planned_budget_quantity'] ?? '') }}"
                    placeholder="0"
                    class="w-full border-0 p-1 text-right planned-budget-quantity-input tooltip-error numeric-input" />
            </td>
            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                <input name="recurrent[{{ $rowData['index'] }}][planned_budget]" type="text"
                    pattern="[0-9]+(\.[0-9]{1,2})?"
                    value="{{ old('recurrent.' . $rowData['index'] . '.planned_budget', $rowData['planned_budget'] ?? '') }}"
                    placeholder="0.00"
                    class="w-full border-0 p-1 text-right planned-budget-input tooltip-error numeric-input" />
            </td>
            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                <input name="recurrent[{{ $rowData['index'] }}][q1_quantity]" type="text" min="0"
                    step="1"
                    value="{{ old('recurrent.' . $rowData['index'] . '.q1_quantity', $rowData['q1_quantity'] ?? '') }}"
                    placeholder="0"
                    class="w-full border-0 p-1 text-right q1-quantity-input tooltip-error numeric-input" />
            </td>
            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                <input name="recurrent[{{ $rowData['index'] }}][q1]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                    value="{{ old('recurrent.' . $rowData['index'] . '.q1', $rowData['q1'] ?? '') }}"
                    placeholder="0.00"
                    class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
            </td>
            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                <input name="recurrent[{{ $rowData['index'] }}][q2_quantity]" type="text" min="0"
                    step="1"
                    value="{{ old('recurrent.' . $rowData['index'] . '.q2_quantity', $rowData['q2_quantity'] ?? '') }}"
                    placeholder="0"
                    class="w-full border-0 p-1 text-right q2-quantity-input tooltip-error numeric-input" />
            </td>
            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                <input name="recurrent[{{ $rowData['index'] }}][q2]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                    value="{{ old('recurrent.' . $rowData['index'] . '.q2', $rowData['q2'] ?? '') }}"
                    placeholder="0.00"
                    class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
            </td>
            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                <input name="recurrent[{{ $rowData['index'] }}][q3_quantity]" type="text" min="0"
                    step="1"
                    value="{{ old('recurrent.' . $rowData['index'] . '.q3_quantity', $rowData['q3_quantity'] ?? '') }}"
                    placeholder="0"
                    class="w-full border-0 p-1 text-right q3-quantity-input tooltip-error numeric-input" />
            </td>
            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                <input name="recurrent[{{ $rowData['index'] }}][q3]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                    value="{{ old('recurrent.' . $rowData['index'] . '.q3', $rowData['q3'] ?? '') }}"
                    placeholder="0.00"
                    class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
            </td>
            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                <input name="recurrent[{{ $rowData['index'] }}][q4_quantity]" type="text" min="0"
                    step="1"
                    value="{{ old('recurrent.' . $rowData['index'] . '.q4_quantity', $rowData['q4_quantity'] ?? '') }}"
                    placeholder="0"
                    class="w-full border-0 p-1 text-right q4-quantity-input tooltip-error numeric-input" />
            </td>
            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                <input name="recurrent[{{ $rowData['index'] }}][q4]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                    value="{{ old('recurrent.' . $rowData['index'] . '.q4', $rowData['q4'] ?? '') }}"
                    placeholder="0.00"
                    class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
            </td>
            <td
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center w-28 sticky right-0 z-30 bg-white dark:bg-gray-800 right-sticky">
                <div class="flex space-x-2 justify-center">
                    @if ($rowData['depth'] < 2)
                        <span class="add-sub-row cursor-pointer text-2xl text-blue-500">+</span>
                    @endif
                    @if ($rowData['depth'] > 0 || count($recurrentRows) > 1)
                        <span class="remove-row cursor-pointer text-2xl text-red-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </span>
                    @endif
                </div>
            </td>
        </tr>
    @endforeach
@else
    <tr class="projectActivity-row" data-depth="0" data-index="1">
        <td
            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-30 bg-white dark:bg-gray-800 left-sticky">
            1
        </td>
        <td
            class="border border-gray-300 dark:border-gray-600 px-2 py-1 w-80 sticky left-12 z-30 bg-white dark:bg-gray-800 left-sticky">
            <input name="recurrent[1][program]" type="text" value="{{ old('recurrent.1.program') }}"
                class="w-full border-0 p-1 tooltip-error" />
        </td>
        {{-- FIXED Total Budget Quantity (empty for new row, readonly) --}}
        <td class="border dark:border-gray-600 px-2 py-1 text-right w-24">
            <input name="recurrent[1][total_budget_quantity]" type="text" min="0" step="1"
                value="{{ old('recurrent.1.total_budget_quantity') }}" placeholder="0"
                class="w-full border-0 p-1 text-right total-budget-quantity-input tooltip-error numeric-input"
                readonly />
        </td>
        {{-- FIXED Total Budget (empty for new row, readonly) --}}
        <td class="border dark:border-gray-600 px-2 py-1 text-right w-28">
            <input name="recurrent[1][total_budget]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                value="{{ old('recurrent.1.total_budget') }}" placeholder="0.00"
                class="w-full border-0 p-1 text-right total-budget-input tooltip-error numeric-input" readonly />
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
            <input name="recurrent[1][total_expense_quantity]" type="text"
                value="{{ old('recurrent.1.total_expense_quantity') }}" placeholder="0"
                class="w-full border-0 p-1 text-right total-expense-quantity-input tooltip-error numeric-input" />
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
            <input name="recurrent[1][total_expense]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                value="{{ old('recurrent.1.total_expense') }}" placeholder="0.00"
                class="w-full border-0 p-1 text-right expenses-input tooltip-error numeric-input" />
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
            <input name="recurrent[1][planned_budget_quantity]" type="text"
                value="{{ old('recurrent.1.planned_budget_quantity') }}" placeholder="0"
                class="w-full border-0 p-1 text-right planned-budget-quantity-input tooltip-error numeric-input" />
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
            <input name="recurrent[1][planned_budget]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                value="{{ old('recurrent.1.planned_budget') }}" placeholder="0.00"
                class="w-full border-0 p-1 text-right planned-budget-input tooltip-error numeric-input" />
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
            <input name="recurrent[1][q1_quantity]" type="text" min="0" step="1"
                value="{{ old('recurrent.1.q1_quantity') }}" placeholder="0"
                class="w-full border-0 p-1 text-right q1-quantity-input tooltip-error numeric-input" />
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
            <input name="recurrent[1][q1]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                value="{{ old('recurrent.1.q1') }}" placeholder="0.00"
                class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
            <input name="recurrent[1][q2_quantity]" type="text" min="0" step="1"
                value="{{ old('recurrent.1.q2_quantity') }}" placeholder="0"
                class="w-full border-0 p-1 text-right q2-quantity-input tooltip-error numeric-input" />
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
            <input name="recurrent[1][q2]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                value="{{ old('recurrent.1.q2') }}" placeholder="0.00"
                class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
            <input name="recurrent[1][q3_quantity]" type="text" min="0" step="1"
                value="{{ old('recurrent.1.q3_quantity') }}" placeholder="0"
                class="w-full border-0 p-1 text-right q3-quantity-input tooltip-error numeric-input" />
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
            <input name="recurrent[1][q3]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                value="{{ old('recurrent.1.q3') }}" placeholder="0.00"
                class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
            <input name="recurrent[1][q4_quantity]" type="text" min="0" step="1"
                value="{{ old('recurrent.1.q4_quantity') }}" placeholder="0"
                class="w-full border-0 p-1 text-right q4-quantity-input tooltip-error numeric-input" />
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
            <input name="recurrent[1][q4]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                value="{{ old('recurrent.1.q4') }}" placeholder="0.00"
                class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
        </td>
        <td
            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center w-28 sticky right-0 z-30 bg-white dark:bg-gray-800 right-sticky">
            <span class="add-sub-row cursor-pointer text-2xl text-blue-500">+</span>
        </td>
    </tr>
@endif
