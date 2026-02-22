<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
                <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Project</th>
                @if ($viewLevel === 'admin')
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Directorate</th>
                @endif
                <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Progress</th>
                <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Schedules</th>
                <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Status</th>
                <th
                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($projectProgress as $project)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $project['title'] }}</div>
                    </td>
                    @if ($viewLevel === 'admin')
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $project['directorate'] }}
                        </td>
                    @endif
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mr-2">
                                <div class="h-1.5 rounded-full {{ $project['progress'] >= 100 ? 'bg-green-500' : 'bg-blue-600' }}"
                                    style="width: {{ $project['progress'] }}%"></div>
                            </div>
                            <span
                                class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($project['progress'], 0) }}%</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $project['completed_schedules'] }}/{{ $project['total_schedules'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span
                            class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ strtolower($project['status']) === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                            {{ $project['status'] }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('admin.projects.schedules.index', $project['id']) }}"
                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400">
                            Details
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400 italic">
                        No projects found matching the current criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
    {{ $projects->links() }}
</div>
