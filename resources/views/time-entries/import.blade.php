<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Import Time Entries') }}
            </h2>
            <a href="{{ route('time-entries.index') }}"
                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                ← Back to Time Entries
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Instructions Card -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">
                    📋 Import Instructions
                </h3>
                <div class="text-sm text-blue-800 dark:text-blue-200 space-y-2">
                    <p><strong>CSV Format:</strong> Your CSV file should have the following columns:</p>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li><strong>project_name</strong> - Exact name of an existing project</li>
                        <li><strong>description</strong> - Optional description of the work</li>
                        <li><strong>start_time</strong> - Start date/time (e.g., "2026-01-29 09:00:00")</li>
                        <li><strong>end_time</strong> - End date/time (e.g., "2026-01-29 17:00:00") - leave empty for
                            ongoing</li>
                        <li><strong>hourly_rate</strong> - Optional hourly rate (overrides project/client defaults)</li>
                        <li><strong>is_billable</strong> - "yes", "1", or "true" for billable (default: yes)</li>
                    </ul>
                    <p class="mt-3">
                        <a href="{{ route('time-entries.import-template') }}"
                            class="inline-flex items-center text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-100 font-medium">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Download CSV Template
                        </a>
                    </p>
                </div>
            </div>

            <!-- Import Errors -->
            @if(session('import_errors'))
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-red-900 dark:text-red-100 mb-3">
                    ⚠️ Import Errors ({{ count(session('import_errors')) }})
                </h3>
                <div class="text-sm text-red-800 dark:text-red-200 max-h-64 overflow-y-auto">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach(session('import_errors') as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <!-- Upload Form -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('time-entries.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="space-y-6">
                            <div>
                                <label for="csv_file"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Select CSV File
                                </label>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" required
                                    class="block w-full text-sm text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none focus:border-indigo-500">
                                @error('csv_file')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Maximum file size: 2MB
                                </p>
                            </div>

                            <div
                                class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
                                <x-secondary-button type="button"
                                    onclick="window.location='{{ route('time-entries.index') }}'">
                                    Cancel
                                </x-secondary-button>
                                <x-primary-button class="inline-flex items-center" type="button">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                    </svg>
                                    Import Time Entries
                                </x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Available Projects -->
            @if($projects->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Your Active Projects
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        Use these exact project names in your CSV file:
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach($projects as $project)
                        <div class="flex items-center space-x-2 text-sm">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <code
                                class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-800 dark:text-gray-200">
                                        {{ $project->name }}
                                    </code>
                            <span class="text-gray-500 dark:text-gray-400">
                                ({{ $project->client->name }})
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @else
            <div
                class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 mt-6">
                <p class="text-yellow-800 dark:text-yellow-200">
                    ⚠️ You don't have any active projects. Please create a project before importing time entries.
                </p>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>