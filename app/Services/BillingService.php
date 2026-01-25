<?php

namespace App\Services;

use App\Models\Client;
use App\Models\TimeEntry;
use Illuminate\Support\Collection;

class BillingService
{
    /**
     * Resolve hourly rate using cascade: entry → project → client → 0
     */
    public function resolveHourlyRate(TimeEntry $timeEntry): float
    {
        // Ensure relationships are loaded
        $timeEntry->loadMissing('project.client');
        
        return $timeEntry->hourly_rate 
            ?? $timeEntry->project->hourly_rate 
            ?? $timeEntry->project->client->hourly_rate 
            ?? 0;
    }

    /**
     * Calculate amount for a time entry
     */
    public function calculateAmount(TimeEntry $timeEntry): float
    {
        $rate = $this->resolveHourlyRate($timeEntry);
        $hours = $timeEntry->duration / 60;
        
        return $hours * $rate;
    }

    /**
     * Get unbilled time entries for a client
     */
    public function getUnbilledTimeEntries(int $clientId, int $userId): Collection
    {
        return TimeEntry::with('project.client')
            ->whereHas('project', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->where('user_id', $userId)
            ->where('is_billable', true)
            ->where('is_invoiced', false)
            ->whereNotNull('end_time')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Calculate total amount for a collection of time entries
     */
    public function calculateTotalAmount(Collection $timeEntries): float
    {
        return $timeEntries->sum(function (TimeEntry $entry) {
            return $this->calculateAmount($entry);
        });
    }

    /**
     * Calculate total hours for a collection of time entries
     */
    public function calculateTotalHours(Collection $timeEntries): float
    {
        return $timeEntries->sum('duration') / 60;
    }

    /**
     * Mark time entries as invoiced
     */
    public function markAsInvoiced(Collection $timeEntries): void
    {
        TimeEntry::whereIn('id', $timeEntries->pluck('id'))
            ->update(['is_invoiced' => true]);
    }

    /**
     * Mark time entries as not invoiced
     */
    public function markAsNotInvoiced(Collection $timeEntries): void
    {
        TimeEntry::whereIn('id', $timeEntries->pluck('id'))
            ->update(['is_invoiced' => false]);
    }
}
