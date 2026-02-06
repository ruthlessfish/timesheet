<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create Project') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('projects.store') }}" method="POST">
                        @csrf

                        <div class="space-y-6">
                            <div>
                                <label for="client_id"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Client *</label>
                                <select name="client_id" id="client_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select a client</option>
                                    @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id',
                                        request('client_id'))==$client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Project Name
                                    *</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="description"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <textarea name="description" id="description" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                                @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="hourly_rate"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hourly
                                        Rate</label>
                                    <input type="number" step="0.01" min="0" name="hourly_rate" id="hourly_rate"
                                        value="{{ old('hourly_rate') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank to use client
                                        rate</p>
                                    @error('hourly_rate')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="budget"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Budget</label>
                                    <input type="number" step="0.01" min="0" name="budget" id="budget"
                                        value="{{ old('budget') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('budget')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="status"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status *</label>
                                <select name="status" id="status" required
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="active" {{ old('status', 'active' )=='active' ? 'selected' : '' }}>
                                        Active</option>
                                    <option value="on_hold" {{ old('status')=='on_hold' ? 'selected' : '' }}>On Hold
                                    </option>
                                    <option value="completed" {{ old('status')=='completed' ? 'selected' : '' }}>
                                        Completed</option>
                                    <option value="cancelled" {{ old('status')=='cancelled' ? 'selected' : '' }}>
                                        Cancelled</option>
                                </select>
                                @error('status')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="start_date"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start
                                        Date</label>
                                    <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('start_date')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="end_date"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">End
                                        Date</label>
                                    <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('end_date')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-secondary-button type="button" onclick="window.location='{{ route('projects.index') }}'">
                                Cancel
                            </x-secondary-button>
                            <x-primary-button type="button">
                                Create Project
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>