<div class="relative py-2">
    @if ($item->children->isNotEmpty())
        <!-- CLICKABLE HEADER (Has Children) -->
        <div onclick="toggleTree({{ $item->id }})"
            class="cursor-pointer flex items-center bg-white dark:bg-gray-700 p-2 rounded shadow-sm border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            <!-- Toggle Icon (+ / -) -->
            <span id="icon-{{ $item->id }}" class="text-blue-600 font-bold mr-2 text-lg select-none">+</span>

            <!-- Item Content -->
            <div class="flex-1 flex justify-between items-center">
                <span class="font-medium text-gray-700 dark:text-gray-200">
                    {{ $item->program }}
                </span>
                <span class="text-sm text-gray-500">
                    {{ number_format($item->total_budget, 2) }}
                </span>
            </div>
        </div>

        <!-- HIDDEN CHILDREN CONTAINER -->
        <div id="children-{{ $item->id }}"
            class="hidden ml-6 pl-4 border-l-2 border-gray-300 dark:border-gray-700 mt-1 space-y-1">
            @foreach ($item->children as $child)
                <!-- Recursive Call -->
                @include('admin.charts.components.tree-item', ['item' => $child])
            @endforeach
        </div>
    @else
        <!-- STATIC ITEM (No Children / Last Level) -->
        <div class="flex items-center bg-gray-50 dark:bg-gray-800/50 p-2 rounded border border-transparent">
            <!-- Spacer to align with text above -->
            <span class="w-4 mr-2"></span>

            <div class="flex-1 flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-300">
                    {{ $item->program }}
                </span>
                <span class="text-sm text-gray-400">
                    {{ number_format($item->total_budget, 2) }}
                </span>
            </div>
        </div>
    @endif

</div>
