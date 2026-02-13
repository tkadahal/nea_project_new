<x-layouts.app>

    <div class="mb-6">
        <h1 class="text-xl font-semibold">Schedule Details</h1>
    </div>

    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow border">

        <div class="grid grid-cols-2 gap-4 text-sm">

            <div><strong>Title:</strong> {{ $schedule->title }}</div>
            <div><strong>Type:</strong> {{ ucfirst($schedule->type) }}</div>

            <div><strong>Start:</strong> {{ $schedule->start_date }}</div>
            <div><strong>End:</strong> {{ $schedule->end_date ?? '-' }}</div>

            <div><strong>Budget:</strong> {{ number_format($schedule->planned_budget, 2) }}</div>
            <div><strong>Progress:</strong> {{ $schedule->progress_percent }}%</div>

            <div class="col-span-2">
                <strong>Remarks:</strong> {{ $schedule->remarks }}
            </div>

        </div>

        <div class="mt-6">
            <a href="{{ route('admin.schedules.index', $plan) }}" class="px-4 py-2 bg-gray-500 text-white rounded-md">
                Back
            </a>
        </div>

    </div>

</x-layouts.app>
