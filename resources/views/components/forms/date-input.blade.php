@props([
    'label',
    'name',
    'placeholder' => '',
    'error' => false,
    'class' => '',
    'labelClass' => '',
    'value' => '', // Add a value prop for pre-filling the date
])

@if ($label)
    <label for="{{ $name }}"
        {{ $attributes->merge(['class' => 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 ' . $labelClass]) }}>
        {{ $label }}
    </label>
@endif

<input type="date" id="{{ $name }}" placeholder="{{ $placeholder }}" name="{{ $name }}"
    value="{{ old($name, $value) }}" onclick="this.showPicker()" {{-- 👈 force open calendar when input is clicked --}}
    {{ $attributes->merge(['class' => 'w-full px-4 py-2 rounded-lg text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ' . $class]) }}>

@error($name)
    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
@enderror
