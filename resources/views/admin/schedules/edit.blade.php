<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <div class="mb-6">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div class="flex-1 space-y-2">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                        Edit Schedule: {{ $schedule->code }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Update progress and track date revisions for {{ $schedule->name }}
                    </p>
                </div>

                <div class="shrink-0">
                    <a href="{{ route('admin.contracts.schedules.index', $contract) }}"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Schedules
                    </a>
                </div>
            </div>

            <nav class="flex mt-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('admin.contract.index') }}"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            contracts
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <a href="{{ route('admin.contract.show', $contract) }}"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                                {{ Str::limit($contract->title, 30) }}
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <a href="{{ route('admin.contracts.schedules.index', $contract) }}"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                                Schedules
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Edit
                                {{ $schedule->code }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="flex flex-col md:flex-row gap-6">
            <div class="flex-1">

                <div
                    class="bg-white dark:bg-gray-800 shadow rounded-lg border border-gray-200 dark:border-gray-700 mb-6 overflow-hidden">
                    <div class="px-6 py-4 bg-blue-600 dark:bg-blue-800 border-b border-blue-700">
                        <h2 class="text-lg font-semibold text-white">Edit Schedule Progress & Dates</h2>
                        <p class="text-blue-100 text-sm">{{ $schedule->code }} - {{ $schedule->name }}</p>
                    </div>

                    <div class="p-6">
                        <form action="{{ route('admin.contracts.schedules.update', [$contract, $schedule]) }}"
                            method="POST" class="max-w-full">
                            @csrf
                            @method('PUT')

                            @if ($errors->any())
                                <div
                                    class="mb-6 p-4 bg-red-50 text-red-800 border border-red-300 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800 rounded">
                                    <ul class="list-disc list-inside text-sm">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Section 1: Basic Information (Read Only) -->
                            <div
                                class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                <h3
                                    class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                    Activity Information
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Activity
                                            Code</label>
                                        <input type="text" value="{{ $schedule->code }}" disabled
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hierarchy
                                            Level</label>
                                        <input type="text" value="Level {{ $schedule->level }}" disabled
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 cursor-not-allowed">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Activity
                                            Name</label>
                                        <input type="text" value="{{ $schedule->name }}" disabled
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 cursor-not-allowed">
                                    </div>
                                    @if ($schedule->parent)
                                        <div class="md:col-span-2">
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Parent
                                                Activity</label>
                                            <input type="text"
                                                value="{{ $schedule->parent->code }} - {{ $schedule->parent->name }}"
                                                disabled
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 cursor-not-allowed">
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Section 2: Progress Update -->
                            <div
                                class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                <h3
                                    class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                    Progress
                                </h3>
                                <div>
                                    <label for="progress"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Progress (%) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center mb-4">
                                        <div class="md:col-span-9">
                                            <input type="range" id="progress-slider" name="progress" min="0"
                                                max="100" step="0.5"
                                                value="{{ old('progress', $assignment->pivot->progress) }}"
                                                oninput="document.getElementById('progress-value').value = this.value; updateProgressBar(this.value)"
                                                class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer accent-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                        </div>
                                        <div class="md:col-span-3">
                                            <input type="number" id="progress-value" name="progress" min="0"
                                                max="100" step="0.5"
                                                value="{{ old('progress', $assignment->pivot->progress) }}"
                                                oninput="document.getElementById('progress-slider').value = this.value; updateProgressBar(this.value)"
                                                required
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                        </div>
                                    </div>

                                    <!-- Section 2.5: Quantity Tracking Mode -->
                                    <div
                                        class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                        <h3
                                            class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                            Progress Tracking Mode
                                        </h3>

                                        <!-- Mode Toggle -->
                                        <div class="flex items-center space-x-6 mb-6">
                                            <label class="inline-flex items-center cursor-pointer">
                                                <input type="radio" name="tracking_mode" value="percentage"
                                                    {{ !($assignment->pivot->use_quantity_tracking ?? false) ? 'checked' : '' }}
                                                    onchange="toggleTrackingMode('percentage')"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <span
                                                    class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Percentage
                                                    Mode (Slider)</span>
                                            </label>

                                            <label class="inline-flex items-center cursor-pointer">
                                                <input type="radio" name="tracking_mode" value="quantity"
                                                    {{ $assignment->pivot->use_quantity_tracking ?? false ? 'checked' : '' }}
                                                    onchange="toggleTrackingMode('quantity')"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <span
                                                    class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Quantity
                                                    Mode (Numbers)</span>
                                            </label>
                                        </div>

                                        <!-- Hidden field for backend -->
                                        <input type="hidden" name="use_quantity_tracking" id="use_quantity_tracking"
                                            value="{{ $assignment->pivot->use_quantity_tracking ?? 0 }}">

                                        <!-- Percentage Mode (Existing slider - will be shown/hidden) -->
                                        <div id="percentage-mode"
                                            class="{{ $assignment->pivot->use_quantity_tracking ?? false ? 'hidden' : '' }}">
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                                <i class="fas fa-info-circle"></i> Use the slider and number input
                                                above to set progress.
                                            </p>
                                        </div>

                                        <!-- Quantity Mode (New) -->
                                        <div id="quantity-mode"
                                            class="{{ !($assignment->pivot->use_quantity_tracking ?? false) ? 'hidden' : '' }}">
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                                <!-- Completed Quantity (Always Editable) -->
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        Completed Quantity <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="number" name="completed_quantity"
                                                        id="completed-quantity"
                                                        value="{{ old('completed_quantity', $assignment->pivot->completed_quantity ?? 0) }}"
                                                        min="0" step="0.01"
                                                        oninput="calculateQuantityProgress()"
                                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5"
                                                        placeholder="0">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        Update this value as work progresses
                                                    </p>
                                                </div>

                                                <!-- Target Quantity (Lock after first save) -->
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        Target Quantity <span class="text-red-500">*</span>
                                                    </label>
                                                    @php
                                                        $targetExists =
                                                            !is_null($assignment->pivot->target_quantity) &&
                                                            $assignment->pivot->target_quantity > 0;
                                                    @endphp

                                                    <input type="number" name="target_quantity" id="target-quantity"
                                                        value="{{ old('target_quantity', $assignment->pivot->target_quantity ?? 0) }}"
                                                        min="0" step="0.01"
                                                        oninput="calculateQuantityProgress()"
                                                        {{ $targetExists ? 'disabled' : '' }}
                                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5
                          {{ $targetExists
                              ? 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                              : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white' }}"
                                                        placeholder="100">

                                                    @if ($targetExists)
                                                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                                            <i class="fas fa-lock"></i> Target is locked (set once like
                                                            baseline dates)
                                                        </p>
                                                    @else
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                            Set your goal quantity (will be locked after save)
                                                        </p>
                                                    @endif
                                                </div>

                                                <!-- Unit (Always Editable) -->
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        Unit <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="text" name="unit"
                                                        value="{{ old('unit', $assignment->pivot->unit ?? '') }}"
                                                        list="unit-suggestions"
                                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5"
                                                        placeholder="e.g., poles, meters">

                                                    <datalist id="unit-suggestions">
                                                        <option value="poles">
                                                        <option value="towers">
                                                        <option value="meters">
                                                        <option value="kilometers">
                                                        <option value="cubic meters">
                                                        <option value="foundations">
                                                        <option value="spans">
                                                        <option value="panels">
                                                        <option value="transformers">
                                                        <option value="items">
                                                    </datalist>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        e.g., poles, meters, cubic meters
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Progress Display for Quantity Mode -->
                                            <div
                                                class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                                                <div class="flex justify-between items-center mb-2">
                                                    <span
                                                        class="text-sm font-medium text-gray-700 dark:text-gray-300">Calculated
                                                        Progress:</span>
                                                    <span id="quantity-progress-display"
                                                        class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                                        {{ number_format($assignment->pivot->progress ?? 0, 1) }}%
                                                    </span>
                                                </div>
                                                <div class="w-full bg-blue-200 dark:bg-blue-800 rounded-full h-3">
                                                    <div id="quantity-progress-bar"
                                                        class="bg-blue-600 dark:bg-blue-500 h-3 rounded-full transition-all"
                                                        style="width: {{ $assignment->pivot->progress ?? 0 }}%">
                                                    </div>
                                                </div>
                                                <p class="text-xs text-blue-700 dark:text-blue-300 mt-2">
                                                    <i class="fas fa-calculator"></i> Progress = (Completed ÷ Target) ×
                                                    100%
                                                </p>
                                            </div>

                                            <!-- Hidden field for calculated progress -->
                                            <input type="hidden" name="progress_quantity"
                                                id="progress-quantity-hidden"
                                                value="{{ old('progress', $assignment->pivot->progress ?? 0) }}">
                                        </div>
                                    </div>

                                    <!-- Visual Progress Bar -->
                                    <div class="mb-2">
                                        <div
                                            class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                                            <span>Current Status</span>
                                            <span
                                                id="progress-text-display">{{ old('progress', $assignment->pivot->progress) }}%</span>
                                        </div>
                                        <div
                                            class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-4 overflow-hidden">
                                            <div id="progress-bar"
                                                class="h-4 rounded-full text-xs font-medium text-white text-center leading-4 transition-all duration-300 shadow-sm"
                                                style="width: {{ old('progress', $assignment->pivot->progress) }}%">
                                            </div>
                                        </div>
                                    </div>

                                    @error('progress')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Section 3: Planned Dates (Baseline) -->
                            <!-- Section 3: Planned Dates (Baseline) -->
                            <div
                                class="mb-8 p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 border-l-4 border-l-blue-500">
                                <div class="flex items-center mb-4">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                                        </path>
                                    </svg>
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Planned Dates
                                        (Baseline)</h3>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Planned Start Date -->
                                    <div>
                                        <label for="start_date"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Planned
                                            Start Date</label>

                                        <input type="date" id="start_date" name="start_date"
                                            value="{{ old('start_date', $assignment->pivot->start_date) }}"
                                            {{-- DISABLED LOGIC: If date exists, disable input --}}
                                            {{ $assignment->pivot->start_date ? 'disabled' : '' }}
                                            {{-- STYLE LOGIC: If disabled, use gray 'read-only' styles, otherwise normal styles --}}
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5
                                            {{ $assignment->pivot->start_date
                                                ? 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                                : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white' }}">

                                        @error('start_date')
                                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Planned End Date -->
                                    <div>
                                        <label for="end_date"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Planned
                                            End Date</label>

                                        <input type="date" id="end_date" name="end_date"
                                            value="{{ old('end_date', $assignment->pivot->end_date) }}"
                                            {{-- DISABLED LOGIC: If date exists, disable input --}} {{ $assignment->pivot->end_date ? 'disabled' : '' }}
                                            {{-- STYLE LOGIC: If disabled, use gray 'read-only' styles, otherwise normal styles --}}
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5
                                            {{ $assignment->pivot->end_date
                                                ? 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                                : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white' }}">

                                        @error('end_date')
                                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-2"><i
                                        class="fas fa-info-circle"></i> These are your baseline/original planned dates.
                                    Once set, they are locked.
                                </p>
                            </div>

                            <!-- Section 3.5: Add Date Revision Button -->
                            <div
                                class="mb-8 flex flex-col items-center justify-center p-8 bg-white dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-green-500 dark:hover:border-green-500 transition-colors">
                                <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Track Date Changes
                                </h3>
                                <p class="text-gray-500 dark:text-gray-400 text-sm mb-4 text-center">If actual dates
                                    have changed or need to be recorded, click below.</p>

                                <button onclick="openRevisionModal()" type="button"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:focus:ring-offset-gray-800">
                                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add Date Revision
                                </button>
                            </div>

                            <!-- Section 4: Remarks -->
                            <div
                                class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                <h3
                                    class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                    Remarks
                                </h3>
                                <div>
                                    <textarea id="remarks" name="remarks" rows="4" placeholder="Add any notes regarding this activity..."
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">{{ old('remarks', $assignment->pivot->remarks) }}</textarea>
                                    @error('remarks')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div
                                class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <a href="{{ route('admin.contracts.schedules.index', $contract) }}"
                                    class="px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                                    Cancel
                                </a>
                                <button type="submit"
                                    class="px-6 py-2 bg-blue-600 text-white border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Update Schedule
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- Date Revisions Section (The Green Form) -->
                <div
                    class="bg-white dark:bg-gray-800 shadow rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 bg-yellow-50 dark:bg-yellow-900/20 border-b border-yellow-200 dark:border-yellow-800 border-l-4 border-l-yellow-500">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 mr-3" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Actual Date
                                    Revisions</h2>
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">Track actual dates when they
                                    change (extensions, delays, etc.)</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        @php
                            $revisions = \App\Models\ContractScheduleDateRevision::where('contract_id', $contract->id)
                                ->where('schedule_id', $schedule->id)
                                ->orderBy('created_at', 'desc')
                                ->get();
                        @endphp

                        @if ($revisions->isNotEmpty())
                            <div class="mb-8 overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Actual Start</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Actual End</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Reason</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Remarks</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Revised By</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Date Added</th>
                                            <th scope="col" class="relative px-6 py-3"><span
                                                    class="sr-only">Action</span></th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($revisions as $revision)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $revision->actual_start_date ? \Carbon\Carbon::parse($revision->actual_start_date)->format('M d, Y') : '-' }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $revision->actual_end_date ? \Carbon\Carbon::parse($revision->actual_end_date)->format('M d, Y') : '-' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                        {{ $revision->revision_reason }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $revision->remarks ?? '-' }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $revision->revisedBy->name ?? 'N/A' }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $revision->created_at->format('M d, Y H:i') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <form
                                                        action="{{ route('admin.contracts.schedules.delete-date-revision', [$contract, $revision]) }}"
                                                        method="POST" class="inline-block"
                                                        onsubmit="return confirm('Delete this date revision?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                            title="Delete Revision">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                                </path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 p-4 mb-8">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                            No revisions yet
                                        </h3>
                                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                            <p>Add one below when actual dates change.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Revision Modal -->
    <div id="revisionModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeRevisionModal()"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <!-- Modal Panel -->
                <div
                    class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">

                    <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100"
                                    id="modal-title">Add Date Revision</h3>
                                <div class="mt-4">
                                    <form
                                        action="{{ route('admin.contracts.schedules.add-date-revision', [$contract, $schedule]) }}"
                                        method="POST">
                                        @csrf
                                        <div class="space-y-4">
                                            <div>
                                                <!-- Updated Label Style (added mb-1) -->
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Actual
                                                    Start Date</label>
                                                <!-- Updated Input Style (Matching Main Form) -->
                                                <input type="date" name="actual_start_date"
                                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Actual
                                                    End Date</label>
                                                <input type="date" name="actual_end_date"
                                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason
                                                    for Revision <span class="text-red-500">*</span></label>
                                                <input type="text" name="revision_reason" required
                                                    placeholder="e.g. Weather delay"
                                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Remarks</label>
                                                <textarea name="remarks" rows="2"
                                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5"></textarea>
                                            </div>
                                        </div>
                                        <div
                                            class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                            <button type="submit"
                                                class="inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 sm:col-start-2">Save
                                                Revision</button>
                                            <button type="button" onclick="closeRevisionModal()"
                                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0 dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-600 dark:hover:bg-gray-600">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const slider = document.getElementById('progress-slider');
                const quantityRadio = document.querySelector('input[name="tracking_mode"][value="quantity"]');

                if (slider) {
                    updateProgressBar(slider.value);
                }

                if (quantityRadio && quantityRadio.checked) {
                    calculateQuantityProgress();
                }
            });

            function updateProgressBar(value) {
                const progressBar = document.getElementById('progress-bar');
                const textDisplay = document.getElementById('progress-text-display');

                if (!progressBar) return;

                progressBar.style.width = value + '%';
                progressBar.textContent = value + '%';
                if (textDisplay) textDisplay.textContent = value + '%';

                progressBar.classList.remove('bg-gray-400', 'bg-blue-500', 'bg-yellow-500', 'bg-green-500');

                if (value >= 100) {
                    progressBar.classList.add('bg-green-500');
                } else if (value >= 75) {
                    progressBar.classList.add('bg-blue-500');
                } else if (value >= 50) {
                    progressBar.classList.add('bg-yellow-500');
                } else {
                    progressBar.classList.add('bg-gray-400');
                }
            }

            function toggleTrackingMode(mode) {
                const percentageMode = document.getElementById('percentage-mode');
                const quantityMode = document.getElementById('quantity-mode');
                const hiddenField = document.getElementById('use_quantity_tracking');
                const progressSlider = document.getElementById('progress-slider');
                const progressValue = document.getElementById('progress-value');

                if (mode === 'quantity') {
                    percentageMode.classList.add('hidden');
                    quantityMode.classList.remove('hidden');
                    hiddenField.value = '1';

                    progressSlider.disabled = true;
                    progressValue.readOnly = true;
                    progressSlider.classList.add('opacity-50', 'cursor-not-allowed');
                    progressValue.classList.add('opacity-50', 'cursor-not-allowed');

                    calculateQuantityProgress();
                } else {
                    percentageMode.classList.remove('hidden');
                    quantityMode.classList.add('hidden');
                    hiddenField.value = '0';

                    progressSlider.disabled = false;
                    progressValue.readOnly = false;
                    progressSlider.classList.remove('opacity-50', 'cursor-not-allowed');
                    progressValue.classList.remove('opacity-50', 'cursor-not-allowed');

                    updateProgressBar(progressSlider.value);
                }
            }

            function calculateQuantityProgress() {
                const completed = parseFloat(document.getElementById('completed-quantity')?.value) || 0;
                const target = parseFloat(document.getElementById('target-quantity')?.value) || 0;

                let progress = 0;

                if (target > 0) {
                    progress = Math.min(100, Math.max(0, (completed / target) * 100));

                    const progressDisplay = document.getElementById('quantity-progress-display');
                    const progressBar = document.getElementById('quantity-progress-bar');
                    const progressHidden = document.getElementById('progress-quantity-hidden');
                    const mainProgressValue = document.getElementById('progress-value');
                    const mainProgressSlider = document.getElementById('progress-slider');

                    if (progressDisplay) progressDisplay.textContent = progress.toFixed(1) + '%';
                    if (progressBar) progressBar.style.width = progress + '%';
                    if (progressHidden) progressHidden.value = progress;

                    if (mainProgressValue) mainProgressValue.value = progress.toFixed(1);
                    if (mainProgressSlider) {
                        mainProgressSlider.value = progress.toFixed(1);
                        updateProgressBar(progress.toFixed(1));
                    }
                }
            }

            function openRevisionModal() {
                document.getElementById('revisionModal').classList.remove('hidden');
            }

            function closeRevisionModal() {
                document.getElementById('revisionModal').classList.add('hidden');
            }

            document.addEventListener('keydown', function(event) {
                if (event.key === "Escape") {
                    closeRevisionModal();
                }
            });
        </script>
    @endpush
</x-layouts.app>
