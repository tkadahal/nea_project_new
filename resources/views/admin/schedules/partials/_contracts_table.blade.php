<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
                <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    contract</th>
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
            @forelse ($contractProgress as $contract)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $contract['title'] }}</div>
                    </td>
                    @if ($viewLevel === 'admin')
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $contract['directorate'] }}
                        </td>
                    @endif
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mr-2">
                                <div class="h-1.5 rounded-full {{ $contract['progress'] >= 100 ? 'bg-green-500' : 'bg-blue-600' }}"
                                    style="width: {{ $contract['progress'] }}%"></div>
                            </div>
                            <span
                                class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($contract['progress'], 0) }}%</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $contract['completed_schedules'] }}/{{ $contract['total_schedules'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span
                            class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ strtolower($contract['status']) === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                            {{ $contract['status'] }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('admin.contracts.schedules.index', $contract['id']) }}"
                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400">
                            Details
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400 italic">
                        No contracts found matching the current criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
    {{ $contracts->links() }}
</div>
