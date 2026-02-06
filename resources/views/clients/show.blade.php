<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $client->name }}
            </h2>
            <x-primary-button type="button" onclick="window.location='{{ route('clients.edit', $client) }}'">
                Edit Client
            </x-primary-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Client Details -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Client Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Company:</span>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $client->company ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Email:</span>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $client->email ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Phone:</span>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $client->phone ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Hourly Rate:</span>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $client->hourly_rate ? '$' .
                                number_format($client->hourly_rate, 2) : 'N/A' }}</p>
                        </div>
                        <div class="col-span-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Address:</span>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $client->address ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Status:</span>
                            <p class="font-medium">
                                @if($client->is_active)
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                    Active
                                </span>
                                @else
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                    Inactive
                                </span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 dark:text-gray-400 text-sm">Total Hours</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalHours,
                            1) }}</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 dark:text-gray-400 text-sm">Total Revenue</div>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">${{
                            number_format($totalRevenue, 2) }}</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 dark:text-gray-400 text-sm">Active Projects</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $client->projects->count()
                            }}</div>
                    </div>
                </div>
            </div>

            <!-- Projects -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Projects</h3>
                        <a href="{{ route('projects.create') }}?client_id={{ $client->id }}"
                            class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                            Add New Project
                        </a>
                    </div>
                    <div class="space-y-3">
                        @forelse($client->projects as $project)
                        <div class="border border-gray-200 dark:border-gray-700 rounded p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <a href="{{ route('projects.show', $project) }}"
                                        class="text-lg font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                        {{ $project->name }}
                                    </a>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $project->description }}
                                    </p>
                                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $project->time_entries_count }} time entries
                                    </div>
                                </div>
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $project->status_css }}">
                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </div>
                        </div>
                        @empty
                        <p class="text-gray-500 dark:text-gray-400 text-sm">No projects for this client yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Invoices -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Invoices</h3>
                    <div class="space-y-3">
                        @forelse($recentInvoices as $invoice)
                        <div
                            class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-2">
                            <div>
                                <a href="{{ route('invoices.show', $invoice) }}"
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
                                    {{ $invoice->invoice_number }}
                                </a>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $invoice->issue_date->format('M
                                    d, Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-gray-900 dark:text-gray-100">${{
                                    number_format($invoice->total, 2) }}</p>
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $invoice->status_css }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </div>
                        </div>
                        @empty
                        <p class="text-gray-500 dark:text-gray-400 text-sm">No invoices for this client yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>