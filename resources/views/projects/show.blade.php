<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $project->name }}
            </h2>
            <a href="{{ route('projects.edit', $project) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Edit Project
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Project Details -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Project Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500">Client:</span>
                            <p class="font-medium">{{ $project->client->name }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Status:</span>
                            <p class="font-medium">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($project->status === 'active') bg-green-100 text-green-800
                                    @elseif($project->status === 'completed') bg-blue-100 text-blue-800
                                    @elseif($project->status === 'on_hold') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Hourly Rate:</span>
                            <p class="font-medium">{{ $project->hourly_rate ? '$' . number_format($project->hourly_rate, 2) : 'Using client rate' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Budget:</span>
                            <p class="font-medium">{{ $project->budget ? '$' . number_format($project->budget, 2) : 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Start Date:</span>
                            <p class="font-medium">{{ $project->start_date ? $project->start_date->format('M d, Y') : 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">End Date:</span>
                            <p class="font-medium">{{ $project->end_date ? $project->end_date->format('M d, Y') : 'N/A' }}</p>
                        </div>
                        @if($project->description)
                        <div class="col-span-2">
                            <span class="text-sm text-gray-500">Description:</span>
                            <p class="font-medium">{{ $project->description }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 text-sm">Total Hours</div>
                        <div class="text-3xl font-bold text-gray-900">{{ number_format($totalHours, 1) }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 text-sm">Total Amount (Billable)</div>
                        <div class="text-3xl font-bold text-green-600">${{ number_format($totalAmount, 2) }}</div>
                    </div>
                </div>
            </div>

            <!-- Time Entries -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Recent Time Entries</h3>
                        <a href="{{ route('time-entries.create') }}?project_id={{ $project->id }}" class="text-blue-600 hover:text-blue-800">
                            Add Time Entry
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Billable</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($project->timeEntries as $entry)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $entry->start_time->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $entry->description ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($entry->end_time)
                                            {{ number_format($entry->duration / 60, 2) }}h
                                        @else
                                            <span class="text-blue-600">Running...</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($entry->is_billable)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Yes</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">No</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($entry->end_time && $entry->is_billable)
                                            ${{ number_format($entry->amount, 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No time entries yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
