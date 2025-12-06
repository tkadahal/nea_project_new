@props([
    'headers' => [],
    'data' => [],
])

<div class="overflow-x-auto">
    <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-md">
        <thead>
            <tr class="bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-sm leading-normal">
                @foreach ($headers as $header)
                    <th class="py-3 px-6 text-left">{{ $header }}</th>
                @endforeach
                <th class="py-3 px-6 text-left">Actions</th>
            </tr>
        </thead>
        <tbody class="text-gray-600 dark:text-gray-300 text-sm font-light">
            @foreach ($data as $row)
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    @foreach ($row as $value)
                        <td class="py-3 px-6 text-left">{{ $value }}</td>
                    @endforeach
                    <td class="py-3 px-6 text-left">
                        <div class="flex space-x-2">
                            <a href="{{ route('admin.permission.show', $row['id']) }}"
                                class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-sm">View</a>
                            <a href="{{ route('admin.permission.edit', $row['id']) }}"
                                class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-sm">Edit</a>
                            <form action="{{ route('admin.permission.destroy', $row['id']) }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this permission?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@error('table')
    <span class="text-red-500 dark:text-red-400">{{ $message }}</span>
@enderror
