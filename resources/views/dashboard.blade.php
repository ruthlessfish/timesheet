<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            @if($firstProject && !$activeTimer)
                <div class="relative inline-flex" x-data="{ open: false }">
                    <!-- Main Start Timer Button -->
                    <button id="startTimerBtn" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-l-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span id="timerBtnText">Start Timer</span>
                    </button>
                    
                    <!-- Dropdown Button -->
                    <button @click="open = !open" @click.away="open = false" type="button" class="inline-flex items-center px-3 py-2 bg-gray-800 border border-l border-gray-700 rounded-r-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 top-full mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-gray-200" style="display: none;">
                        <div class="py-1 max-h-64 overflow-y-auto">
                            @foreach($userProjects as $project)
                                <button onclick="startTimer({{ $project->id }})" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition">
                                    <div class="font-medium">{{ $project->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $project->client->name }}</div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @elseif(!$firstProject && !$activeTimer)
                <a href="{{ route('projects.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-l-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">>
                    Create Project First
                </a>
            @endif
        </div>
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
                        <a href="{{ route('time-entries.edit', $activeTimer) }}" class="text-indigo-500 hover:underline pr-2 text-lg font-bold">Edit</a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
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
                        <div style="height: 300px;">
                            <canvas id="dailyHoursChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Project Hours Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Top Projects by Hours</h3>
                        <div style="height: 300px;">
                            <canvas id="projectHoursChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billable vs Non-billable -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Billable vs Non-billable (This Month)</h3>
                        <div style="height: 300px;">
                            <canvas id="billableChart"></canvas>
                        </div>
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
                maintainAspectRatio: true,
                aspectRatio: 2,
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
                maintainAspectRatio: true,
                aspectRatio: 2,
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
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Start Timer via API
        @if($firstProject && !$activeTimer)
        const startTimerBtn = document.getElementById('startTimerBtn');
        const timerBtnText = document.getElementById('timerBtnText');
        
        // Function to start timer with a specific project
        async function startTimer(projectId) {
            // Disable button during request
            startTimerBtn.disabled = true;
            timerBtnText.textContent = 'Starting...';
            
            try {
                const response = await fetch('/api/v1/time-entries', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': 'Bearer {{ $apiToken }}'
                    },
                    body: JSON.stringify({
                        project_id: projectId,
                        start_time: new Date().toISOString(),
                        is_billable: true
                    })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    // Reload page to show active timer
                    window.location.reload();
                } else {
                    const errorData = await response.json();
                    alert('Error starting timer: ' + (errorData.message || 'Unknown error'));
                    startTimerBtn.disabled = false;
                    timerBtnText.textContent = 'Start Timer';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to start timer. Please try again.');
                startTimerBtn.disabled = false;
                timerBtnText.textContent = 'Start Timer';
            }
        }
        
        // Main button uses first project
        startTimerBtn.addEventListener('click', async function() {
            await startTimer({{ $firstProject->id }});
        });
        @endif
    </script>
    @endpush
</x-app-layout>
