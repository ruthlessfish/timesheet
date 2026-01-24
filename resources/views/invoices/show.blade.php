<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Invoice {{ $invoice->invoice_number }}
            </h2>
            <div class="space-x-2">
                <a href="{{ route('invoices.pdf', $invoice) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                    Download PDF
                </a>
                <a href="{{ route('invoices.edit', $invoice) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    Edit Invoice
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8">
                    <!-- Invoice Header -->
                    <div class="flex justify-between mb-8">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">INVOICE</h1>
                            <p class="text-gray-600 mt-1">{{ $invoice->invoice_number }}</p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 text-sm font-semibold rounded-full 
                                @if($invoice->status === 'paid') bg-green-100 text-green-800
                                @elseif($invoice->status === 'sent') bg-blue-100 text-blue-800
                                @elseif($invoice->status === 'draft') bg-gray-100 text-gray-800
                                @elseif($invoice->status === 'overdue') bg-red-100 text-red-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bill To / From -->
                    <div class="grid grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Bill To</h3>
                            <div class="text-gray-900">
                                <p class="font-semibold">{{ $invoice->client->name }}</p>
                                @if($invoice->client->company)
                                    <p>{{ $invoice->client->company }}</p>
                                @endif
                                @if($invoice->client->email)
                                    <p>{{ $invoice->client->email }}</p>
                                @endif
                                @if($invoice->client->address)
                                    <p class="whitespace-pre-line">{{ $invoice->client->address }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Invoice Details</h3>
                            <div class="text-gray-900">
                                <p><span class="text-gray-600">Issue Date:</span> {{ $invoice->issue_date->format('M d, Y') }}</p>
                                <p><span class="text-gray-600">Due Date:</span> {{ $invoice->due_date->format('M d, Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Items -->
                    <div class="mb-8">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Hours</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rate</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($invoice->items as $item)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $item->description }}
                                        @if($item->timeEntry)
                                            <span class="text-xs text-gray-500">({{ $item->timeEntry->start_time->format('M d, Y') }})</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900">${{ number_format($item->rate, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-right font-medium text-gray-900">${{ number_format($item->amount, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals -->
                    <div class="flex justify-end mb-8">
                        <div class="w-64">
                            <div class="flex justify-between py-2 text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">${{ number_format($invoice->subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 text-sm">
                                <span class="text-gray-600">Tax ({{ $invoice->tax_rate }}%):</span>
                                <span class="font-medium">${{ number_format($invoice->tax_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-3 text-lg font-bold border-t border-gray-200">
                                <span>Total:</span>
                                <span>${{ number_format($invoice->total, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($invoice->notes)
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Notes</h3>
                        <p class="text-gray-900 whitespace-pre-line">{{ $invoice->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
