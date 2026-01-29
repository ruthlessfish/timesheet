<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Time Entries') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('time-entries.import-form') }}"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Import CSV
                </a>
                <a href="{{ route('time-entries.create') }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    Add Time Entry
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
            <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 dark:border-green-500 p-4">
                <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
            @endif

            @if(session('warning'))
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-500 p-4">
                <p class="text-sm text-yellow-700 dark:text-yellow-300">{{ session('warning') }}</p>
            </div>
            @endif

            @if(session('error'))
            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 dark:border-red-500 p-4">
                <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
            @endif

            <!-- Active Timer -->
            @if($activeTimer)
            <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-400 dark:border-blue-500 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300">Timer Running</h3>
                        <p class="text-blue-700 dark:text-blue-300 mt-1">
                            <strong>{{ $activeTimer->project->client->name }}</strong> - {{ $activeTimer->project->name
                            }}
                        </p>
                        @if($activeTimer->description)
                        <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">{{ $activeTimer->description }}</p>
                        @endif
                        <p class="text-sm text-blue-600 dark:text-blue-400 mt-2">Started: {{
                            $activeTimer->start_time->format('M d, Y g:i A') }}</p>
                        <p class="text-sm text-blue-600 dark:text-blue-400">Duration: <span id="timer-duration">{{
                                $activeTimer->start_time->diffForHumans(null, true) }}</span></p>
                    </div>
                    <form action="{{ route('time-entries.stop', $activeTimer) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg text-lg font-semibold">
                            Stop Timer
                        </button>
                    </form>
                </div>
            </div>

            <script>
                // Update timer display every second
                setInterval(function() {
                    const startTime = new Date('{{ $activeTimer->start_time->toIso8601String() }}');
                    const now = new Date();
                    const diff = Math.floor((now - startTime) / 1000);
                    
                    const hours = Math.floor(diff / 3600);
                    const minutes = Math.floor((diff % 3600) / 60);
                    const seconds = diff % 60;
                    
                    document.getElementById('timer-duration').textContent = 
                        `${hours}h ${minutes}m ${seconds}s`;
                }, 1000);
            </script>
            @endif

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('time-entries.index') }}"
                        class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="project_id"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Project</label>
                            <select name="project_id" id="project_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Projects</option>
                                @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ request('project_id')==$project->id ? 'selected' :
                                    '' }}>
                                    {{ $project->client->name }} - {{ $project->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="start_date"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                            <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">End
                                Date</label>
                            <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="flex items-end">
                            <button type="submit"
                                class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                                Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Time Entries List -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg" x-data="bulkSelector()">
                <!-- Bulk Actions Toolbar -->
                <div x-show="selectedIds.length > 0" x-transition
                    class="bg-indigo-50 dark:bg-indigo-900/20 border-b-2 border-indigo-200 dark:border-indigo-800 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-indigo-700 dark:text-indigo-300 font-medium">
                            <span x-text="selectedIds.length"></span> item(s) selected
                        </span>

                        <div class="flex items-center space-x-3">
                            <button @click="bulkEdit()"
                                class="px-3 py-1.5 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium transition">
                                Edit Selected
                            </button>

                            <button @click="bulkDelete()"
                                class="px-3 py-1.5 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm font-medium transition">
                                Delete Selected
                            </button>

                            <button @click="clearSelection()"
                                class="px-3 py-1.5 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500 text-sm font-medium transition">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3">
                                        <input type="checkbox" @change="toggleAll($event.target.checked)"
                                            :checked="selectedIds.length > 0 && selectedIds.length === {{ $timeEntries->count() }}"
                                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Date</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Client / Project</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Description</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Duration</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Billable</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Amount</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($timeEntries as $entry)
                                <tr
                                    :class="selectedIds.includes({{ $entry->id }}) ? 'bg-indigo-50 dark:bg-indigo-900/10' : ''">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" :checked="selectedIds.includes({{ $entry->id }})"
                                            @change="toggleSelection({{ $entry->id }})"
                                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $entry->start_time->format('M d, Y') }}
                                        <br>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{
                                            $entry->start_time->format('g:i A') }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{
                                            $entry->project->client->name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $entry->project->name
                                            }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $entry->description ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        @if($entry->end_time)
                                        {{ number_format($entry->duration / 60, 2) }}h
                                        @else
                                        <span class="text-blue-600 dark:text-blue-400 font-semibold">Running...</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($entry->is_billable)
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                            Yes
                                        </span>
                                        @else
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                            No
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        @if($entry->end_time && $entry->is_billable)
                                        ${{ number_format($entry->amount, 2) }}
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('time-entries.edit', $entry) }}"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3">Edit</a>
                                        <form action="{{ route('time-entries.destroy', $entry) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                                onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No time entries found. <a href="{{ route('time-entries.create') }}"
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Add
                                            your first time entry</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $timeEntries->links() }}
                    </div>
                </div>

                <script>
                    function bulkSelector() {
                    return {
                        selectedIds: [],
                        
                        toggleSelection(id) {
                            const index = this.selectedIds.indexOf(id);
                            if (index === -1) {
                                this.selectedIds.push(id);
                            } else {
                                this.selectedIds.splice(index, 1);
                            }
                        },
                        
                        toggleAll(checked) {
                            if (checked) {
                                this.selectedIds = @json($timeEntries->pluck('id')->toArray());
                            } else {
                                this.selectedIds = [];
                            }
                        },
                        
                        clearSelection() {
                            this.selectedIds = [];
                        },
                        
                        bulkDelete() {
                            if (!confirm(`Delete ${this.selectedIds.length} time entries? This action cannot be undone.`)) return;
                            
                            fetch('{{ route("time-entries.bulk-delete") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({ ids: this.selectedIds })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.error) {
                                    alert(data.error);
                                } else {
                                    window.location.reload();
                                }
                            })
                            .catch(error => {
                                alert('An error occurred while deleting entries.');
                                console.error(error);
                            });
                        },
                        
                        bulkEdit() {
                            const idsParam = this.selectedIds.join(',');
                            window.location.href = `{{ route('time-entries.bulk-edit') }}?ids=${idsParam}`;
                        }
                    }
                }
                </script>
            </div>
        </div>
    </div>
</x-app-layout>