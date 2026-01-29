<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeEntryResource;
use App\Models\TimeEntry;
use App\Services\TimeEntryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class TimeEntryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TimeEntryService $timeEntryService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [];

        if ($request->has('project_id')) {
            $filters['project_id'] = $request->project_id;
        }

        if ($request->has('start_date')) {
            $filters['start_date'] = $request->start_date;
        }

        if ($request->has('end_date')) {
            $filters['end_date'] = $request->end_date;
        }

        if ($request->has('is_billable')) {
            $filters['is_billable'] = $request->boolean('is_billable');
        }

        if ($request->has('is_invoiced')) {
            $filters['is_invoiced'] = $request->boolean('is_invoiced');
        }

        $timeEntries = $this->timeEntryService->getEntriesForUser(auth()->id(), $filters);

        return TimeEntryResource::collection($timeEntries);
    }

    /**
     * Get active timer for the user
     */
    public function active()
    {
        $activeTimer = $this->timeEntryService->getActiveTimer(auth()->id());

        if (! $activeTimer) {
            return response()->json([
                'message' => 'No active timer',
            ], 404);
        }

        return new TimeEntryResource($activeTimer);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_billable' => 'boolean',
        ]);

        $validated['is_billable'] = $validated['is_billable'] ?? true;

        try {
            $timeEntry = $this->timeEntryService->createManualEntry(
                userId: auth()->id(),
                data: $validated
            );

            return new TimeEntryResource($timeEntry->load('project.client'));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TimeEntry $timeEntry)
    {
        $this->authorize('view', $timeEntry);

        $timeEntry->load('project.client');

        return new TimeEntryResource($timeEntry);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TimeEntry $timeEntry)
    {
        $this->authorize('update', $timeEntry);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_billable' => 'boolean',
        ]);

        $validated['is_billable'] = $validated['is_billable'] ?? $timeEntry->is_billable;

        $timeEntry = $this->timeEntryService->updateEntry($timeEntry, $validated);

        return new TimeEntryResource($timeEntry->load('project.client'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TimeEntry $timeEntry)
    {
        $this->authorize('delete', $timeEntry);

        $timeEntry->delete();

        return response()->json([
            'message' => 'Time entry deleted successfully',
        ]);
    }

    /**
     * Stop a running timer.
     */
    public function stop(TimeEntry $timeEntry)
    {
        $this->authorize('update', $timeEntry);

        if ($timeEntry->end_time) {
            return response()->json([
                'message' => 'This timer has already been stopped.',
            ], 422);
        }

        $this->timeEntryService->stopTimer($timeEntry);

        return new TimeEntryResource($timeEntry->fresh(['project.client']));
    }
}
