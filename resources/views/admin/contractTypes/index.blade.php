<x-layouts.app>
    <!-- Header Section -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                contract Types
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.contract_type.title') }}
            </p>
        </div>

        {{-- @can('contractType_create') --}}
        <a href="{{ route('admin.contractType.create') }}"
            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 
                  text-white font-medium rounded-md shadow-sm
                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 
                  dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-offset-gray-900
                  transition-colors duration-150">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ trans('global.add_new') }}
        </a>
        {{-- @endcan --}}
    </div>

    <!-- Table Card -->
    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if ($contractTypes->isEmpty())
            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                No contract types found. {{ trans('global.create_new_one') }}?
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                ID
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Name
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Code
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Description
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Active
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Sort Order
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($contractTypes as $contractType)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                    {{ $contractType->id }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $contractType->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ $contractType->code ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ Str::limit($contractType->description ?? '—', 60) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if ($contractType->is_active)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-300">
                                            Yes
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-300">
                                            No
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ $contractType->sort_order ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        @can('contractType_show')
                                            <!-- or use 'contractType_access' / 'contractType_view' depending on your gates -->
                                            <a href="{{ route('admin.contractType.show', $contractType) }}"
                                                class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md
                                                      bg-indigo-50 text-indigo-700 hover:bg-indigo-100
                                                      dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50
                                                      focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1
                                                      transition-colors"
                                                title="View Details">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                View
                                            </a>
                                        @endcan

                                        @can('contractType_edit')
                                            <a href="{{ route('admin.contractType.edit', $contractType) }}"
                                                class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md
                                                      bg-blue-50 text-blue-700 hover:bg-blue-100
                                                      dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50
                                                      focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1
                                                      transition-colors"
                                                title="Edit">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                                Edit
                                            </a>
                                        @endcan

                                        @can('contractType_delete')
                                            <form action="{{ route('admin.contractType.destroy', $contractType) }}"
                                                method="POST" class="inline"
                                                onsubmit="return confirm('Are you sure you want to delete this contract type?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md
                                                               bg-red-50 text-red-700 hover:bg-red-100
                                                               dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50
                                                               focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-1
                                                               transition-colors"
                                                    title="Delete">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Delete
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination (uncomment when ready) -->
            {{-- <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $contractTypes->links() }}
            </div> --}}
        @endif
    </div>
</x-layouts.app>
