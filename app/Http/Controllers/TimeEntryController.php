<?php

namespace App\Http\Controllers;

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

        // Filter by project if provided
        if ($request->has('project_id')) {
            $filters['project_id'] = $request->project_id;
        }

        // Filter by date range if provided
        if ($request->has('start_date')) {
            $filters['start_date'] = $request->start_date;
        }
        if ($request->has('end_date')) {
            $filters['end_date'] = $request->end_date;
        }

        $filters['paginate'] = 20;
        $filters['orderBy'] = 'start_time';
        $filters['orderDirection'] = 'desc';

        $timeEntries = $this->timeEntryService->getEntriesForUser(auth()->id(), $filters);

        // Get active timer if any
        $activeTimer = $this->timeEntryService->getActiveTimer(auth()->id());

        $projects = auth()->user()->projects()
            ->with('client')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('time-entries.index', compact('timeEntries', 'activeTimer', 'projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $projects = auth()->user()->projects()
            ->with('client')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('time-entries.create', compact('projects'));
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
        ]);

        $validated['is_billable'] = $request->has('is_billable') ? true : false;

        try {
            $timeEntry = $this->timeEntryService->createManualEntry(
                userId: auth()->id(),
                data: $validated
            );

            return redirect()->route('time-entries.index')
                ->with('success', 'Time entry created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TimeEntry $timeEntry)
    {
        $this->authorize('view', $timeEntry);

        $timeEntry->load('project.client');

        return view('time-entries.show', compact('timeEntry'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TimeEntry $timeEntry)
    {
        $this->authorize('update', $timeEntry);

        $projects = auth()->user()->projects()
            ->with('client')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('time-entries.edit', compact('timeEntry', 'projects'));
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
        ]);

        $validated['is_billable'] = $request->has('is_billable') ? true : false;

        $this->timeEntryService->updateEntry($timeEntry, $validated);

        return redirect()->route('time-entries.index')
            ->with('success', 'Time entry updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TimeEntry $timeEntry)
    {
        $this->authorize('delete', $timeEntry);

        $timeEntry->delete();

        return redirect()->route('time-entries.index')
            ->with('success', 'Time entry deleted successfully.');
    }

    /**
     * Stop a running timer.
     */
    public function stop(TimeEntry $timeEntry)
    {
        $this->authorize('update', $timeEntry);

        if ($timeEntry->end_time) {
            return back()->with('error', 'This timer has already been stopped.');
        }

        $this->timeEntryService->stopTimer($timeEntry);

        return redirect()->route('time-entries.index')
            ->with('success', 'Timer stopped successfully.');
    }

    /**
     * Bulk delete time entries.
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:time_entries,id',
        ]);

        // Verify ownership of all entries
        $entries = TimeEntry::whereIn('id', $validated['ids'])
            ->where('user_id', auth()->id())
            ->get();

        if ($entries->count() !== count($validated['ids'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if any entries are already invoiced
        $invoicedCount = $entries->where('is_invoiced', true)->count();
        if ($invoicedCount > 0) {
            return response()->json([
                'error' => "{$invoicedCount} of the selected entries have already been invoiced and cannot be deleted.",
            ], 422);
        }

        TimeEntry::whereIn('id', $validated['ids'])->delete();

        return response()->json(['success' => true, 'deleted' => count($validated['ids'])]);
    }

    /**
     * Show bulk edit form.
     */
    public function bulkEditForm(Request $request)
    {
        $ids = explode(',', $request->input('ids', ''));

        $entries = TimeEntry::whereIn('id', $ids)
            ->where('user_id', auth()->id())
            ->with('project.client')
            ->get();

        if ($entries->isEmpty()) {
            return redirect()->route('time-entries.index')
                ->with('error', 'No time entries selected.');
        }

        $projects = auth()->user()->projects()->with('client')->get();

        return view('time-entries.bulk-edit', compact('entries', 'projects'));
    }

    /**
     * Bulk update time entries.
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:time_entries,id',
            'project_id' => 'nullable|exists:projects,id',
            'is_billable' => 'nullable|boolean',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        // Build update data from non-null fields
        $updateData = [];
        if (isset($validated['project_id'])) {
            $updateData['project_id'] = $validated['project_id'];
        }
        if (isset($validated['is_billable'])) {
            $updateData['is_billable'] = $validated['is_billable'];
        }
        if (isset($validated['hourly_rate'])) {
            $updateData['hourly_rate'] = $validated['hourly_rate'];
        }

        if (empty($updateData)) {
            return redirect()->route('time-entries.index')
                ->with('error', 'No fields selected for update.');
        }

        $updated = TimeEntry::whereIn('id', $validated['ids'])
            ->where('user_id', auth()->id())
            ->update($updateData);

        return redirect()->route('time-entries.index')
            ->with('success', "Successfully updated {$updated} time entries.");
    }
}
