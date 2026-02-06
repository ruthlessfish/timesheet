<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Client') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('clients.update', $client) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label for="name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name *</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $client->name) }}"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email', $client->email) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $client->phone) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('phone')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-span-2">
                                <label for="company"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Company</label>
                                <input type="text" name="company" id="company"
                                    value="{{ old('company', $client->company) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('company')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-span-2">
                                <label for="address"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                                <textarea name="address" id="address" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('address', $client->address) }}</textarea>
                                @error('address')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="hourly_rate"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Hourly
                                    Rate</label>
                                <input type="number" step="0.01" min="0" name="hourly_rate" id="hourly_rate"
                                    value="{{ old('hourly_rate', $client->hourly_rate) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('hourly_rate')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="is_active" class="flex items-center mt-6">
                                    <input type="checkbox" name="is_active" id="is_active" {{ old('is_active',
                                        $client->is_active) ? 'checked' : '' }}
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm
                                    focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-secondary-button type="button" onclick="window.location='{{ route('clients.index') }}'">
                                Cancel
                            </x-secondary-button>
                            <x-primary-button>
                                Update Client
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>