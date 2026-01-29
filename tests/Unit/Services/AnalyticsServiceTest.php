<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $analyticsService;

    private BillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billingService = new BillingService;
        $this->analyticsService = new AnalyticsService($this->billingService);
    }

    public function test_gets_dashboard_stats()
    {
        $user = User::factory()->create();
        Client::factory()->for($user)->count(3)->create(['is_active' => true]);
        $client = Client::factory()->for($user)->create(['is_active' => true]);
        $project = Project::factory()->for($client)->for($user)->create(['status' => 'active']);

        // Create time entry this month
        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => Carbon::now()->startOfMonth(),
            'duration' => 120,
            'is_billable' => true,
            'hourly_rate' => 100,
            'end_time' => Carbon::now()->startOfMonth()->addHours(2),
        ]);

        $stats = $this->analyticsService->getDashboardStats($user->id);

        $this->assertEquals(4, $stats['total_clients']);
        $this->assertEquals(1, $stats['active_projects']);
        $this->assertEquals(2, $stats['monthly_hours']);
        $this->assertEquals(200, $stats['monthly_revenue']);
    }

    public function test_gets_total_clients()
    {
        $user = User::factory()->create();
        Client::factory()->for($user)->count(3)->create(['is_active' => true]);
        Client::factory()->for($user)->create(['is_active' => false]);

        $count = $this->analyticsService->getTotalClients($user->id);

        $this->assertEquals(3, $count);
    }

    public function test_gets_active_projects()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        Project::factory()->for($client)->for($user)->count(2)->create(['status' => 'active']);
        Project::factory()->for($client)->for($user)->create(['status' => 'completed']);

        $count = $this->analyticsService->getActiveProjects($user->id);

        $this->assertEquals(2, $count);
    }

    public function test_gets_monthly_hours()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $project = Project::factory()->for($client)->for($user)->create();

        $startOfMonth = Carbon::now()->startOfMonth();

        // This month
        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => $startOfMonth->copy()->addDays(5),
            'duration' => 120, // 2 hours
            'end_time' => $startOfMonth->copy()->addDays(5)->addHours(2),
        ]);

        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => $startOfMonth->copy()->addDays(10),
            'duration' => 180, // 3 hours
            'end_time' => $startOfMonth->copy()->addDays(10)->addHours(3),
        ]);

        // Last month (should not be included)
        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => $startOfMonth->copy()->subMonth(),
            'duration' => 60,
            'end_time' => $startOfMonth->copy()->subMonth()->addHour(),
        ]);

        $hours = $this->analyticsService->getMonthlyHours($user->id);

        $this->assertEquals(5, $hours); // 2 + 3
    }

    public function test_gets_monthly_revenue()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create(['hourly_rate' => 100]);
        $project = Project::factory()->for($client)->for($user)->create(['hourly_rate' => null]);

        $startOfMonth = Carbon::now()->startOfMonth();

        // Billable entry
        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => $startOfMonth->copy()->addDays(5),
            'duration' => 120, // 2 hours
            'is_billable' => true,
            'hourly_rate' => null,
            'end_time' => $startOfMonth->copy()->addDays(5)->addHours(2),
        ]);

        // Non-billable entry (should not count)
        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => $startOfMonth->copy()->addDays(10),
            'duration' => 180,
            'is_billable' => false,
            'hourly_rate' => null,
            'end_time' => $startOfMonth->copy()->addDays(10)->addHours(3),
        ]);

        $revenue = $this->analyticsService->getMonthlyRevenue($user->id);

        $this->assertEquals(200, $revenue); // 2 hours * 100
    }

    public function test_gets_daily_hours_time_series()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $project = Project::factory()->for($client)->for($user)->create();

        // Create entries for last 7 days
        for ($i = 0; $i < 7; $i++) {
            TimeEntry::factory()->for($project)->for($user)->create([
                'start_time' => Carbon::now()->subDays($i),
                'duration' => 60 * ($i + 1), // Different hours each day
                'end_time' => Carbon::now()->subDays($i)->addHours($i + 1),
            ]);
        }

        $timeSeries = $this->analyticsService->getDailyHoursTimeSeries($user->id, 7);

        $this->assertCount(7, $timeSeries);
        $this->assertArrayHasKey('date', $timeSeries->first());
        $this->assertArrayHasKey('hours', $timeSeries->first());
    }

    public function test_gets_project_hours_breakdown()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $project1 = Project::factory()->for($client)->for($user)->create(['name' => 'Project A']);
        $project2 = Project::factory()->for($client)->for($user)->create(['name' => 'Project B']);
        $project3 = Project::factory()->for($client)->for($user)->create(['name' => 'Project C']);

        // Project A: 5 hours
        TimeEntry::factory()->for($project1)->for($user)->create(['duration' => 300]);

        // Project B: 3 hours
        TimeEntry::factory()->for($project2)->for($user)->create(['duration' => 180]);

        // Project C: 1 hour
        TimeEntry::factory()->for($project3)->for($user)->create(['duration' => 60]);

        $breakdown = $this->analyticsService->getProjectHoursBreakdown($user->id, 5);

        $this->assertCount(3, $breakdown);
        $this->assertEquals('Project A', $breakdown[0]['name']);
        $this->assertEquals(5, $breakdown[0]['hours']);
        $this->assertEquals('Project B', $breakdown[1]['name']);
        $this->assertEquals(3, $breakdown[1]['hours']);
    }

    public function test_gets_billable_ratio()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $project = Project::factory()->for($client)->for($user)->create();

        $startOfMonth = Carbon::now()->startOfMonth();

        // Billable: 180 minutes (3 hours)
        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => $startOfMonth->copy()->addDays(1),
            'duration' => 180,
            'is_billable' => true,
            'end_time' => $startOfMonth->copy()->addDays(1)->addHours(3),
        ]);

        // Non-billable: 60 minutes (1 hour)
        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => $startOfMonth->copy()->addDays(2),
            'duration' => 60,
            'is_billable' => false,
            'end_time' => $startOfMonth->copy()->addDays(2)->addHour(),
        ]);

        $ratio = $this->analyticsService->getBillableRatio($user->id);

        $this->assertEquals(180, $ratio['billable_minutes']);
        $this->assertEquals(3, $ratio['billable_hours']);
        $this->assertEquals(60, $ratio['non_billable_minutes']);
        $this->assertEquals(1, $ratio['non_billable_hours']);
        $this->assertEquals(240, $ratio['total_minutes']);
        $this->assertEquals(4, $ratio['total_hours']);
        $this->assertEquals(75, $ratio['billable_percentage']); // 180/240 * 100
    }

    public function test_gets_revenue_by_client()
    {
        $user = User::factory()->create();

        $client1 = Client::factory()->for($user)->create([
            'name' => 'Client A',
            'hourly_rate' => 100,
        ]);
        $client2 = Client::factory()->for($user)->create([
            'name' => 'Client B',
            'hourly_rate' => 150,
        ]);

        $project1 = Project::factory()->for($client1)->for($user)->create(['hourly_rate' => null]);
        $project2 = Project::factory()->for($client2)->for($user)->create(['hourly_rate' => null]);

        $startOfMonth = Carbon::now()->startOfMonth();

        // Client A: 2 hours
        TimeEntry::factory()->for($project1)->for($user)->create([
            'start_time' => $startOfMonth->copy()->addDays(1),
            'duration' => 120,
            'is_billable' => true,
            'hourly_rate' => null,
            'end_time' => $startOfMonth->copy()->addDays(1)->addHours(2),
        ]);

        // Client B: 3 hours
        TimeEntry::factory()->for($project2)->for($user)->create([
            'start_time' => $startOfMonth->copy()->addDays(2),
            'duration' => 180,
            'is_billable' => true,
            'hourly_rate' => null,
            'end_time' => $startOfMonth->copy()->addDays(2)->addHours(3),
        ]);

        $revenue = $this->analyticsService->getRevenueByClient($user->id);

        $this->assertCount(2, $revenue);
        // Client B should be first (higher revenue)
        $this->assertEquals('Client B', $revenue[0]['client_name']);
        $this->assertEquals(3, $revenue[0]['hours']);
        $this->assertEquals(450, $revenue[0]['revenue']);

        $this->assertEquals('Client A', $revenue[1]['client_name']);
        $this->assertEquals(2, $revenue[1]['hours']);
        $this->assertEquals(200, $revenue[1]['revenue']);
    }

    public function test_gets_time_entries_stats()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create(['hourly_rate' => 100]);
        $project = Project::factory()->for($client)->for($user)->create(['hourly_rate' => null]);

        $startOfMonth = Carbon::now()->startOfMonth();

        // Completed billable entry
        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => $startOfMonth->copy()->addDays(1),
            'duration' => 120,
            'is_billable' => true,
            'is_invoiced' => false,
            'hourly_rate' => null,
            'end_time' => $startOfMonth->copy()->addDays(1)->addHours(2),
        ]);

        // Completed non-billable entry
        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => $startOfMonth->copy()->addDays(2),
            'duration' => 60,
            'is_billable' => false,
            'is_invoiced' => false,
            'hourly_rate' => null,
            'end_time' => $startOfMonth->copy()->addDays(2)->addHour(),
        ]);

        // Running timer
        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => $startOfMonth->copy()->addDays(3),
            'duration' => null,
            'is_billable' => true,
            'is_invoiced' => false,
            'hourly_rate' => null,
            'end_time' => null,
        ]);

        // Invoiced entry
        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => $startOfMonth->copy()->addDays(4),
            'duration' => 90,
            'is_billable' => true,
            'is_invoiced' => true,
            'hourly_rate' => null,
            'end_time' => $startOfMonth->copy()->addDays(4)->addMinutes(90),
        ]);

        $stats = $this->analyticsService->getTimeEntriesStats($user->id);

        $this->assertEquals(4, $stats['total_entries']);
        $this->assertEquals(3, $stats['completed_entries']);
        $this->assertEquals(1, $stats['running_entries']);
        $this->assertEquals(3, $stats['billable_entries']);
        $this->assertEquals(1, $stats['non_billable_entries']);
        $this->assertEquals(1, $stats['invoiced_entries']);
        $this->assertEquals(1, $stats['unbilled_entries']); // Only completed, billable, not invoiced
        $this->assertEquals(4.5, $stats['total_hours']); // 120 + 60 + 90 = 270 minutes = 4.5 hours
        $this->assertEquals(350, $stats['total_revenue']); // (2 + 1.5) * 100
    }

    public function test_gets_average_hourly_rate()
    {
        $user = User::factory()->create();

        $client1 = Client::factory()->for($user)->create(['hourly_rate' => 100]);
        $client2 = Client::factory()->for($user)->create(['hourly_rate' => 150]);

        $project1 = Project::factory()->for($client1)->for($user)->create(['hourly_rate' => null]);
        $project2 = Project::factory()->for($client2)->for($user)->create(['hourly_rate' => null]);

        // 2 hours at $100/hour = $200
        TimeEntry::factory()->for($project1)->for($user)->create([
            'duration' => 120,
            'is_billable' => true,
            'hourly_rate' => null,
            'end_time' => now(),
        ]);

        // 2 hours at $150/hour = $300
        TimeEntry::factory()->for($project2)->for($user)->create([
            'duration' => 120,
            'is_billable' => true,
            'hourly_rate' => null,
            'end_time' => now(),
        ]);

        // Total: $500 / 4 hours = $125/hour average
        $avgRate = $this->analyticsService->getAverageHourlyRate($user->id);

        $this->assertEquals(125, $avgRate);
    }

    public function test_average_hourly_rate_returns_zero_when_no_entries()
    {
        $user = User::factory()->create();

        $avgRate = $this->analyticsService->getAverageHourlyRate($user->id);

        $this->assertEquals(0, $avgRate);
    }

    public function test_billable_ratio_handles_zero_entries()
    {
        $user = User::factory()->create();

        $ratio = $this->analyticsService->getBillableRatio($user->id);

        $this->assertEquals(0, $ratio['billable_percentage']);
        $this->assertEquals(0, $ratio['total_hours']);
    }
}
