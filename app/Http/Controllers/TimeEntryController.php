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

    /**
     * Show the CSV import form.
     */
    public function importForm()
    {
        $projects = auth()->user()->projects()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('time-entries.import', compact('projects'));
    }

    /**
     * Import time entries from CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        // Skip header row
        $header = fgetcsv($handle);

        $imported = 0;
        $errors = [];
        $row = 1; // Start at 1 since we skipped header

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            try {
                // Expected CSV format: project_name, description, start_time, end_time, hourly_rate, is_billable
                if (count($data) < 4) {
                    $errors[] = "Row {$row}: Not enough columns";

                    continue;
                }

                [$projectName, $description, $startTime, $endTime, $hourlyRate, $isBillable] = array_pad($data, 6, null);

                // Find project by name
                $project = auth()->user()->projects()
                    ->where('name', trim($projectName))
                    ->first();

                if (! $project) {
                    $errors[] = "Row {$row}: Project '{$projectName}' not found";

                    continue;
                }

                // Parse dates
                $startTimeParsed = \Carbon\Carbon::parse($startTime);
                $endTimeParsed = $endTime ? \Carbon\Carbon::parse($endTime) : null;

                // Validate end time is after start time
                if ($endTimeParsed && $endTimeParsed->lte($startTimeParsed)) {
                    $errors[] = "Row {$row}: End time must be after start time";

                    continue;
                }

                // Create time entry
                $this->timeEntryService->createManualEntry(
                    userId: auth()->id(),
                    data: [
                        'project_id' => $project->id,
                        'description' => trim($description) ?: null,
                        'start_time' => $startTimeParsed,
                        'end_time' => $endTimeParsed,
                        'hourly_rate' => $hourlyRate ? (float) $hourlyRate : null,
                        'is_billable' => in_array(strtolower(trim($isBillable ?? '1')), ['1', 'true', 'yes']),
                    ]
                );

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$row}: ".$e->getMessage();
            }
        }

        fclose($handle);

        if ($imported > 0 && empty($errors)) {
            return redirect()->route('time-entries.index')
                ->with('success', "Successfully imported {$imported} time entries.");
        } elseif ($imported > 0 && ! empty($errors)) {
            return redirect()->route('time-entries.index')
                ->with('warning', "Imported {$imported} time entries with ".count($errors).' errors.')
                ->with('import_errors', $errors);
        } else {
            return back()
                ->with('error', 'No time entries were imported.')
                ->with('import_errors', $errors);
        }
    }

    /**
     * Download a CSV template.
     */
    public function downloadTemplate()
    {
        $filename = 'time_entries_template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // Write header
            fputcsv($handle, [
                'project_name',
                'description',
                'start_time',
                'end_time',
                'hourly_rate',
                'is_billable',
            ]);

            // Write example row
            fputcsv($handle, [
                'Example Project',
                'Working on feature X',
                now()->subHours(2)->format('Y-m-d H:i:s'),
                now()->format('Y-m-d H:i:s'),
                '100.00',
                'yes',
            ]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
