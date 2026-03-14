<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Invoice') }} - {{ $invoice->invoice_number }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form id="edit-invoice-form" action="{{ route('invoices.update', $invoice) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="client_id"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Client
                                        *</label>
                                    <select name="client_id" id="client_id" required
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select a client</option>
                                        @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ old('client_id', $invoice->client_id) ==
                                            $client->id ? 'selected' : '' }}>
                                            {{ $client->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="status"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status
                                        *</label>
                                    <select name="status" id="status" required
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="draft" {{ old('status', $invoice->status) == 'draft' ? 'selected'
                                            : '' }}>Draft</option>
                                        <option value="sent" {{ old('status', $invoice->status) == 'sent' ? 'selected' :
                                            '' }}>Sent</option>
                                        <option value="paid" {{ old('status', $invoice->status) == 'paid' ? 'selected' :
                                            '' }}>Paid</option>
                                        <option value="overdue" {{ old('status', $invoice->status) == 'overdue' ?
                                            'selected' : '' }}>Overdue</option>
                                        <option value="cancelled" {{ old('status', $invoice->status) == 'cancelled' ?
                                            'selected' : '' }}>Cancelled</option>
                                    </select>
                                    @error('status')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="issue_date"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Issue Date
                                        *</label>
                                    <input type="date" name="issue_date" id="issue_date"
                                        value="{{ old('issue_date', $invoice->issue_date->format('Y-m-d')) }}" required
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('issue_date')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="due_date"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date
                                        *</label>
                                    <input type="date" name="due_date" id="due_date"
                                        value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}" required
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('due_date')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="tax_rate"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tax Rate
                                    (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="tax_rate" id="tax_rate"
                                    value="{{ old('tax_rate', $invoice->tax_rate) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('tax_rate')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="notes"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                                <textarea name="notes" id="notes" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $invoice->notes) }}</textarea>
                                @error('notes')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Invoice Items</h3>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <p>Subtotal: ${{ number_format($invoice->subtotal, 2) }}</p>
                                    <p>Tax: ${{ number_format($invoice->tax_amount, 2) }}</p>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">Total: ${{
                                        number_format($invoice->total, 2) }}</p>
                                </div>
                            </div>
                        </div>

                    </form>

                        <div class="mt-6 flex justify-between space-x-3">
                            <x-delete-button :url="route('invoices.destroy', $invoice)"
                                confirm-text="Are you sure you want to delete this invoice?" />
                            <div>
                                <x-secondary-button type="button"
                                    onclick="window.location='{{ route('invoices.show', $invoice) }}'">
                                    Cancel
                                </x-secondary-button>
                                <x-primary-button type="submit" form="edit-invoice-form">
                                    Update Invoice
                                </x-primary-button>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>