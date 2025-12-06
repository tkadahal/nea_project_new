@props([
    'label' => null,
    'name',
    'multiple' => false,
    'accept' => '.pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip',
    'maxSize' => '10MB',
    'placeholder' => '',
    'error' => false,
    'class' => '',
    'labelClass' => '',
])

@php
    // Parse accept prop to extract formats (e.g., '.pdf,.doc' -> 'PDF, DOC')
    $formats = collect(explode(',', $accept))->map(fn($ext) => strtoupper(trim($ext, '.')))->implode(', ');
@endphp

@if ($label)
    <label for="{{ $name }}"
        {{ $attributes->merge(['class' => 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 ' . $labelClass]) }}>
        {{ $label }}
    </label>
@endif

<input type="file" id="{{ $name }}" name="{{ $multiple ? $name . '[]' : $name }}"
    {{ $multiple ? 'multiple' : '' }} accept="{{ $accept }}" placeholder="{{ $placeholder }}"
    {{ $attributes->merge(['class' => 'w-full px-4 py-2 rounded-lg text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ' . $class, 'onchange' => 'updateFileNameList(event)']) }}
    aria-describedby="{{ $name }}-error">

<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
    {{ __('Supported formats: :formats. Max size: :maxSize each.', ['formats' => $formats, 'maxSize' => $maxSize]) }}
</p>

<div id="{{ $name }}-list" class="mt-1"></div>

@error($multiple ? $name . '.*' : $name)
    <p class="text-red-500 text-sm mt-1" id="{{ $name }}-error">{{ $message }}</p>
@enderror

<script>
    function waitForJquery(callback) {
        console.log('Checking for jQuery');
        if (typeof window.jQuery !== 'undefined') {
            console.log('jQuery loaded, executing callback');
            callback();
        } else {
            console.log('jQuery not loaded, retrying in 100ms');
            setTimeout(() => waitForJquery(callback), 100);
        }
    }

    waitForJquery(() => {
        console.log('Defining updateFileNameList and removeFile');
        window.updateFileNameList = function(event) {
            console.log('updateFileNameList triggered', event);
            const input = event.target;
            const fileList = document.getElementById(`${input.id}-list`);
            console.log('Input:', input, 'FileList element:', fileList, 'Files:', input.files);
            if (fileList) {
                fileList.innerHTML = ''; // Clear previous list
                const files = Array.from(input.files);
                console.log('Selected files:', files);
                if (files.length > 0) {
                    const ul = document.createElement('ul');
                    ul.className = 'text-sm text-gray-600 dark:text-gray-400 mt-1';
                    files.forEach((file, index) => {
                        console.log('Adding file:', file.name);
                        const li = document.createElement('li');
                        li.className = 'flex items-center justify-between';
                        const span = document.createElement('span');
                        span.textContent = file.name;
                        const removeButton = document.createElement('button');
                        removeButton.textContent = 'Remove';
                        removeButton.className =
                            'text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs ml-2';
                        removeButton.onclick = () => window.removeFile(input, index);
                        li.appendChild(span);
                        li.appendChild(removeButton);
                        ul.appendChild(li);
                    });
                    fileList.appendChild(ul);
                } else {
                    console.log('No files selected');
                }
            } else {
                console.error('File list element not found:', `${input.id}-list`);
            }
        };

        window.removeFile = function(input, indexToRemove) {
            console.log('removeFile called for index:', indexToRemove);
            const files = Array.from(input.files);
            const newFiles = files.filter((_, index) => index !== indexToRemove);
            console.log('New files after removal:', newFiles);
            const dataTransfer = new DataTransfer();
            newFiles.forEach(file => dataTransfer.items.add(file));
            input.files = dataTransfer.files;
            console.log('Updated input files:', input.files);
            // Trigger updateFileNameList to refresh the displayed list
            const event = new Event('change');
            input.dispatchEvent(event);
        };
    });
</script>
