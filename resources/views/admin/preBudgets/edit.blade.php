<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            Edit Pre Budget Allocation
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            Update the pre budget request. You may change the Fiscal Year if needed.
        </p>
    </div>

    <p class="text-sm text-red-600 dark:text-red-400 mt-2 font-medium">
        Note: Please use full figure for amounts. Do not enter figures in thousands or lakhs.
    </p>

    <form method="POST" action="{{ route('admin.preBudget.update', $preBudget->id) }}">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div
                    class="mb-6 p-4 bg-red-100 text-red-800 border border-red-300 rounded-lg dark:bg-red-900 dark:text-red-200 dark:border-red-700">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

                {{-- Fiscal Year (Selectable) --}}
                <div>
                    <label class="block text-sm font-medium mb-2">Fiscal Year</label>
                    <select name="fiscal_year_id"
                        class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500"
                        required>
                        @foreach ($fiscalYears as $id => $title)
                            <option value="{{ $id }}"
                                {{ old('fiscal_year_id', $preBudget->fiscal_year_id) == $id ? 'selected' : '' }}>
                                {{ $title }}
                            </option>
                        @endforeach
                    </select>
                    @error('fiscal_year_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Project (Read Only) --}}
                <div>
                    <label class="block text-sm font-medium mb-2">Project</label>
                    <div
                        class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400">
                        {{ $preBudget->project->title ?? 'N/A' }}
                    </div>
                </div>
            </div>

            {{-- Budget Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                    <thead>
                        <tr class="bg-gray-200 dark:bg-gray-600">
                            <th class="border px-3 py-2 text-left text-sm">S.N.</th>
                            <th class="border px-3 py-2 text-left text-sm">Title</th>
                            <th class="border px-3 py-2 text-left text-sm">Source</th>
                            <th class="border px-3 py-2 text-right text-sm">Q1</th>
                            <th class="border px-3 py-2 text-right text-sm">Q2</th>
                            <th class="border px-3 py-2 text-right text-sm">Q3</th>
                            <th class="border px-3 py-2 text-right text-sm">Q4</th>
                            <th class="border px-3 py-2 text-right text-sm">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $qAllocs = $preBudget->quarterAllocations->keyBy('quarter');
                        @endphp

                        @foreach ($fields as $key => $label)
                            <tr class="budget-row">
                                <td class="border px-3 py-2 text-center text-sm">{{ $loop->iteration }}</td>
                                <td class="border px-3 py-2 text-sm font-medium">{{ $label }}</td>

                                {{-- Source Column --}}
                                <td class="border px-3 py-2">
                                    @if (in_array($key, ['foreign_loan_budget', 'foreign_subsidy_budget', 'company_budget']))
                                        @php
                                            $sourceField = match ($key) {
                                                'foreign_loan_budget' => 'foreign_loan_source',
                                                'foreign_subsidy_budget' => 'foreign_subsidy_source',
                                                'company_budget' => 'company_source',
                                                default => null,
                                            };
                                        @endphp
                                        <input type="text" name="sources[{{ $key }}]"
                                            value="{{ old("sources.$key") ?? $preBudget->$sourceField }}"
                                            class="w-full text-left bg-transparent border-none focus:ring-1 focus:ring-blue-500">
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Quarter Inputs --}}
                                @for ($q = 1; $q <= 4; $q++)
                                    @php
                                        $alloc = $qAllocs->get($q);
                                        $val = $alloc ? $alloc->$key ?? 0 : 0;
                                        // Display empty string if value is 0
                                        $displayValue = $val > 0 ? $val : '';
                                    @endphp
                                    <td class="border px-3 py-2">
                                        <input type="number"
                                            name="quarters[{{ $q }}][{{ $key }}]"
                                            value="{{ old("quarters.$q.$key", $displayValue) }}" placeholder="0"
                                            min="0" step="0.01"
                                            class="quarter-input w-full text-right bg-transparent border-none focus:ring-1 focus:ring-blue-500"
                                            data-quarter="{{ $q }}">
                                    </td>
                                @endfor

                                {{-- Row Total --}}
                                <td class="border px-3 py-2 text-right font-semibold total-cell"></td>
                            </tr>
                        @endforeach

                        {{-- Grand Total Row --}}
                        <tr class="bg-gray-50 dark:bg-gray-700 font-bold">
                            <td colspan="3" class="border px-3 py-2 text-right">Grand Total</td>
                            @for ($q = 1; $q <= 4; $q++)
                                <td class="border px-3 py-2 text-right" id="grand-q{{ $q }}"></td>
                            @endfor
                            <td class="border px-3 py-2 text-right" id="grand-total"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <a href="{{ route('admin.preBudget.index') }}"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                    Cancel
                </a>
                <x-buttons.primary type="submit">Update Pre-Budget</x-buttons.primary>
            </div>

        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const quarterInputs = document.querySelectorAll('.quarter-input');

                function updateRowTotals() {
                    document.querySelectorAll('.budget-row').forEach(row => {
                        let rowTotal = 0;
                        row.querySelectorAll('.quarter-input').forEach(input => {
                            rowTotal += parseFloat(input.value || 0);
                        });

                        const cell = row.querySelector('.total-cell');
                        cell.textContent = (rowTotal === 0) ? '' : rowTotal.toFixed(2);
                    });
                }

                function updateGrandTotals() {
                    let grandTotal = 0;
                    for (let q = 1; q <= 4; q++) {
                        let quarterSum = 0;
                        document.querySelectorAll(`[data-quarter="${q}"]`).forEach(input => {
                            quarterSum += parseFloat(input.value || 0);
                        });

                        const cell = document.getElementById(`grand-q${q}`);
                        cell.textContent = (quarterSum === 0) ? '' : quarterSum.toFixed(2);

                        grandTotal += quarterSum;
                    }

                    const gCell = document.getElementById('grand-total');
                    gCell.textContent = (grandTotal === 0) ? '' : grandTotal.toFixed(2);
                }

                function recalc() {
                    updateRowTotals();
                    updateGrandTotals();
                }

                quarterInputs.forEach(input => {
                    input.addEventListener('input', recalc);
                });

                // Initialize totals on page load
                recalc();
            });
        </script>
    @endpush

</x-layouts.app>
