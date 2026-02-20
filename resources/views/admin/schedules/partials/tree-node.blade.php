{{-- resources/views/admin/schedules/partials/tree-node.blade.php --}}
@php
    $hasChildren = $schedule->children && $schedule->children->isNotEmpty();
    $isLeaf = !$hasChildren;

    // Progress value
    $scheduleProgress = null;
    if ($schedule->projects->isNotEmpty()) {
        $pivot = $schedule->projects->first()->pivot;
        $scheduleProgress = $pivot->progress ?? 0;
    }

    // Color logic to match your screenshot + legend
    $levelClass = match (true) {
        $level === 0 => 'bg-blue-600 hover:bg-blue-700 text-white',
        $hasChildren => 'bg-indigo-600 hover:bg-indigo-700 text-white',
        default => 'bg-green-600 hover:bg-green-700 text-white',
    };

    // Progress bar color (same logic as your edit page & legend)
    $progressColor = match (true) {
        $scheduleProgress >= 100 => 'bg-green-500',
        $scheduleProgress >= 75 => 'bg-blue-500',
        $scheduleProgress >= 50 => 'bg-yellow-500',
        default => 'bg-gray-500',
    };
@endphp

<div class="tree-node level-{{ $level }} rounded-lg border border-gray-700/50 bg-gray-800/90 shadow-md hover:shadow-lg transition-all duration-150 mb-1.5 overflow-hidden backdrop-blur-sm"
    data-schedule-id="{{ $schedule->id }}">

    <div class="node-header flex items-center justify-between px-4 py-3 gap-4">
        <!-- Left: toggle + code badge + name -->
        <div class="flex items-center gap-3 flex-1 min-w-0">
            @if ($hasChildren)
                <button type="button"
                    class="tree-toggle flex items-center justify-center w-8 h-8 rounded-md hover:bg-white/10 transition-colors focus:outline-none focus:ring-2 focus:ring-white/30"
                    data-node-id="{{ $schedule->id }}" aria-expanded="false" aria-label="Toggle children">
                    <svg class="w-5 h-5 text-white/90 transition-transform duration-200" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            @else
                <span class="w-8 inline-block"></span>
            @endif

            <span
                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium {{ $levelClass }} shadow-sm whitespace-nowrap font-mono tracking-tight">
                <svg class="w-4 h-4 mr-1.5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="{{ ($level === 0 ? 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z' : $hasChildren) ? 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-4l-2 2H5a2 2 0 00-2 2z' : 'M9 12h6m-6 4h6m-6-8h6' }}" />
                </svg>
                {{ $schedule->code }}
            </span>

            <span class="text-sm font-medium text-gray-100 truncate">
                {{ $schedule->name }}
            </span>
        </div>

        <!-- Right: weightage + progress + edit -->
        <div class="flex items-center gap-4 shrink-0">
            @if ($schedule->weightage)
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-gray-700/80 text-gray-200 border border-gray-600"
                    title="Weightage">
                    <svg class="w-3.5 h-3.5 mr-1.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ $schedule->weightage }}%
                </span>
            @endif

            @if ($scheduleProgress !== null)
                <div class="w-32 min-w-[8rem] hidden sm:block">
                    <div class="w-full bg-gray-700/60 rounded-full h-2 overflow-hidden">
                        <div class="{{ $progressColor }} h-2 rounded-full transition-all duration-400 ease-out"
                            style="width: {{ $scheduleProgress }}%"></div>
                    </div>
                    <div class="text-right text-xs font-medium mt-1 text-gray-300">
                        {{ number_format($scheduleProgress, 0) }}%
                    </div>
                </div>
            @endif

            @if ($isLeaf)
                <a href="{{ route('admin.projects.schedules.edit', [$project, $schedule]) }}"
                    class="inline-flex items-center px-3.5 py-1.5 text-xs font-medium rounded-md bg-blue-600/90 hover:bg-blue-700 text-white shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-gray-900">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    Edit
                </a>
            @endif
        </div>
    </div>

    @if ($hasChildren)
        <div class="tree-children hidden pl-8 sm:pl-12 border-t border-gray-700/40 bg-gray-900/40"
            id="children-{{ $schedule->id }}">
            @foreach ($schedule->children as $child)
                @include('admin.schedules.partials.tree-node', [
                    'schedule' => $child,
                    'project' => $project,
                    'level' => $level + 1,
                ])
            @endforeach
        </div>
    @endif
</div>
