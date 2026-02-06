<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Time Entry') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('time-entries.update', $timeEntry) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            <div>
                                <label for="project_id"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Project *</label>
                                <select name="project_id" id="project_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select a project</option>
                                    @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ old('project_id', $timeEntry->project_id) ==
                                        $project->id ? 'selected' : '' }}>
                                        {{ $project->name }} ({{ $project->client->name }})
                                    </option>
                                    @endforeach
                                </select>
                                @error('project_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="description"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <textarea name="description" id="description" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $timeEntry->description) }}</textarea>
                                @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="start_time"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Time
                                        *</label>
                                    <input type="datetime-local" name="start_time" id="start_time"
                                        value="{{ old('start_time', $timeEntry->start_time->format('Y-m-d\TH:i')) }}"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('start_time')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="end_time"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">End
                                        Time</label>
                                    <input type="datetime-local" name="end_time" id="end_time"
                                        value="{{ old('end_time', $timeEntry->end_time?->format('Y-m-d\TH:i')) }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank if timer is
                                        running</p>
                                    @error('end_time')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="hourly_rate"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hourly Rate
                                    Override</label>
                                <input type="number" step="0.01" min="0" name="hourly_rate" id="hourly_rate"
                                    value="{{ old('hourly_rate', $timeEntry->hourly_rate) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank to use project or
                                    client rate</p>
                                @error('hourly_rate')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="is_billable" id="is_billable" value="1" {{
                                        old('is_billable', $timeEntry->is_billable) ? 'checked' : '' }}
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600
                                    focus:ring-indigo-500">
                                </div>
                                <div class="ml-3">
                                    <label for="is_billable"
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300">Billable</label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Mark this time entry as billable
                                        to client</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-secondary-button type="button"
                                onclick="window.location='{{ route('time-entries.index') }}'">
                                Cancel
                            </x-secondary-button>
                            <x-primary-button>
                                Update Time Entry
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>