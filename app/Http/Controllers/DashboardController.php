<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Get statistics
        $totalClients = $user->clients()->where('is_active', true)->count();
        $activeProjects = $user->projects()->where('status', 'active')->count();
        
        // Calculate total hours this month
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        $monthlyMinutes = $user->timeEntries()
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->sum('duration');
        $monthlyHours = round($monthlyMinutes / 60, 2);
        
        // Calculate monthly revenue (billable hours only)
        $monthlyTimeEntries = $user->timeEntries()
            ->with('project.client')
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->where('is_billable', true)
            ->get();
            
        $monthlyRevenue = $monthlyTimeEntries->sum(function ($entry) {
            $rate = $entry->hourly_rate 
                ?? $entry->project->hourly_rate 
                ?? $entry->project->client->hourly_rate 
                ?? 0;
            return ($entry->duration / 60) * $rate;
        });
        
        // Get recent time entries
        $recentTimeEntries = $user->timeEntries()
            ->with(['project.client'])
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get();
        
        // Get active timer (if any)
        $activeTimer = $user->timeEntries()
            ->whereNull('end_time')
            ->with('project.client')
            ->first();
        
        // Data for charts - last 7 days
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayMinutes = $user->timeEntries()
                ->whereDate('start_time', $date)
                ->sum('duration');
            
            $last7Days->push([
                'date' => $date->format('M d'),
                'hours' => round($dayMinutes / 60, 2),
            ]);
        }
        
        // Hours by project (top 5)
        $projectHours = $user->projects()
            ->with('timeEntries')
            ->get()
            ->map(function ($project) {
                return [
                    'name' => $project->name,
                    'hours' => round($project->timeEntries->sum('duration') / 60, 2),
                ];
            })
            ->sortByDesc('hours')
            ->take(5)
            ->values();
        
        // Billable vs Non-billable this month
        $billableMinutes = $user->timeEntries()
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->where('is_billable', true)
            ->sum('duration');
            
        $nonBillableMinutes = $user->timeEntries()
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->where('is_billable', false)
            ->sum('duration');
        
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
