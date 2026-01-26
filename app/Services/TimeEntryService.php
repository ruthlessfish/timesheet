<?php

namespace App\Services;

use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimeEntryService
{
    public function __construct(
        private BillingService $billingService
    ) {}

    /**
     * Start a new timer for a user
     * 
     * @throws \Exception if user already has an active timer
     */
    public function startTimer(int $userId, int $projectId, array $data = []): TimeEntry
    {
        // Check for active timers
        $activeTimer = TimeEntry::where('user_id', $userId)
            ->whereNull('end_time')
            ->first();

        if ($activeTimer) {
            throw new \Exception('You already have an active timer running. Please stop it before starting a new one.');
        }

        return TimeEntry::create([
            'user_id' => $userId,
            'project_id' => $projectId,
            'description' => $data['description'] ?? null,
            'start_time' => $data['start_time'] ?? now(),
            'end_time' => null,
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'is_billable' => $data['is_billable'] ?? true,
            'is_invoiced' => false,
        ]);
    }

    /**
     * Stop a running timer
     */
    public function stopTimer(TimeEntry $timeEntry): TimeEntry
    {
        if ($timeEntry->end_time !== null) {
            throw new \Exception('This timer has already been stopped.');
        }

        $timeEntry->stop();
        
        return $timeEntry->fresh();
    }

    /**
     * Create a manual time entry with duration calculation
     */
    public function createManualEntry(int $userId, array $data): TimeEntry
    {
        $timeEntry = TimeEntry::create([
            'user_id' => $userId,
            'project_id' => $data['project_id'],
            'description' => $data['description'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'is_billable' => $data['is_billable'] ?? true,
            'is_invoiced' => false,
        ]);

        // Calculate duration if end_time is provided
        if ($timeEntry->end_time) {
            $timeEntry->calculateDuration();
            $timeEntry->save();
        }

        return $timeEntry;
    }

    /**
     * Update an existing time entry
     */
    public function updateEntry(TimeEntry $timeEntry, array $data): TimeEntry
    {
        $timeEntry->update([
            'project_id' => $data['project_id'] ?? $timeEntry->project_id,
            'description' => $data['description'] ?? $timeEntry->description,
            'start_time' => $data['start_time'] ?? $timeEntry->start_time,
            'end_time' => $data['end_time'] ?? $timeEntry->end_time,
            'hourly_rate' => $data['hourly_rate'] ?? $timeEntry->hourly_rate,
            'is_billable' => $data['is_billable'] ?? $timeEntry->is_billable,
        ]);

        // Recalculate duration if times changed
        if (isset($data['start_time']) || isset($data['end_time'])) {
            if ($timeEntry->end_time) {
                $timeEntry->calculateDuration();
                $timeEntry->save();
            }
        }

        return $timeEntry->fresh();
    }

    /**
     * Get active timer for a user
     */
    public function getActiveTimer(int $userId): ?TimeEntry
    {
        return TimeEntry::where('user_id', $userId)
            ->whereNull('end_time')
            ->with('project.client')
            ->first();
    }

    /**
     * Get time entries for a user with optional filters
     */
    public function getEntriesForUser(int $userId, array $filters = []): Collection
    {
        $query = TimeEntry::where('user_id', $userId)
            ->with('project.client');

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('start_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('start_time', '<=', $filters['end_date']);
        }

        if (isset($filters['is_billable'])) {
            $query->where('is_billable', $filters['is_billable']);
        }

        if (isset($filters['is_invoiced'])) {
            $query->where('is_invoiced', $filters['is_invoiced']);
        }

        return $query->orderBy('start_time', 'desc')->get();
    }

    /**
     * Calculate total hours for a collection of time entries
     */
    public function calculateTotalHours(Collection $timeEntries): float
    {
        return $this->billingService->calculateTotalHours($timeEntries);
    }

    /**
     * Calculate total amount for a collection of time entries
     */
    public function calculateTotalAmount(Collection $timeEntries): float
    {
        return $this->billingService->calculateTotalAmount($timeEntries);
    }
}
