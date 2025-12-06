@props([
    'label' => '',
    'name' => '',
    'placeholder' => '',
    'error' => false,
    'class' => '',
    'labelClass' => '',
    'value' => '',
])

@php
    // Sanitize the value to ensure it's a valid 6-digit hex color
$sanitizedValue = $value && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : '#000000';
@endphp

<div class="mb-4 {{ $error ? 'has-error' : '' }}">
    @if ($label)
        <label for="{{ $name }}"
            {{ $attributes->merge(['class' => 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 ' . $labelClass]) }}>
            {{ $label }}
        </label>
    @endif

    <div class="relative flex items-center">
        <input type="color" id="{{ $name }}" name="{{ $name }}" value="{{ $sanitizedValue }}"
            {{ $attributes->merge(['class' => 'w-10 h-10 p-1 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer ' . $class]) }}>
        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 js-color-value">{{ $sanitizedValue }}</span>
    </div>

    @error($name)
        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
    @enderror

    <!-- jQuery script to handle color changes -->
    @push('scripts')
        <script>
            jQuery(document).ready(function($) {
                const $colorInput = $('#{{ $name }}');
                const $colorValue = $colorInput.siblings('.js-color-value');

                // Update hex value display on input change
                $colorInput.on('input change', function() {
                    const color = $(this).val();
                    $colorValue.text(color);
                });

                // Debugging: Log initial value (remove in production)
                console.log(
                    'Color picker for {{ $name }}: Initial value = "{{ $value }}", Sanitized value = "{{ $sanitizedValue }}"'
                );
            });
        </script>
    @endpush
</div>
