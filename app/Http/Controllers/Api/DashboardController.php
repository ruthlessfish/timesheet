<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        $user = auth()->user();
        
        $stats = $this->analyticsService->getDashboardStats($user->id);

        return response()->json($stats);
    }

    /**
     * Get chart data for dashboard
     */
    public function charts(Request $request)
    {
        $user = auth()->user();
        
        $days = $request->input('days', 7);
        $projectLimit = $request->input('project_limit', 5);
        
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $data = [
            'daily_hours' => $this->analyticsService->getDailyHoursTimeSeries($user->id, $days),
            'project_hours' => $this->analyticsService->getProjectHoursBreakdown($user->id, $projectLimit),
            'billable_ratio' => $this->analyticsService->getBillableRatio($user->id, $startOfMonth, $endOfMonth),
        ];

        return response()->json($data);
    }
}
