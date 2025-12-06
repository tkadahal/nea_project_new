<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Duplicate Budgets
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Budgets having more than one revision
            </p>
        </div>

        <form action="{{ route('admin.budget.cleanDuplicates') }}" method="POST"
            onsubmit="return confirm('Are you sure you want to clean duplicate revisions?');">
            @csrf
            <button type="submit"
                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700
                focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2
                dark:bg-red-700 dark:hover:bg-red-800 dark:focus:ring-offset-gray-900">
                🧹 Clean Duplicates
            </button>
        </form>
    </div>

    <div class="mb-6">
        @if ($budgets->isEmpty())
            <div class="text-gray-600 dark:text-gray-400 text-center py-10">
                ✅ No duplicate revisions found.
            </div>
        @else
            <div class="overflow-x-auto rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Project
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Revision Count
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Last Updated
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($budgets as $budget)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">
                                    {{ $budget->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">
                                    {{ $budget->project->title ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">
                                    {{ $budget->revisions_count }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">
                                    {{ $budget->updated_at->format('Y-m-d') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
