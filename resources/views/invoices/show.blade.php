<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Invoice {{ $invoice->invoice_number }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('invoices.pdf', $invoice) }}"
                    class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 inline-flex items-center px-4 py-2 border border-green-600 dark:border-green-400 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-green-50 dark:hover:bg-green-900/20 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Download PDF
                </a>
                <x-primary-button type="button" onclick="window.location='{{ route('invoices.edit', $invoice) }}'">
                    Edit Invoice
                </x-primary-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
            <div
                class="mb-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-400 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8">
                    <!-- Invoice Header -->
                    <div class="flex justify-between mb-8">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">INVOICE</h1>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $invoice->invoice_number }}</p>
                        </div>
                        <div class="text-right relative">
                            <span class="block absolute top-40 right-10 px-10 py-3 text-[200px] opacity-30 font-semibold rounded-full {{
                                $invoice->status_css }}"
                                style="transform: rotate(-45deg);text-outline:black;text-transform:uppercase ;background:transparent;">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bill To / From -->
                    <div class="grid grid-cols-2 gap-8 mb-6">
                        <div>
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                <span class="font-semibold text-gray-500 dark:text-gray-400 uppercase">Invoice
                                    Details:</span><br>
                                <span class="font-semibold">Issue Date:</span> {{ $invoice->issue_date->format('M d, Y')
                                }}<br>
                                <span class="font-semibold">Due Date:</span> {{ $invoice->due_date->format('M d, Y') }}
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">From</h3>
                            <div class="text-gray-900 dark:text-gray-100">
                                @php
                                $company = $invoice->user->defaultCompany();
                                @endphp

                                @if($company)
                                <p class="font-semibold">{{ $company->name }}</p>
                                @if($company->address)
                                <p class="whitespace-pre-line text-sm">{{ $company->address }}</p>
                                @endif
                                @if($company->phone)
                                <p class="text-sm">Phone: {{ $company->phone }}</p>
                                @endif
                                @if($company->email)
                                <p class="text-sm">Email: {{ $company->email }}</p>
                                @endif
                                @if($company->website)
                                <p class="text-sm">Web: {{ $company->website }}</p>
                                @endif
                                @else
                                <p class="font-semibold">{{ $invoice->user->name }}</p>
                                <p class="text-sm">Email: {{ $invoice->user->email }}</p>
                                @endif
                            </div>
                            <br>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Bill
                                    To
                                </h3>
                                <div class="text-gray-900 dark:text-gray-100">
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
                        </div>
                    </div>

                    <!-- Invoice Items -->
                    <div class="mb-8">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Type</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Description</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Qty</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Rate</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($invoice->consolidated_items as $item)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        @if($item->type === 'expense')
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400">Expense</span>
                                        @else
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">Service</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $item->description }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-gray-100">
                                        @if($item->type === 'expense')
                                        {{ number_format($item->quantity, 0) }}
                                        @else
                                        {{ number_format($item->quantity, 2) }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-gray-100">${{
                                        number_format($item->rate, 2) }}</td>
                                    <td
                                        class="px-6 py-4 text-sm text-right font-medium text-gray-900 dark:text-gray-100">
                                        ${{ number_format($item->amount, 2) }}</td>
                                </tr>
                                @endforeach
                                @if($invoice->total_hours > 0)
                                <tr class="bg-gray-50 dark:bg-gray-900">
                                    <td colspan="2"
                                        class="px-6 py-3 text-xs text-gray-500 dark:text-gray-400 uppercase">Total
                                        Hours:</td>
                                    <td class="px-6 py-3 text-right text-xs text-gray-500 dark:text-gray-400">
                                        {{ number_format($invoice->total_hours, 2) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals -->
                    <div class="flex justify-end mb-8">
                        <div class="w-64">
                            <div class="flex justify-between py-2 text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">${{
                                    number_format($invoice->subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Tax ({{ $invoice->tax_rate }}%):</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">${{
                                    number_format($invoice->tax_amount, 2) }}</span>
                            </div>
                            <div
                                class="flex justify-between py-3 text-lg font-bold border-t border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">
                                <span>Total:</span>
                                <span>${{ number_format($invoice->total, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($invoice->notes)
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Notes</h3>
                        <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $invoice->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>