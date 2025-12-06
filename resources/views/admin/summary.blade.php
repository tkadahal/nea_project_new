<x-layouts.app>
    <div class="mb-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-lg sm:text-xl lg:text-2xl xl:text-3xl font-bold text-gray-800 dark:text-gray-200">
            Dashboard
        </h1>
        <p class="text-sm sm:text-base lg:text-lg text-gray-600 dark:text-gray-400 mt-1">
            Summary
        </p>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-4 mb-6 px-4 sm:px-6 lg:px-8">
        <div class="w-full" style="z-index: 1001;">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @if (auth()->user()->hasRole(\App\Models\Role::SUPERADMIN))
                    <div class="w-full">
                        <label for="directorate_id"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('global.directorate.title') }}
                        </label>
                        <select id="directorate_id" name="directorate_id"
                            class="block w-full p-2 text-base border border-gray-300 rounded-lg bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                            <option value="">{{ trans('global.pleaseSelect') }}</option>
                            @foreach ($directorates as $directorate)
                                <option value="{{ $directorate->id }}"
                                    {{ request()->input('directorate_id') == $directorate->id ? 'selected' : '' }}>
                                    {{ $directorate->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="w-full">
                    <label for="project_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ trans('global.project.title') }}
                    </label>
                    <select id="project_id" name="project_id"
                        class="block w-full p-2 text-base border border-gray-300 rounded-lg bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                        <option value="">{{ trans('global.pleaseSelect') }}</option>
                        @foreach ($projectsList as $id => $title)
                            <option value="{{ $id }}"
                                {{ request()->input('project_id') == $id ? 'selected' : '' }}>
                                {{ $title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full">
                    <label for="status_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ trans('global.status.title') }}
                    </label>
                    <select id="status_id" name="status_id"
                        class="block w-full p-2 text-base border border-gray-300 rounded-lg bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                        <option value="">{{ trans('global.pleaseSelect') }}</option>
                        @foreach ($statuses as $id => $title)
                            <option value="{{ $id }}"
                                {{ request()->input('status_id') == $id ? 'selected' : '' }}>
                                {{ $title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full">
                    <label for="priority_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ trans('global.priority.title') }}
                    </label>
                    <select id="priority_id" name="priority_id"
                        class="block w-full p-2 text-base border border-gray-300 rounded-lg bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                        <option value="">{{ trans('global.pleaseSelect') }}</option>
                        @foreach ($priorities as $id => $title)
                            <option value="{{ $id }}"
                                {{ request()->input('priority_id') == $id ? 'selected' : '' }}>
                                {{ $title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                Total Projects
            </h3>
            <p class="text-lg sm:text-xl lg:text-2xl text-gray-600 dark:text-gray-400" id="total-projects">
                {{ $summary['total_projects'] ?? 0 }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                Total Contracts
            </h3>
            <p class="text-lg sm:text-xl lg:text-2xl text-gray-600 dark:text-gray-400" id="total-contracts">
                {{ $summary['total_contracts'] ?? 0 }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                Total Tasks
            </h3>
            <p class="text-lg sm:text-xl lg:text-2xl text-gray-600 dark:text-gray-400" id="total-tasks">
                {{ $summary['total_tasks'] ?? 0 }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                Total Budget
            </h3>
            <p class="text-lg sm:text-xl lg:text-2xl text-gray-600 dark:text-gray-400" id="total-budget">
                ${{ number_format($summary['total_budget'] ?? 0, 2) }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                Avg Physical Progress
            </h3>
            <p class="text-lg sm:text-xl lg:text-2xl text-gray-600 dark:text-gray-400" id="average-physical-progress">
                {{ $summary['average_physical_progress'] ?? 0 }}%
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                Avg Financial Progress
            </h3>
            <p class="text-lg sm:text-xl lg:text-2xl text-gray-600 dark:text-gray-400" id="average-financial-progress">
                {{ $summary['average_financial_progress'] ?? 0 }}%
            </p>
        </div>
    </div>

    {{-- Charts and Project Table Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6 px-4 sm:px-6 lg:px-8">
        <!-- Charts (col-lg-1) -->
        <div class="lg:col-span-1">
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow">
                    <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        Project Status Distribution
                    </h3>
                    <div class="relative w-full max-w-[160px] sm:max-w-[200px] aspect-square mx-auto">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <p class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white"
                                    id="statusPercentage">
                                    {{ round(array_sum($charts['status']['data'] ?? []) ? (($charts['status']['data'][0] ?? 0) / array_sum($charts['status']['data'] ?? [])) * 100 : 0) }}%
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" id="statusMainLabel">
                                    {{ $charts['status']['labels'][0] ?? 'Main Status' }}
                                </p>
                            </div>
                        </div>
                        <canvas id="statusChart" class="w-full h-full"></canvas>
                    </div>
                    <div class="flex flex-wrap justify-around text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-2"
                        id="statusLegend">
                        @foreach ($charts['status']['labels'] ?? [] as $index => $label)
                            <div class="text-center mx-2">
                                <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full inline-block mr-1 sm:mr-2"
                                    style="background-color: {{ $charts['status']['colors'][$index] ?? '#6B7280' }}">
                                </div>
                                <span>{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow">
                    <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        Directorate Status Distribution
                    </h3>
                    <div class="relative w-full max-w-[160px] sm:max-w-[200px] aspect-square mx-auto">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <p class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white"
                                    id="directoratePercentage">
                                    {{ round(array_sum($charts['directorate']['data'] ?? []) ? (($charts['directorate']['data'][0] ?? 0) / array_sum($charts['directorate']['data'] ?? [])) * 100 : 0) }}%
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" id="directorateMainLabel">
                                    {{ $charts['directorate']['labels'][0] ?? 'Main Directorate' }}
                                </p>
                            </div>
                        </div>
                        <canvas id="directorateChart" class="w-full h-full"></canvas>
                    </div>
                    <div class="flex flex-wrap justify-around text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-2"
                        id="directorateLegend">
                        @foreach ($charts['directorate']['labels'] ?? [] as $index => $label)
                            <div class="text-center mx-2">
                                <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full inline-block mr-1 sm:mr-2"
                                    style="background-color: {{ $charts['directorate']['colors'][$index] ?? '#6B7280' }}">
                                </div>
                                <span>{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Table (col-lg-3) -->
        <div class="lg:col-span-3">
            <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                        Projct Details
                    </h3>
                    <a href=""
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-sm sm:text-base">
                        Export to CSV
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ trans('global.project.fields.title') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sm:table-cell">
                                    Entity
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ trans('global.project.fields.status_id') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider md:table-cell">
                                    {{ trans('global.project.fields.priority_id') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider lg:table-cell">
                                    {{ trans('global.project.fields.end_date') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider xl:table-cell">
                                    {{ trans('global.project.fields.project_manager') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider xl:table-cell">
                                    Progress
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider xl:table-cell">
                                    {{ trans('global.project.fields.financial_progress') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider xl:table-cell">
                                    Total Budget
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                            id="projectTableBody">
                            @if (isset($tableData) && is_array($tableData) && count($tableData) > 0)
                                @foreach ($tableData as $row)
                                    <tr>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                            {{ $row['title'] ?? 'N/A' }}
                                        </td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 sm:table-cell">
                                            <span
                                                class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs">
                                                {{ $row['entity'] ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                            <span
                                                class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs"
                                                style="background-color: {{ $row['status']['color'] ?? 'gray' }}">
                                                {{ $row['status']['title'] ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 md:table-cell">
                                            <span
                                                class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs"
                                                style="background-color: {{ $row['priority']['color'] ?? 'gray' }}">
                                                {{ $row['priority']['title'] ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 lg:table-cell">
                                            {{ $row['due_date'] ?? 'N/A' }}
                                        </td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 xl:table-cell">
                                            <div class="flex flex-wrap gap-2">
                                                @if (isset($row['users']) && is_array($row['users']) && count($row['users']) > 0)
                                                    @foreach ($row['users'] as $user)
                                                        <span
                                                            class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs">
                                                            {{ $user['initials'] ?? 'N/A' }}
                                                        </span>
                                                    @endforeach
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400 text-xs">No
                                                        Manager</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 xl:table-cell">
                                            {{ $row['progress'] ?? 0 }}%
                                        </td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 xl:table-cell">
                                            {{ $row['financial_progress'] ?? 0 }}%
                                        </td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 xl:table-cell">
                                            ${{ number_format($row['total_budget'] ?? 0, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9"
                                        class="px-4 sm:px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-300">
                                        No projects available
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    <div class="mt-4" id="pagination">
                        {{ $projects->links() }}
                    </div>
                </div>
            </div>

            <!-- Contracts Table -->
            <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                        Contract Details
                    </h3>
                    <a href=""
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-sm sm:text-base">
                        Export to CSV
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ trans('global.contract.fields.title') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sm:table-cell">
                                    Project
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ trans('global.contract.fields.status_id') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider md:table-cell">
                                    {{ trans('global.contract.fields.priority_id') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider lg:table-cell">
                                    Completion Date
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider xl:table-cell">
                                    {{ trans('global.contract.fields.contract_amount') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                            id="contractTableBody">
                            @foreach ($contracts as $contract)
                                <tr>
                                    <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                        {{ $contract->title ?? 'N/A' }}
                                    </td>
                                    <td
                                        class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 sm:table-cell">
                                        <span
                                            class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs">
                                            {{ $contract->project?->title ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                        <span
                                            class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs"
                                            style="background-color: {{ $contract->status?->color ?? 'gray' }}">
                                            {{ $contract->status?->title ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 md:table-cell">
                                        <span
                                            class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs"
                                            style="background-color: {{ $contract->priority?->color ?? 'gray' }}">
                                            {{ $contract->priority?->title ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 lg:table-cell">
                                        {{ $contract->effective_completion_date?->format('Y-m-d') ?? 'N/A' }}
                                    </td>
                                    <td
                                        class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 xl:table-cell">
                                        ${{ number_format($contract->contract_amount ?? 0, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4" id="contractPagination">
                        {{ $contracts->links() }}
                    </div>
                </div>
            </div>

            <!-- Tasks Table -->
            <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                        Task Details
                    </h3>
                    <a href=""
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-sm sm:text-base">
                        Export to CSV
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ trans('global.task.fields.title') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sm:table-cell">
                                    {{ trans('global.task.fields.entity') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ trans('global.task.fields.status_id') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider md:table-cell">
                                    {{ trans('global.task.fields.priority_id') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider lg:table-cell">
                                    {{ trans('global.task.fields.due_date') }}
                                </th>
                                <th
                                    class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider xl:table-cell">
                                    {{ trans('global.task.fields.user_id') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                            id="taskTableBody">
                            @foreach ($tasks as $task)
                                <tr>
                                    <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                        {{ $task->title ?? 'N/A' }}
                                    </td>
                                    <td
                                        class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 sm:table-cell">
                                        <span
                                            class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs">
                                            {{ $task->directorate?->title ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                        <span
                                            class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs"
                                            style="background-color: {{ $task->status?->color ?? 'gray' }}">
                                            {{ $task->status?->title ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 md:table-cell">
                                        <span
                                            class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs"
                                            style="background-color: {{ $task->priority?->color ?? 'gray' }}">
                                            {{ $task->priority?->title ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 lg:table-cell">
                                        {{ $task->due_date?->format('Y-m-d') ?? 'N/A' }}
                                    </td>
                                    <td
                                        class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 xl:table-cell">
                                        <div class="flex flex-wrap gap-2">
                                            @if ($task->users->isNotEmpty())
                                                @foreach ($task->users as $user)
                                                    <span
                                                        class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs">
                                                        {{ $this->getInitials($user->name) }}
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="text-gray-500 dark:text-gray-400 text-xs">No Users</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4" id="taskPagination">
                        {{ $tasks->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
        <script>
            // Store initial data globally
            try {
                window.initialData = @json($data ?? null);
                console.log('Initial Data:', window.initialData);
            } catch (e) {
                console.error('Error parsing initial data:', e);
                window.initialData = null;
            }

            let statusChartInstance = null;
            let directorateChartInstance = null;

            function updateChartsAndTables(data) {
                try {
                    // Update summary
                    document.getElementById('total-projects').textContent = data?.summary?.total_projects ?? 0;
                    document.getElementById('total-contracts').textContent = data?.summary?.total_contracts ?? 0;
                    document.getElementById('total-tasks').textContent = data?.summary?.total_tasks ?? 0;
                    document.getElementById('total-budget').textContent = '$' + (data?.summary?.total_budget ?? 0).toFixed(2);
                    document.getElementById('average-physical-progress').textContent = (data?.summary
                        ?.average_physical_progress ?? 0) + '%';
                    document.getElementById('average-financial-progress').textContent = (data?.summary
                        ?.average_financial_progress ?? 0) + '%';

                    // Prepare chart data with fallbacks
                    const statusData = Array.isArray(data?.charts?.status?.data) ? data.charts.status.data : [0];
                    const statusLabels = Array.isArray(data?.charts?.status?.labels) ? data.charts.status.labels : ['No Data'];
                    const statusColors = Array.isArray(data?.charts?.status?.colors) ? data.charts.status.colors : ['#6B7280'];
                    const directorateData = Array.isArray(data?.charts?.directorate?.data) ? data.charts.directorate.data : [0];
                    const directorateLabels = Array.isArray(data?.charts?.directorate?.labels) ? data.charts.directorate
                        .labels : ['No Data'];
                    const directorateColors = Array.isArray(data?.charts?.directorate?.colors) ? data.charts.directorate
                        .colors : ['#6B7280'];

                    // Update status chart
                    const statusCtx = document.getElementById('statusChart')?.getContext('2d');
                    if (!statusCtx) {
                        console.error('Status chart canvas not found');
                    } else {
                        if (statusChartInstance) statusChartInstance.destroy();
                        statusChartInstance = new Chart(statusCtx, {
                            type: 'doughnut',
                            data: {
                                labels: statusLabels.length ? statusLabels : ['No Data'],
                                datasets: [{
                                    data: statusData.length ? statusData : [1],
                                    backgroundColor: statusColors.length ? statusColors : ['#6B7280'],
                                    borderWidth: 0,
                                }],
                            },
                            options: {
                                cutout: '70%',
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        enabled: true
                                    }
                                },
                                maintainAspectRatio: true,
                                responsive: true,
                            }
                        });
                    }

                    // Update status legend
                    const statusLegend = document.getElementById('statusLegend');
                    statusLegend.innerHTML = statusLabels.length ? statusLabels.map((label, index) => `
                        <div class="text-center mx-2">
                            <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full inline-block mr-1 sm:mr-2" style="background-color: ${statusColors[index] || '#6B7280'}"></div>
                            <span>${label || 'Unknown'}</span>
                        </div>
                    `).join('') : '<span class="text-gray-500 dark:text-gray-400 text-xs">No status data</span>';

                    // Update status percentage
                    const statusPercentage = document.getElementById('statusPercentage');
                    const statusMainLabel = document.getElementById('statusMainLabel');
                    const statusTotal = statusData.reduce((a, b) => a + b, 0);
                    statusPercentage.textContent = statusTotal ? Math.round((statusData[0] || 0) / statusTotal * 100) + '%' :
                        '0%';
                    statusMainLabel.textContent = statusLabels[0] || 'No Status';

                    // Update directorate chart
                    const directorateCtx = document.getElementById('directorateChart')?.getContext('2d');
                    if (!directorateCtx) {
                        console.error('Directorate chart canvas not found');
                    } else {
                        if (directorateChartInstance) directorateChartInstance.destroy();
                        directorateChartInstance = new Chart(directorateCtx, {
                            type: 'doughnut',
                            data: {
                                labels: directorateLabels.length ? directorateLabels : ['No Data'],
                                datasets: [{
                                    data: directorateData.length ? directorateData : [1],
                                    backgroundColor: directorateColors.length ? directorateColors : ['#6B7280'],
                                    borderWidth: 0,
                                }],
                            },
                            options: {
                                cutout: '70%',
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        enabled: true
                                    }
                                },
                                maintainAspectRatio: true,
                                responsive: true,
                            }
                        });
                    }

                    // Update directorate legend
                    const directorateLegend = document.getElementById('directorateLegend');
                    directorateLegend.innerHTML = directorateLabels.length ? directorateLabels.map((label, index) => `
                        <div class="text-center mx-2">
                            <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full inline-block mr-1 sm:mr-2" style="background-color: ${directorateColors[index] || '#6B7280'}"></div>
                            <span>${label || 'Unknown'}</span>
                        </div>
                    `).join('') : '<span class="text-gray-500 dark:text-gray-400 text-xs">No directorate data</span>';

                    // Update directorate percentage
                    const directoratePercentage = document.getElementById('directoratePercentage');
                    const directorateMainLabel = document.getElementById('directorateMainLabel');
                    const directorateTotal = directorateData.reduce((a, b) => a + b, 0);
                    directoratePercentage.textContent = directorateTotal ? Math.round((directorateData[0] || 0) /
                        directorateTotal * 100) + '%' : '0%';
                    directorateMainLabel.textContent = directorateLabels[0] || 'No Directorate';

                    // Update project table
                    const projectTableBody = document.getElementById('projectTableBody');
                    projectTableBody.innerHTML = (data?.tableData?.length ?? 0) ? data.tableData.map(row => `
                        <tr>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">${row.title || 'N/A'}</td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 sm:table-cell">
                                <span class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs">${row.entity || 'N/A'}</span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs" style="background-color: ${row.status?.color || 'gray'}">${row.status?.title || 'N/A'}</span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 md:table-cell">
                                <span class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs" style="background-color: ${row.priority?.color || 'gray'}">${row.priority?.title || 'N/A'}</span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 lg:table-cell">${row.due_date || 'N/A'}</td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 xl:table-cell">
                                <div class="flex flex-wrap gap-2">
                                    ${row.users?.length ? row.users.map(user => `
                                                                                                                                                <span class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs">${user.initials || 'N/A'}</span>
                                                                                                                                            `).join('') : '<span class="text-gray-500 dark:text-gray-400 text-xs">No Manager</span>'}
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 xl:table-cell">${row.progress || 0}%</td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 xl:table-cell">${row.financial_progress || 0}%</td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 xl:table-cell">$${Number(row.total_budget || 0).toFixed(2)}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td colspan="9" class="px-4 sm:px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-300">
                                No projects available
                            </td>
                        </tr>
                    `;

                    // Update contract table
                    const contractTableBody = document.getElementById('contractTableBody');
                    contractTableBody.innerHTML = (data?.contracts?.data?.length ?? 0) ? data.contracts.data.map(contract => `
                        <tr>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">${contract.title || 'N/A'}</td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 sm:table-cell">
                                <span class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs">${contract.project?.title || 'N/A'}</span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs" style="background-color: ${contract.status?.color || 'gray'}">${contract.status?.title || 'N/A'}</span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 md:table-cell">
                                <span class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs" style="background-color: ${contract.priority?.color || 'gray'}">${contract.priority?.title || 'N/A'}</span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 lg:table-cell">${contract.effective_completion_date || 'N/A'}</td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 xl:table-cell">$${Number(contract.contract_amount || 0).toFixed(2)}</td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td colspan="6" class="px-4 sm:px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-300">
                                No contracts available
                            </td>
                        </tr>
                    `;

                    // Update task table
                    const taskTableBody = document.getElementById('taskTableBody');
                    taskTableBody.innerHTML = (data?.tasks?.data?.length ?? 0) ? data.tasks.data.map(task => `
                        <tr>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">${task.title || 'N/A'}</td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 sm:table-cell">
                                <span class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs">${task.directorate?.title || 'N/A'}</span>
                            </td>
                            <td class="px-4 sm:px-6 annakku4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs" style="background-color: ${task.status?.color || 'gray'}">${task.status?.title || 'N/A'}</span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 md:table-cell">
                                <span class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs" style="background-color: ${task.priority?.color || 'gray'}">${task.priority?.title || 'N/A'}</span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 lg:table-cell">${task.due_date || 'N/A'}</td>
                            <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300 xl:table-cell">
                                <div class="flex flex-wrap gap-2">
                                    ${task.users?.length ? task.users.map(user => `
                                                                                                                                                <span class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs">${user.initials || 'N/A'}</span>
                                                                                                                                            `).join('') : '<span class="text-gray-500 dark:text-gray-400 text-xs">No Users</span>'}
                                </div>
                            </td>
                        </tr>
                    `).join('') : `
                        <tr>
                            <td colspan="6" class="px-4 sm:px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-300">
                                No tasks available
                            </td>
                        </tr>
                    `;

                    // Update pagination
                    document.getElementById('pagination').innerHTML = data?.projects?.links || '';
                    document.getElementById('contractPagination').innerHTML = data?.contracts?.links || '';
                    document.getElementById('taskPagination').innerHTML = data?.tasks?.links || '';
                } catch (error) {
                    console.error('Error in updateChartsAndTables:', error);
                    console.warn('Preserving existing table content due to error');
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js not loaded');
                    document.getElementById('statusLegend').innerHTML =
                        '<span class="text-gray-500 dark:text-gray-400 text-xs">Chart.js failed to load</span>';
                    document.getElementById('directorateLegend').innerHTML =
                        '<span class="text-gray-500 dark:text-gray-400 text-xs">Chart.js failed to load</span>';
                    return;
                }

                if (window.initialData && window.initialData.summary && window.initialData.charts && window.initialData
                    .tableData) {
                    console.log('Initializing with data:', window.initialData);
                    updateChartsAndTables(window.initialData);
                } else {
                    console.error('Initial data invalid or incomplete:', window.initialData);
                }

                document.addEventListener('change', function(event) {
                    const target = event.target;
                    if (target.matches(
                            'select[name="directorate_id"], select[name="project_id"], select[name="status_id"], select[name="priority_id"]'
                        )) {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                            'content');
                        if (!csrfToken) {
                            console.error('CSRF token not found');
                            alert('CSRF token missing. Please refresh the page.');
                            return;
                        }

                        const directorateId = document.querySelector('select[name="directorate_id"]')?.value ||
                            '';
                        const projectId = document.querySelector('select[name="project_id"]')?.value || '';
                        const statusId = document.querySelector('select[name="status_id"]')?.value || '';
                        const priorityId = document.querySelector('select[name="priority_id"]')?.value || '';

                        const url = new URL('{{ route('admin.summary') }}', window.location.origin);
                        if (directorateId) url.searchParams.set('directorate_id', directorateId);
                        if (projectId) url.searchParams.set('project_id', projectId);
                        if (statusId) url.searchParams.set('status_id', statusId);
                        if (priorityId) url.searchParams.set('priority_id', priorityId);

                        console.log('Filter Request URL:', url.toString());
                        window.history.pushState({}, '', url.toString());

                        fetch(url.toString(), {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                            })
                            .then(response => {
                                console.log('Filter Response Status:', response.status);
                                if (!response.ok) {
                                    return response.text().then(text => {
                                        throw new Error(
                                            `HTTP error! Status: ${response.status}, Body: ${text.substring(0, 200)}...`
                                        );
                                    });
                                }
                                return response.json();
                            })
                            .then(data => {
                                console.log('Filter Data Loaded:', data);
                                updateChartsAndTables(data);
                            })
                            .catch(error => {
                                console.error('Filter request failed:', error);
                                alert('Failed to apply filters: ' + error.message);
                                console.warn('Preserving existing table content due to filter error');
                            });
                    }
                }, true);
            });
        </script>
    @endpush
</x-layouts.app>
