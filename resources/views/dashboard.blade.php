<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 text-sm">Active Clients</div>
                        <div class="text-3xl font-bold text-gray-900">{{ $totalClients }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 text-sm">Active Projects</div>
                        <div class="text-3xl font-bold text-gray-900">{{ $activeProjects }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 text-sm">Hours This Month</div>
                        <div class="text-3xl font-bold text-gray-900">{{ number_format($monthlyHours, 1) }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 text-sm">Revenue This Month</div>
                        <div class="text-3xl font-bold text-green-600">${{ number_format($monthlyRevenue, 2) }}</div>
                    </div>
                </div>
            </div>

            <!-- Active Timer Alert -->
            @if($activeTimer)
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Timer Running:</strong> {{ $activeTimer->project->client->name }} - {{ $activeTimer->project->name }}
                                <span class="text-xs ml-2">Started {{ $activeTimer->start_time->diffForHumans() }}</span>
                            </p>
                        </div>
                    </div>
                    <form action="{{ route('time-entries.stop', $activeTimer) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            Stop Timer
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Daily Hours Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Last 7 Days - Hours Tracked</h3>
                        <canvas id="dailyHoursChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Project Hours Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Top Projects by Hours</h3>
                        <canvas id="projectHoursChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Billable vs Non-billable -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Billable vs Non-billable (This Month)</h3>
                        <canvas id="billableChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Recent Time Entries -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Recent Time Entries</h3>
                            <a href="{{ route('time-entries.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
                        </div>
                        <div class="space-y-3">
                            @forelse($recentTimeEntries as $entry)
                            <div class="border-b pb-2">
                                <div class="flex justify-between">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $entry->project->client->name }} - {{ $entry->project->name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ number_format($entry->duration / 60, 2) }}h
                                    </div>
                                </div>
                                @if($entry->description)
                                <div class="text-xs text-gray-600 mt-1">{{ Str::limit($entry->description, 50) }}</div>
                                @endif
                                <div class="text-xs text-gray-400 mt-1">{{ $entry->start_time->format('M d, Y') }}</div>
                            </div>
                            @empty
                            <p class="text-gray-500 text-sm">No time entries yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Daily Hours Chart
        const dailyHoursCtx = document.getElementById('dailyHoursChart').getContext('2d');
        new Chart(dailyHoursCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($last7Days->pluck('date')) !!},
                datasets: [{
                    label: 'Hours',
                    data: {!! json_encode($last7Days->pluck('hours')) !!},
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Project Hours Chart
        const projectHoursCtx = document.getElementById('projectHoursChart').getContext('2d');
        new Chart(projectHoursCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($projectHours->pluck('name')) !!},
                datasets: [{
                    label: 'Hours',
                    data: {!! json_encode($projectHours->pluck('hours')) !!},
                    backgroundColor: 'rgb(59, 130, 246)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Billable Chart
        const billableCtx = document.getElementById('billableChart').getContext('2d');
        new Chart(billableCtx, {
            type: 'doughnut',
            data: {
                labels: ['Billable', 'Non-billable'],
                datasets: [{
                    data: [{{ $billableMinutes / 60 }}, {{ $nonBillableMinutes / 60 }}],
                    backgroundColor: ['rgb(34, 197, 94)', 'rgb(156, 163, 175)'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
    @endpush
</x-app-layout>
