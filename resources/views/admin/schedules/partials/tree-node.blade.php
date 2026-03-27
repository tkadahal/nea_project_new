@php
    // ✅ Use children_count for isLeaf check (no query)
    $isLeaf = $schedule->children_count === 0;

    // ✅ Get progress - use aggregated_progress for parents, direct pivot for leaves
    if (isset($schedule->aggregated_progress)) {
        // Pre-calculated aggregated progress from controller (for parents)
        $progress = $schedule->aggregated_progress;
    } else {
        // Direct pivot progress (for leaves)
        $assignment = $schedule->contracts->first();
        $progress = $assignment ? (float) ($assignment->pivot->progress ?? 0) : 0;
    }

    // Determine badge color and label
    if ($level === 0) {
        $badgeClass = 'bg-blue-600 text-white';
        $badgeIcon = 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z';
        $badgeLabel = 'Phase';
    } elseif ($isLeaf) {
        $badgeClass = 'bg-green-600 text-white';
        $badgeIcon = 'M9 12h6m-6 4h6m-6-8h6';
        $badgeLabel = 'Activity';
    } else {
        $badgeClass = 'bg-indigo-500 text-white';
        $badgeIcon = 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-4l-2 2H5a2 2 0 00-2 2z';
        $badgeLabel = 'Group';
    }

    // Progress bar color
    if ($progress >= 100) {
        $progressColor = 'bg-green-500';
    } elseif ($progress >= 75) {
        $progressColor = 'bg-blue-500';
    } elseif ($progress >= 50) {
        $progressColor = 'bg-yellow-500';
    } else {
        $progressColor = 'bg-gray-400 dark:bg-gray-500';
    }
@endphp

<div class="tree-node {{ $level > 0 ? 'ml-6' : '' }}">
    <div
        class="flex items-start gap-3 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md transition-shadow group">

        {{-- Toggle Button (only if has children) --}}
        <div class="flex-shrink-0 w-6">
            @if (!$isLeaf)
                <button
                    class="tree-toggle w-6 h-6 flex items-center justify-center rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                    data-node-id="{{ $schedule->id }}" aria-expanded="false" aria-label="Toggle children">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 transition-transform duration-200" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            @else
                <div class="w-6 h-6 flex items-center justify-center">
                    <div class="w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                </div>
            @endif
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-3 mb-2">
                {{-- Code Badge --}}
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-semibold {{ $badgeClass }} shadow-sm">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $badgeIcon }}" />
                    </svg>
                    {{ $schedule->code }}
                </span>

                {{-- Type Label --}}
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $badgeLabel }}</span>

                {{-- Level Indicator --}}
                @if ($level > 0)
                    <span class="text-xs text-gray-400 dark:text-gray-500">Level {{ $level + 1 }}</span>
                @endif
            </div>

            {{-- Title --}}
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">
                {{ $schedule->name }}
            </h3>

            {{-- Description --}}
            @if ($schedule->description)
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                    {{ $schedule->description }}
                </p>
            @endif

            {{-- Progress Bar --}}
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                        <span>Progress</span>
                        <span
                            class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($progress, 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                        <div class="h-full {{ $progressColor }} transition-all duration-300 rounded-full"
                            style="width: {{ $progress }}%"></div>
                    </div>
                </div>

                {{-- Edit Button (only for leaf nodes) --}}
                @if ($isLeaf)
                    <a href="{{ route('admin.contracts.schedules.edit', [$contract, $schedule]) }}"
                        class="flex-shrink-0 inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-md hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Children (if not leaf) --}}
    @if (!$isLeaf)
        <div class="tree-children mt-2" id="children-{{ $schedule->id }}" style="display: none;">
            {{-- ✅ OPTIMIZED: Use already-loaded children (no queries) --}}
            @if ($schedule->relationLoaded('children'))
                @foreach ($schedule->children as $child)
                    @include('admin.schedules.partials.tree-node', [
                        'schedule' => $child,
                        'contract' => $contract,
                        'level' => $level + 1,
                    ])
                @endforeach
            @endif
        </div>
    @endif
</div>
