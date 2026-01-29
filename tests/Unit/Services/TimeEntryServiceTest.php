<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\BillingService;
use App\Services\TimeEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryServiceTest extends TestCase
{
    use RefreshDatabase;

    private TimeEntryService $timeEntryService;

    private BillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billingService = new BillingService;
        $this->timeEntryService = new TimeEntryService($this->billingService);
    }

    public function test_starts_timer_for_user()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $timer = $this->timeEntryService->startTimer($user->id, $project->id, [
            'description' => 'Working on feature',
        ]);

        $this->assertInstanceOf(TimeEntry::class, $timer);
        $this->assertEquals($user->id, $timer->user_id);
        $this->assertEquals($project->id, $timer->project_id);
        $this->assertEquals('Working on feature', $timer->description);
        $this->assertNull($timer->end_time);
        $this->assertTrue($timer->is_billable);
        $this->assertFalse($timer->is_invoiced);
    }

    public function test_throws_exception_if_user_has_active_timer()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        // Start first timer
        $this->timeEntryService->startTimer($user->id, $project->id);

        // Try to start second timer
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You already have an active timer running');

        $this->timeEntryService->startTimer($user->id, $project->id);
    }

    public function test_stops_running_timer()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $timer = $this->timeEntryService->startTimer($user->id, $project->id, [
            'start_time' => now()->subMinutes(5),
        ]);

        $this->assertNull($timer->end_time);

        $stoppedTimer = $this->timeEntryService->stopTimer($timer);

        $this->assertNotNull($stoppedTimer->end_time);
        $this->assertGreaterThan(0, $stoppedTimer->duration);
    }

    public function test_throws_exception_when_stopping_already_stopped_timer()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $timer = TimeEntry::factory()->for($project)->for($user)->create([
            'end_time' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This timer has already been stopped');

        $this->timeEntryService->stopTimer($timer);
    }

    public function test_creates_manual_entry_with_duration()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $entry = $this->timeEntryService->createManualEntry($user->id, [
            'project_id' => $project->id,
            'description' => 'Manual entry',
            'start_time' => now()->subHours(2),
            'end_time' => now(),
            'is_billable' => true,
        ]);

        $this->assertInstanceOf(TimeEntry::class, $entry);
        $this->assertEquals('Manual entry', $entry->description);
        $this->assertNotNull($entry->end_time);
        $this->assertEquals(120, $entry->duration); // 2 hours in minutes
    }

    public function test_creates_manual_entry_without_end_time()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $entry = $this->timeEntryService->createManualEntry($user->id, [
            'project_id' => $project->id,
            'start_time' => now(),
        ]);

        $this->assertNull($entry->end_time);
        $this->assertNull($entry->duration);
    }

    public function test_updates_time_entry()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $entry = TimeEntry::factory()->for($project)->for($user)->create([
            'description' => 'Old description',
        ]);

        $updated = $this->timeEntryService->updateEntry($entry, [
            'description' => 'New description',
            'is_billable' => false,
        ]);

        $this->assertEquals('New description', $updated->description);
        $this->assertFalse($updated->is_billable);
    }

    public function test_updates_recalculates_duration_when_times_change()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $startTime = now()->subHours(3);
        $endTime = now()->subHours(2);

        $entry = TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => 60,
        ]);

        $newEndTime = now();
        $updated = $this->timeEntryService->updateEntry($entry, [
            'end_time' => $newEndTime,
        ]);

        $this->assertEquals(180, $updated->duration); // 3 hours
    }

    public function test_gets_active_timer_for_user()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        // Create stopped entry
        TimeEntry::factory()->for($project)->for($user)->create([
            'end_time' => now(),
        ]);

        // Create active timer
        $activeTimer = TimeEntry::factory()->for($project)->for($user)->create([
            'end_time' => null,
        ]);

        $result = $this->timeEntryService->getActiveTimer($user->id);

        $this->assertNotNull($result);
        $this->assertEquals($activeTimer->id, $result->id);
    }

    public function test_returns_null_when_no_active_timer()
    {
        $user = User::factory()->create();

        $result = $this->timeEntryService->getActiveTimer($user->id);

        $this->assertNull($result);
    }

    public function test_gets_entries_for_user_with_filters()
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->for($user)->create();
        $project2 = Project::factory()->for($user)->create();

        // Create entries for project 1
        TimeEntry::factory()->for($project1)->for($user)->create([
            'start_time' => now()->subDays(5),
        ]);
        TimeEntry::factory()->for($project1)->for($user)->create([
            'start_time' => now()->subDays(3),
        ]);

        // Create entry for project 2
        TimeEntry::factory()->for($project2)->for($user)->create([
            'start_time' => now()->subDays(1),
        ]);

        // Filter by project
        $entries = $this->timeEntryService->getEntriesForUser($user->id, [
            'project_id' => $project1->id,
        ]);

        $this->assertCount(2, $entries);
    }

    public function test_gets_entries_for_user_with_date_filters()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => now()->subDays(10),
        ]);
        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => now()->subDays(5),
        ]);
        TimeEntry::factory()->for($project)->for($user)->create([
            'start_time' => now()->subDays(2),
        ]);

        $entries = $this->timeEntryService->getEntriesForUser($user->id, [
            'start_date' => now()->subDays(6)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]);

        $this->assertCount(2, $entries);
    }

    public function test_gets_entries_for_user_with_billable_filter()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        TimeEntry::factory()->for($project)->for($user)->create(['is_billable' => true]);
        TimeEntry::factory()->for($project)->for($user)->create(['is_billable' => true]);
        TimeEntry::factory()->for($project)->for($user)->create(['is_billable' => false]);

        $entries = $this->timeEntryService->getEntriesForUser($user->id, [
            'is_billable' => true,
        ]);

        $this->assertCount(2, $entries);
    }

    public function test_calculates_total_hours()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $entry1 = TimeEntry::factory()->for($project)->for($user)->create(['duration' => 60]);
        $entry2 = TimeEntry::factory()->for($project)->for($user)->create(['duration' => 90]);

        $entries = collect([$entry1, $entry2]);
        $totalHours = $this->timeEntryService->calculateTotalHours($entries);

        $this->assertEquals(2.5, $totalHours);
    }

    public function test_calculates_total_amount()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create(['hourly_rate' => 100]);
        $project = Project::factory()->for($client)->for($user)->create(['hourly_rate' => null]);

        $entry1 = TimeEntry::factory()->for($project)->for($user)->create([
            'hourly_rate' => null,
            'duration' => 60,
        ]);
        $entry2 = TimeEntry::factory()->for($project)->for($user)->create([
            'hourly_rate' => null,
            'duration' => 120,
        ]);

        $entries = collect([$entry1, $entry2]);
        $totalAmount = $this->timeEntryService->calculateTotalAmount($entries);

        $this->assertEquals(300, $totalAmount); // (1 + 2) * 100
    }
}
