<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $client->name }}
            </h2>
            <a href="{{ route('clients.edit', $client) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Edit Client
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Client Details -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Client Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500">Company:</span>
                            <p class="font-medium">{{ $client->company ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Email:</span>
                            <p class="font-medium">{{ $client->email ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Phone:</span>
                            <p class="font-medium">{{ $client->phone ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Hourly Rate:</span>
                            <p class="font-medium">{{ $client->hourly_rate ? '$' . number_format($client->hourly_rate, 2) : 'N/A' }}</p>
                        </div>
                        <div class="col-span-2">
                            <span class="text-sm text-gray-500">Address:</span>
                            <p class="font-medium">{{ $client->address ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Status:</span>
                            <p class="font-medium">
                                @if($client->is_active)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 text-sm">Total Hours</div>
                        <div class="text-3xl font-bold text-gray-900">{{ number_format($totalHours, 1) }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 text-sm">Total Revenue</div>
                        <div class="text-3xl font-bold text-green-600">${{ number_format($totalRevenue, 2) }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 text-sm">Active Projects</div>
                        <div class="text-3xl font-bold text-gray-900">{{ $client->projects->count() }}</div>
                    </div>
                </div>
            </div>

            <!-- Projects -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Projects</h3>
                        <a href="{{ route('projects.create') }}?client_id={{ $client->id }}" class="text-blue-600 hover:text-blue-800">
                            Add New Project
                        </a>
                    </div>
                    <div class="space-y-3">
                        @forelse($client->projects as $project)
                        <div class="border rounded p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <a href="{{ route('projects.show', $project) }}" class="text-lg font-medium text-blue-600 hover:text-blue-800">
                                        {{ $project->name }}
                                    </a>
                                    <p class="text-sm text-gray-600 mt-1">{{ $project->description }}</p>
                                    <div class="mt-2 text-xs text-gray-500">
                                        {{ $project->time_entries_count }} time entries
                                    </div>
                                </div>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($project->status === 'active') bg-green-100 text-green-800
                                    @elseif($project->status === 'completed') bg-blue-100 text-blue-800
                                    @elseif($project->status === 'on_hold') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </div>
                        </div>
                        @empty
                        <p class="text-gray-500 text-sm">No projects for this client yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Invoices -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Recent Invoices</h3>
                    <div class="space-y-3">
                        @forelse($recentInvoices as $invoice)
                        <div class="flex justify-between items-center border-b pb-2">
                            <div>
                                <a href="{{ route('invoices.show', $invoice) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $invoice->invoice_number }}
                                </a>
                                <p class="text-xs text-gray-500">{{ $invoice->issue_date->format('M d, Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium">${{ number_format($invoice->total, 2) }}</p>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($invoice->status === 'paid') bg-green-100 text-green-800
                                    @elseif($invoice->status === 'sent') bg-blue-100 text-blue-800
                                    @elseif($invoice->status === 'overdue') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </div>
                        </div>
                        @empty
                        <p class="text-gray-500 text-sm">No invoices for this client yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
