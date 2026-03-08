<x-layouts.app>

    <div class="mb-6">
        <h1 class="text-2xl font-bold">
            Pre Budget Detail
        </h1>
        <p class="text-sm text-gray-500">
            {{ $preBudget->project->title }} |
            {{ $preBudget->fiscalYear->title }}
        </p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">

        <table class="min-w-full border border-gray-300 text-sm">
            <thead>
                <tr class="bg-gray-200 dark:bg-gray-600">
                    <th class="border px-3 py-2">Title</th>
                    <th class="border px-3 py-2">Source</th>
                    <th class="border px-3 py-2 text-right">Q1</th>
                    <th class="border px-3 py-2 text-right">Q2</th>
                    <th class="border px-3 py-2 text-right">Q3</th>
                    <th class="border px-3 py-2 text-right">Q4</th>
                    <th class="border px-3 py-2 text-right">Total</th>
                </tr>
            </thead>

            <tbody>

                @foreach ($preBudget->quarterAllocations->groupBy('quarter')->first() as $key => $value)
                @endforeach

                @php
                    $fields = [
                        'internal_budget' => 'ने. वि. प्रा',
                        'government_share' => 'नेपाल सरकार सेयर',
                        'government_loan' => 'नेपाल सरकार ऋण',
                        'foreign_loan_budget' => 'वैदेशिक ऋण',
                        'foreign_subsidy_budget' => 'वैदेशिक अनुदान',
                        'company_budget' => 'अन्य श्रोत',
                    ];
                @endphp

                @foreach ($fields as $column => $label)
                    <tr>
                        <td class="border px-3 py-2">{{ $label }}</td>

                        <td class="border px-3 py-2">
                            @if ($column === 'foreign_loan_budget')
                                {{ $preBudget->foreign_loan_source ?? '-' }}
                            @elseif($column === 'foreign_subsidy_budget')
                                {{ $preBudget->foreign_subsidy_source ?? '-' }}
                            @elseif($column === 'company_budget')
                                {{ $preBudget->company_source ?? '-' }}
                            @else
                                -
                            @endif
                        </td>

                        @php
                            $q1 = $preBudget->quarterAllocations->where('quarter', 1)->first()?->$column ?? 0;
                            $q2 = $preBudget->quarterAllocations->where('quarter', 2)->first()?->$column ?? 0;
                            $q3 = $preBudget->quarterAllocations->where('quarter', 3)->first()?->$column ?? 0;
                            $q4 = $preBudget->quarterAllocations->where('quarter', 4)->first()?->$column ?? 0;
                            $total = $q1 + $q2 + $q3 + $q4;
                        @endphp

                        <td class="border px-3 py-2 text-right">{{ number_format($q1, 2) }}</td>
                        <td class="border px-3 py-2 text-right">{{ number_format($q2, 2) }}</td>
                        <td class="border px-3 py-2 text-right">{{ number_format($q3, 2) }}</td>
                        <td class="border px-3 py-2 text-right">{{ number_format($q4, 2) }}</td>
                        <td class="border px-3 py-2 text-right font-semibold">
                            {{ number_format($total, 2) }}
                        </td>
                    </tr>
                @endforeach

                <tr class="bg-gray-100 font-bold">
                    <td colspan="6" class="border px-3 py-2 text-right">
                        Grand Total
                    </td>
                    <td class="border px-3 py-2 text-right">
                        {{ number_format($preBudget->total_budget, 2) }}
                    </td>
                </tr>

            </tbody>
        </table>

    </div>

</x-layouts.app>
