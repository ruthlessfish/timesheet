<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for generating analytics and statistics
 * for users and their projects
 */
class AnalyticsService
{
    public function __construct(
        private BillingService $billingService
    ) {}

    /**
     * Get dashboard statistics for a user
     * 
     * @return array<string, mixed>
     * @throws ModelNotFoundException
     * @throws \Exception
     * @return array<string, mixed> 
     */
    public function getDashboardStats(int $userId): array
    {
        $user = User::findOrFail($userId);

        return [
            'total_clients' => $this->getTotalClients($userId),
            'active_projects' => $this->getActiveProjects($userId),
            'monthly_hours' => $this->getMonthlyHours($userId),
            'monthly_revenue' => $this->getMonthlyRevenue($userId),
        ];
    }

    /**
     * Get total active clients for a user
     * 
     * @param int $userId
     * @return int
     */
    public function getTotalClients(int $userId): int
    {
        $user = User::findOrFail($userId);
        return $user->clients()->where('is_active', true)->count();
    }

    /**
     * Get total active projects for a user
     * 
     * @param int $userId
     * @return int
     */
    public function getActiveProjects(int $userId): int
    {
        $user = User::findOrFail($userId);
        return $user->projects()->where('status', 'active')->count();
    }

    /**
     * Get total hours worked this month
     * 
     * @param int $userId
     * @param Carbon|null $month
     * @return float
     */
    public function getMonthlyHours(int $userId, ?Carbon $month = null): float
    {
        $month = $month ?? Carbon::now();
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $user = User::findOrFail($userId);
        $monthlyMinutes = $user->timeEntries()
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->sum('duration');

        return round($monthlyMinutes / 60, 2);
    }

    /**
     * Get total revenue for this month (billable entries only)
     * @param int $userId
     * @param Carbon|null $month
     * @return float
     */
    public function getMonthlyRevenue(int $userId, ?Carbon $month = null): float
    {
        $month = $month ?? Carbon::now();
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $user = User::findOrFail($userId);
        $monthlyTimeEntries = $user->timeEntries()
            ->with('project.client')
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->where('is_billable', true)
            ->get();

        return $this->billingService->calculateTotalAmount($monthlyTimeEntries);
    }

    /**
     * Get daily hours for the last N days
     * @param int $userId
     * @param int $days
     * @return Collection<int, float>
     */
    public function getDailyHoursTimeSeries(int $userId, int $days = 7): Collection
    {
        $user = User::findOrFail($userId);
        $timeSeries = collect();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayMinutes = $user->timeEntries()
                ->whereDate('start_time', $date)
                ->sum('duration');

            $timeSeries->push([
                'date' => $date->format('M d'),
                'hours' => round($dayMinutes / 60, 2),
            ]);
        }

        return $timeSeries;
    }

    /**
     * Get hours breakdown by project (top N projects)
     * 
     * @param int $userId
     * @param int $limit
     * @return Collection<int, array{name: string, hours: float}>
     */
    public function getProjectHoursBreakdown(int $userId, int $limit = 5): Collection
    {
        $user = User::findOrFail($userId);

        return $user->projects()
            ->with('timeEntries')
            ->get()
            ->map(function ($project) {
                return [
                    'name' => $project->name,
                    'hours' => round($project->timeEntries->sum('duration') / 60, 2),
                ];
            })
            ->sortByDesc('hours')
            ->take($limit)
            ->values();
    }

    /**
     * Get billable vs non-billable hours for a period
     * 
     * @param int $userId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array<string, float|int>
     */
    public function getBillableRatio(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        $user = User::findOrFail($userId);

        $billableMinutes = $user->timeEntries()
            ->whereBetween('start_time', [$startDate, $endDate])
            ->where('is_billable', true)
            ->sum('duration');

        $nonBillableMinutes = $user->timeEntries()
            ->whereBetween('start_time', [$startDate, $endDate])
            ->where('is_billable', false)
            ->sum('duration');

        return [
            'billable_minutes' => $billableMinutes,
            'billable_hours' => round($billableMinutes / 60, 2),
            'non_billable_minutes' => $nonBillableMinutes,
            'non_billable_hours' => round($nonBillableMinutes / 60, 2),
            'total_minutes' => $billableMinutes + $nonBillableMinutes,
            'total_hours' => round(($billableMinutes + $nonBillableMinutes) / 60, 2),
            'billable_percentage' => ($billableMinutes + $nonBillableMinutes) > 0
                ? round(($billableMinutes / ($billableMinutes + $nonBillableMinutes)) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get revenue breakdown by client for a period
     * 
     * @param int $userId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return Collection<int, array{client_name: string, hours: float, revenue: float}>
     */
    public function getRevenueByClient(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        $user = User::findOrFail($userId);

        return $user->clients()
            ->with(['projects.timeEntries' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_time', [$startDate, $endDate])
                    ->where('is_billable', true);
            }])
            ->get()
            ->map(function ($client) {
                $timeEntries = $client->projects->flatMap->timeEntries;
                return [
                    'client_name' => $client->name,
                    'hours' => $this->billingService->calculateTotalHours($timeEntries),
                    'revenue' => $this->billingService->calculateTotalAmount($timeEntries),
                ];
            })
            ->sortByDesc('revenue')
            ->values();
    }

    /**
     * Get time entries statistics for a date range
     * 
     * @param int $userId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array<string, float|int> 
     */
    public function getTimeEntriesStats(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        $user = User::findOrFail($userId);
        $entries = $user->timeEntries()
            ->whereBetween('start_time', [$startDate, $endDate])
            ->get();

        $billableEntries = $entries->where('is_billable', true);
        $completedEntries = $entries->whereNotNull('end_time');

        return [
            'total_entries' => $entries->count(),
            'completed_entries' => $completedEntries->count(),
            'running_entries' => $entries->whereNull('end_time')->count(),
            'billable_entries' => $billableEntries->count(),
            'non_billable_entries' => $entries->where('is_billable', false)->count(),
            'invoiced_entries' => $entries->where('is_invoiced', true)->count(),
            'unbilled_entries' => $billableEntries->where('is_invoiced', false)->whereNotNull('end_time')->count(),
            'total_hours' => $this->billingService->calculateTotalHours($completedEntries),
            'total_revenue' => $this->billingService->calculateTotalAmount($billableEntries),
        ];
    }

    /**
     * Get average hourly rate across all projects
     * 
     * @param int $userId
     * @return float
     */
    public function getAverageHourlyRate(int $userId): float
    {
        $user = User::findOrFail($userId);
        $entries = $user->timeEntries()
            ->with('project.client')
            ->where('is_billable', true)
            ->whereNotNull('end_time')
            ->get();

        if ($entries->isEmpty()) {
            return 0;
        }

        $totalRevenue = $this->billingService->calculateTotalAmount($entries);
        $totalHours = $this->billingService->calculateTotalHours($entries);

        return $totalHours > 0 ? round($totalRevenue / $totalHours, 2) : 0;
    }
}
