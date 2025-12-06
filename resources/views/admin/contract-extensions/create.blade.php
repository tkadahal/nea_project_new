<x-layouts.app>
    <h1>Add Extension to Contract: {{ $contract->title }}</h1>

    <form method="POST" action="{{ route('admin.contract.extensions.store', $contract) }}">
        @csrf
        <x-forms.input label="Extension Period (days)" name="extension_period" type="number" min="1" required />
        <x-forms.text-area label="Reason" name="reason" required />
        <x-forms.date-input label="Approval Date" name="approval_date" required />

        <x-buttons.primary type="submit">Add Extension</x-buttons.primary>
    </form>
</x-layouts.app>
