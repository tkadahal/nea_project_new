@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <div>
        <label class="block text-sm mb-1">Title</label>
        <input type="text" name="title" value="{{ old('title', $schedule->title ?? '') }}"
            class="w-full p-2 border rounded-md dark:bg-gray-800">
    </div>

    <div>
        <label class="block text-sm mb-1">Type</label>
        <select name="type" class="w-full p-2 border rounded-md dark:bg-gray-800">
            <option value="phase" @selected(old('type', $schedule->type ?? '') == 'phase')>Phase</option>
            <option value="milestone" @selected(old('type', $schedule->type ?? '') == 'milestone')>Milestone</option>
        </select>
    </div>

    <div>
        <label class="block text-sm mb-1">Start Date</label>
        <input type="date" name="start_date"
            value="{{ old('start_date', isset($schedule) ? $schedule->start_date?->format('Y-m-d') : '') }}"
            class="w-full p-2 border rounded-md dark:bg-gray-800">
    </div>

    <div>
        <label class="block text-sm mb-1">End Date</label>
        <input type="date" name="end_date"
            value="{{ old('end_date', isset($schedule) ? $schedule->end_date?->format('Y-m-d') : '') }}"
            class="w-full p-2 border rounded-md dark:bg-gray-800">
    </div>

    <div>
        <label class="block text-sm mb-1">Planned Budget</label>
        <input type="number" step="0.01" name="planned_budget"
            value="{{ old('planned_budget', $schedule->planned_budget ?? 0) }}"
            class="w-full p-2 border rounded-md dark:bg-gray-800">
    </div>

    <div>
        <label class="block text-sm mb-1">Progress (%)</label>
        <input type="number" step="0.01" name="progress_percent"
            value="{{ old('progress_percent', $schedule->progress_percent ?? 0) }}"
            class="w-full p-2 border rounded-md dark:bg-gray-800">
    </div>

    <div>
        <label class="block text-sm mb-1">Sort Order</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $schedule->sort_order ?? 1) }}"
            class="w-full p-2 border rounded-md dark:bg-gray-800">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm mb-1">Remarks</label>
        <textarea name="remarks" class="w-full p-2 border rounded-md dark:bg-gray-800">{{ old('remarks', $schedule->remarks ?? '') }}</textarea>
    </div>

</div>

<div class="mt-6 flex gap-3">
    <button class="px-4 py-2 bg-blue-600 text-white rounded-md">
        Save
    </button>

    <a href="{{ route('admin.schedules.index', $plan) }}" class="px-4 py-2 bg-gray-500 text-white rounded-md">
        Back
    </a>
</div>
