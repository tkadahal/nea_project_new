<x-layouts.app>

    <!-- Full width container -->
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Breadcrumb & Header (Matched to reference style) -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Edit Schedule: {{ $schedule->code }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Update progress and dates for {{ $schedule->name }}
            </p>
        </div>

        <div class="flex flex-col md:flex-row gap-6">
            <!-- Main Content Area -->
            <div class="flex-1">
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6 p-6">
                    <form action="{{ route('admin.projects.schedules.update', [$project, $schedule]) }}" method="POST"
                        class="max-w-full">
                        @csrf
                        @method('PUT')

                        <!-- Error Display (Using Reference Style) -->
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
                                class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
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
                                class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                Progress Update
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center mb-4">
                                <div class="md:col-span-8">
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Adjust
                                        Progress Slider</label>
                                    <input type="range" id="progress-slider" name="progress" min="0"
                                        max="100" step="0.5"
                                        value="{{ old('progress', $assignment->pivot->progress) }}"
                                        oninput="document.getElementById('progress-value').value = this.value; updateProgressBar(this.value)"
                                        class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer accent-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                </div>
                                <div class="md:col-span-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Exact
                                        Value (%)</label>
                                    <input type="number" id="progress-value" name="progress" min="0"
                                        max="100" step="0.5"
                                        value="{{ old('progress', $assignment->pivot->progress) }}"
                                        oninput="document.getElementById('progress-slider').value = this.value; updateProgressBar(this.value)"
                                        required
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                </div>
                            </div>

                            <!-- Visual Progress Bar -->
                            <div class="mb-2">
                                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                                    <span>Current Status</span>
                                    <span
                                        id="progress-text-display">{{ old('progress', $assignment->pivot->progress) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-4 overflow-hidden">
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

                        <!-- Section 3: Planned Schedule -->
                        <div
                            class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                            <h3
                                class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                Planned Schedule
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="start_date"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Planned
                                        Start Date</label>
                                    <input type="date" id="start_date" name="start_date"
                                        value="{{ old('start_date', $assignment->pivot->start_date) }}"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                    @error('start_date')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="end_date"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Planned
                                        End Date</label>
                                    <input type="date" id="end_date" name="end_date"
                                        value="{{ old('end_date', $assignment->pivot->end_date) }}"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                    @error('end_date')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Actual Execution -->
                        <div
                            class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                            <h3
                                class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                Actual Execution
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="actual_start_date"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Actual
                                        Start Date</label>
                                    <input type="date" id="actual_start_date" name="actual_start_date"
                                        value="{{ old('actual_start_date', $assignment->pivot->actual_start_date) }}"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                    @error('actual_start_date')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="actual_end_date"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Actual
                                        End Date</label>
                                    <input type="date" id="actual_end_date" name="actual_end_date"
                                        value="{{ old('actual_end_date', $assignment->pivot->actual_end_date) }}"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                    @error('actual_end_date')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Section 5: Remarks -->
                        <div
                            class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                            <h3
                                class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
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
                            <a href="{{ route('admin.projects.schedules.index', $project) }}"
                                class="px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-6 py-2 bg-blue-600 text-white border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                Update Schedule
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const slider = document.getElementById('progress-slider');
                if (slider) {
                    updateProgressBar(slider.value);
                }
            });

            function updateProgressBar(value) {
                const progressBar = document.getElementById('progress-bar');
                const textDisplay = document.getElementById('progress-text-display');
                if (!progressBar) return;

                // Update width and text
                progressBar.style.width = value + '%';
                progressBar.textContent = value + '%';
                if (textDisplay) textDisplay.textContent = value + '%';

                // Tailwind color classes
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
        </script>
    @endpush
</x-layouts.app>
