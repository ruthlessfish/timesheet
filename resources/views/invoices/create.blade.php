<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Invoice') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('invoices.store') }}" method="POST" id="invoice-form">
                        @csrf

                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="client_id" class="block text-sm font-medium text-gray-700">Client *</label>
                                    <select name="client_id" id="client_id" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        onchange="this.form.submit()">
                                        <option value="">Select a client</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" {{ old('client_id', request('client_id')) == $client->id ? 'selected' : '' }}>
                                                {{ $client->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                                    <select name="status" id="status" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="sent" {{ old('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                                        <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="overdue" {{ old('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                    @error('status')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="issue_date" class="block text-sm font-medium text-gray-700">Issue Date *</label>
                                    <input type="date" name="issue_date" id="issue_date" value="{{ old('issue_date', now()->format('Y-m-d')) }}" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('issue_date')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date *</label>
                                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('due_date')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="tax_rate" class="block text-sm font-medium text-gray-700">Tax Rate (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="tax_rate" id="tax_rate" value="{{ old('tax_rate', 0) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('tax_rate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                <textarea name="notes" id="notes" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            @if($unbilledTimeEntries->count() > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Time Entries to Include</label>
                                <div class="border rounded-md p-4 max-h-96 overflow-y-auto">
                                    @foreach($unbilledTimeEntries as $entry)
                                    <div class="flex items-start space-x-3 p-2 hover:bg-gray-50">
                                        <input type="checkbox" name="time_entries[]" value="{{ $entry->id }}" id="entry_{{ $entry->id }}"
                                            {{ in_array($entry->id, old('time_entries', [])) ? 'checked' : '' }}
                                            class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <label for="entry_{{ $entry->id }}" class="flex-1 cursor-pointer">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $entry->project->name }} - {{ $entry->start_time->format('M d, Y') }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $entry->description ?? 'No description' }} 
                                                ({{ number_format($entry->duration / 60, 2) }} hours)
                                            </div>
                                        </label>
                                        <div class="text-sm font-medium text-gray-900">
                                            ${{ number_format(($entry->duration / 60) * ($entry->hourly_rate ?? $entry->project->hourly_rate ?? $entry->project->client->hourly_rate ?? 0), 2) }}
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @elseif(request('client_id'))
                            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                <p class="text-sm text-yellow-800">No unbilled time entries found for this client.</p>
                            </div>
                            @else
                            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                                <p class="text-sm text-blue-800">Select a client to view unbilled time entries.</p>
                            </div>
                            @endif
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <a href="{{ route('invoices.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded">
                                Cancel
                            </a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                                Create Invoice
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Prevent auto-submit when loading page with client_id in URL
        document.getElementById('client_id').addEventListener('change', function() {
            if (this.value) {
                window.location.href = '{{ route("invoices.create") }}?client_id=' + this.value;
            }
        });
    </script>
    @endpush
</x-app-layout>
