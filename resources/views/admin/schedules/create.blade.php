<x-layouts.app>

    <div class="mb-6">
        <h1 class="text-xl font-semibold">Create Schedule</h1>
    </div>

    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow border">

        <form method="POST" action="{{ route('admin.schedules.store', $plan) }}">
            <input type="hidden" name="project_activity_plan_id" value="{{ $plan->id }}">
            @include('admin.schedules._form')
        </form>

    </div>

</x-layouts.app>
