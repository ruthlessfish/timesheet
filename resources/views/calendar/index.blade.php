<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Calendar') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div id="calendar"></div>
                </div>
            </div>
            
            <!-- Entry Details Modal -->
            <div id="entryModal"></div>
        </div>
    </div>
    
    @push('styles')
    <style>
        /* FullCalendar custom styles for dark mode */
        .dark #calendar {
            --fc-border-color: #374151;
            --fc-button-bg-color: #4F46E5;
            --fc-button-border-color: #4F46E5;
            --fc-button-hover-bg-color: #4338CA;
            --fc-button-hover-border-color: #4338CA;
            --fc-button-active-bg-color: #3730A3;
            --fc-button-active-border-color: #3730A3;
            --fc-event-bg-color: #4F46E5;
            --fc-event-border-color: #4F46E5;
            --fc-page-bg-color: #1F2937;
            --fc-neutral-bg-color: #374151;
            --fc-neutral-text-color: #F3F4F6;
            --fc-list-event-hover-bg-color: #374151;
        }
        
        .dark .fc {
            color: #F3F4F6;
        }
        
        .dark .fc .fc-col-header-cell {
            background-color: #374151;
            color: #F3F4F6;
        }
        
        .dark .fc .fc-daygrid-day {
            background-color: #1F2937;
        }
        
        .dark .fc .fc-daygrid-day-number {
            color: #F3F4F6;
        }
        
        .dark .fc .fc-day-today {
            background-color: #374151 !important;
        }
        
        .dark .fc .fc-button {
            color: #fff;
        }
        
        .dark .fc .fc-button-primary:not(:disabled).fc-button-active {
            background-color: #3730A3;
            border-color: #3730A3;
        }
    </style>
    @endpush
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek'
            },
            events: {
                url: '{{ route("calendar.entries") }}',
                method: 'GET',
                extraParams: function() {
                    return {
                        _token: '{{ csrf_token() }}'
                    };
                },
                failure: function() {
                    alert('Error loading time entries');
                }
            },
            eventClick: function(info) {
                showEntryDetails(info.event);
            },
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            },
            displayEventTime: true,
            eventDidMount: function(info) {
                // Add tooltip with duration
                const duration = info.event.extendedProps.duration;
                const hours = Math.floor(duration / 60);
                const minutes = duration % 60;
                info.el.title = `${hours}h ${minutes}m`;
            },
            height: 'auto',
            aspectRatio: 1.5
        });
        
        calendar.render();
        
        function showEntryDetails(event) {
            const props = event.extendedProps;
            const duration = props.duration;
            const hours = Math.floor(duration / 60);
            const minutes = duration % 60;
            
            // Create modal content
            const modalHtml = `
                <div class="fixed inset-0 z-50 overflow-y-auto" id="entryModalOverlay">
                    <div class="flex items-center justify-center min-h-screen p-4">
                        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeModal()"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Time Entry Details
                            </h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Client:</span>
                                    <p class="text-gray-900 dark:text-white">${props.clientName}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Project:</span>
                                    <p class="text-gray-900 dark:text-white">${props.projectName}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Description:</span>
                                    <p class="text-gray-900 dark:text-white">${event.title || 'No description'}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Duration:</span>
                                    <p class="text-gray-900 dark:text-white">${hours}h ${minutes}m</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Amount:</span>
                                    <p class="text-gray-900 dark:text-white">$${props.amount.toFixed(2)}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Billable:</span>
                                    <p class="text-gray-900 dark:text-white">
                                        ${props.isBillable ? 'Yes' : 'No'}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-6 flex justify-end space-x-3">
                                <a href="/time-entries/${event.id}/edit" 
                                   class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                                    Edit
                                </a>
                                <button onclick="closeModal()" 
                                        class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500 transition">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('entryModal').innerHTML = modalHtml;
        }
        
        window.closeModal = function() {
            document.getElementById('entryModal').innerHTML = '';
        };
    });
    </script>
    @endpush
</x-app-layout>
