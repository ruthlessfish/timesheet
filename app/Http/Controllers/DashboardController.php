<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Services\TimeEntryService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService,
        private TimeEntryService $timeEntryService
    ) {}

    public function index()
    {
        $user = auth()->user();
        
        // Get statistics from AnalyticsService
        $stats = $this->analyticsService->getDashboardStats($user->id);
        $totalClients = $stats['total_clients'];
        $activeProjects = $stats['active_projects'];
        $monthlyHours = $stats['monthly_hours'];
        $monthlyRevenue = $stats['monthly_revenue'];
        
        // Get recent time entries
        $recentTimeEntries = $this->timeEntryService->getEntriesForUser($user->id, [
            'limit' => 10,
            'orderBy' => 'start_time',
            'orderDirection' => 'desc',
        ]);
        
        // Get active timer (if any)
        $activeTimer = $this->timeEntryService->getActiveTimer($user->id);
        
        // Data for charts - last 7 days
        $last7Days = $this->analyticsService->getDailyHoursTimeSeries($user->id, 7);
        
        // Hours by project (top 5)
        $projectHours = $this->analyticsService->getProjectHoursBreakdown($user->id, 5);
        
        // Billable vs Non-billable this month
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $billableRatio = $this->analyticsService->getBillableRatio(
            $user->id, 
            $startOfMonth, 
            $endOfMonth
        );
        $billableMinutes = $billableRatio['billable_minutes'];
        $nonBillableMinutes = $billableRatio['non_billable_minutes'];
        
        return view('dashboard', compact(
            'totalClients',
            'activeProjects',
            'monthlyHours',
            'monthlyRevenue',
            'recentTimeEntries',
            'activeTimer',
            'last7Days',
            'projectHours',
            'billableMinutes',
            'nonBillableMinutes'
        ));
    }
}
