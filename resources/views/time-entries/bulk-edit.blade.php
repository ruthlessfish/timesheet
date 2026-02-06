<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Bulk Edit Time Entries') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            Editing {{ count($entries) }} time entries
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Only fill in the fields you want to update. Empty fields will not be changed.
                        </p>
                    </div>

                    <!-- Preview of selected entries -->
                    <div class="mb-6 bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Selected Entries:</h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach($entries as $entry)
                            <div class="text-sm text-gray-600 dark:text-gray-400 flex justify-between">
                                <span>{{ $entry->start_time->format('M d, Y') }} - {{ $entry->project->client->name }} /
                                    {{ $entry->project->name }}</span>
                                <span class="text-gray-500">{{ $entry->description ?? 'No description' }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <form method="POST" action="{{ route('time-entries.bulk-update') }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        @foreach($entries as $entry)
                        <input type="hidden" name="ids[]" value="{{ $entry->id }}">
                        @endforeach

                        <!-- Project -->
                        <div>
                            <label for="project_id"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Change Project <span class="text-gray-500 text-xs">(optional)</span>
                            </label>
                            <select id="project_id" name="project_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Keep existing projects --</option>
                                @foreach($projects as $project)
                                <option value="{{ $project->id }}">
                                    {{ $project->client->name }} - {{ $project->name }}
                                </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                All selected entries will be moved to this project
                            </p>
                        </div>

                        <!-- Billable Status -->
                        <div>
                            <label for="is_billable"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Billable Status <span class="text-gray-500 text-xs">(optional)</span>
                            </label>
                            <select id="is_billable" name="is_billable"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Keep existing status --</option>
                                <option value="1">Billable</option>
                                <option value="0">Non-billable</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Mark all selected entries as billable or non-billable
                            </p>
                        </div>

                        <!-- Hourly Rate Override -->
                        <div>
                            <label for="hourly_rate"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Hourly Rate Override <span class="text-gray-500 text-xs">(optional)</span>
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 sm:text-sm">$</span>
                                </div>
                                <input type="number" id="hourly_rate" name="hourly_rate" step="0.01" min="0"
                                    class="pl-7 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Leave empty to keep existing rates">
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Override the hourly rate for all selected entries
                            </p>
                        </div>

                        <div
                            class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                            <x-secondary-button type="button"
                                onclick="window.location='{{ route('time-entries.index') }}'">
                                Cancel
                            </x-secondary-button>
                            <x-primary-button>
                                Update {{ count($entries) }} Entries
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>