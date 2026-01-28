<?php

namespace App\Http\Controllers;

use App\Services\TimeEntryService;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __construct(
        private TimeEntryService $timeEntryService
    ) {}

    public function index()
    {
        return view('calendar.index');
    }

    public function entries(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');

        $entries = $this->timeEntryService->getEntriesForUser(
            userId: auth()->id(),
            filters: [
                'start_date' => $start,
                'end_date' => $end,
            ]
        );

        // Transform for FullCalendar
        $events = $entries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'title' => $entry->project->name.($entry->description ? ': '.$entry->description : ''),
                'start' => $entry->start_time,
                'end' => $entry->end_time,
                'backgroundColor' => $this->getProjectColor($entry->project_id),
                'borderColor' => $this->getProjectColor($entry->project_id),
                'extendedProps' => [
                    'projectName' => $entry->project->name,
                    'clientName' => $entry->project->client->name,
                    'duration' => $entry->duration,
                    'amount' => $entry->amount,
                    'isBillable' => $entry->is_billable,
                ],
            ];
        });

        return response()->json($events);
    }

    private function getProjectColor(int $projectId): string
    {
        // Generate consistent color per project
        $colors = [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
            '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#84CC16',
        ];

        return $colors[$projectId % count($colors)];
    }
}
