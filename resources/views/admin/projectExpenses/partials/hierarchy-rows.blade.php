{{-- admin/projectExpenses/partials/hierarchy-rows.blade.php --}}
@foreach ($group[$parentId] ?? [] as $childIndex => $activity)
    @php
        $childNumber = $numberPrefix . '.' . ($childIndex + 1);
        $amounts = $activityAmounts[$activity->id] ?? [
            'q1_qty' => 0,
            'q1_amt' => 0,
            'q2_qty' => 0,
            'q2_amt' => 0,
            'q3_qty' => 0,
            'q3_amt' => 0,
            'q4_qty' => 0,
            'q4_amt' => 0,
        ];
        $bgClass = $groupedActivities->has($activity->id) ? 'bg-gray-50 dark:bg-gray-700/50' : '';
        $hasChildren = $groupedActivities->has($activity->id);
    @endphp
    <tr class="projectExpense-row {{ $bgClass }}" data-depth="{{ $depth }}">
        <td
            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
            {{ $childNumber }}
        </td>
        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1" style="padding-left: {{ $depth * 20 }}px;">
            <span class="text-gray-600 dark:text-gray-300">{{ $activity->program ?? 'Untitled' }}</span>
        </td>
        @foreach ([1, 2, 3, 4] as $q)
            <td
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-sm text-gray-700 dark:text-gray-200">
                {{ number_format($amounts['q' . $q . '_qty'], 2) }} {{-- Changed from 0 to 2 --}}
            </td>
            <td
                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right text-sm text-gray-700 dark:text-gray-200">
                {{ number_format($amounts['q' . $q . '_amt'], 2) }}
            </td>
        @endforeach
    </tr>
    @if ($hasChildren)
        @include('admin.projectExpenses.partials.hierarchy-rows', [
            'group' => $group,
            'parentId' => $activity->id,
            'depth' => $depth + 1,
            'numberPrefix' => $childNumber,
            'activityAmounts' => $activityAmounts,
            'subtreeAmountTotals' => $subtreeAmountTotals,
            'subtreeQuantityTotals' => $subtreeQuantityTotals,
        ])
    @endif
@endforeach
