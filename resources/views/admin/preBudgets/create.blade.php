<x-layouts.app>
    <div class="mb-6">
        {{-- Removed the specific year from the title since it's now selectable below --}}
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            Pre Budget Allocation
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            Select fiscal year and project, then enter pre budget request.
        </p>
    </div>

    <p class="text-sm text-red-600 dark:text-red-400 mt-2 font-medium">
        Note: Please use full figure for amounts. Do not enter figures in thousands or lakhs.
    </p>

    <form method="POST" action="{{ route('admin.preBudget.store') }}">
        @csrf

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

            {{-- Project and Fiscal Year Selection --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

                {{-- Fiscal Year Dropdown --}}
                <div>
                    <label class="block text-sm font-medium mb-2">
                        Select Fiscal Year
                    </label>
                    <select name="fiscal_year_id"
                        class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <option value="">Select Fiscal Year</option>
                        @foreach ($fiscalYears as $id => $title)
                            <option value="{{ $id }}"
                                {{ old('fiscal_year_id', $currentFiscalYear->id ?? null) == $id ? 'selected' : '' }}>
                                {{ $title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Project Dropdown --}}
                <div>
                    <label class="block text-sm font-medium mb-2">
                        Select Project
                    </label>
                    <select id="project_id" name="project_id"
                        class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <option value="">Select a Project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project['id'] }}"
                                {{ old('project_id') == $project['id'] ? 'selected' : '' }}>
                                {{ $project['title'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Budget Table (Keep your existing table code here) --}}
            <div class="overflow-x-auto">
                <!-- ... Table code remains exactly the same ... -->
                <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                    <!-- ... Ensure table is unchanged ... -->
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
                        @foreach ($fields as $key => $label)
                            <tr class="budget-row">
                                <td class="border px-3 py-2 text-center text-sm">{{ $loop->iteration }}</td>
                                <td class="border px-3 py-2 text-sm font-medium">{{ $label }}</td>
                                <td class="border px-3 py-2">
                                    @if (in_array($key, ['foreign_loan_budget', 'foreign_subsidy_budget', 'company_budget']))
                                        <input type="text" name="sources[{{ $key }}]"
                                            value="{{ old("sources.$key") }}"
                                            class="w-full text-left bg-transparent border-none focus:ring-1 focus:ring-blue-500">
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                @for ($q = 1; $q <= 4; $q++)
                                    <td class="border px-3 py-2">
                                        <input type="number"
                                            name="quarters[{{ $q }}][{{ $key }}]"
                                            value="{{ old("quarters.$q.$key") }}" min="0" step="0.01"
                                            placeholder="0.00"
                                            class="quarter-input w-full text-right bg-transparent border-none focus:ring-1 focus:ring-blue-500"
                                            data-quarter="{{ $q }}">
                                    </td>
                                @endfor
                                <td class="border px-3 py-2 text-right font-semibold total-cell">0.00</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 dark:bg-gray-700 font-bold">
                            <td colspan="3" class="border px-3 py-2 text-right">Grand Total</td>
                            @for ($q = 1; $q <= 4; $q++)
                                <td class="border px-3 py-2 text-right" id="grand-q{{ $q }}">0.00</td>
                            @endfor
                            <td class="border px-3 py-2 text-right" id="grand-total">0.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-8">
                <x-buttons.primary type="submit">Save Pre-Budget</x-buttons.primary>
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
                        row.querySelector('.total-cell').textContent = rowTotal.toFixed(2);
                    });
                }

                function updateGrandTotals() {
                    let grandTotal = 0;
                    for (let q = 1; q <= 4; q++) {
                        let quarterSum = 0;
                        document.querySelectorAll(`[data-quarter="${q}"]`).forEach(input => {
                            quarterSum += parseFloat(input.value || 0);
                        });
                        document.getElementById(`grand-q${q}`).textContent = quarterSum.toFixed(2);
                        grandTotal += quarterSum;
                    }
                    document.getElementById('grand-total').textContent = grandTotal.toFixed(2);
                }

                function recalc() {
                    updateRowTotals();
                    updateGrandTotals();
                }

                quarterInputs.forEach(input => {
                    input.addEventListener('input', recalc);
                });

                recalc();
            });
        </script>
    @endpush

</x-layouts.app>
