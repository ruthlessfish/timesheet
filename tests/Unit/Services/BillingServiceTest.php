<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billingService = new BillingService;
    }

    public function test_resolves_rate_from_time_entry()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create(['hourly_rate' => 100]);
        $project = Project::factory()->for($client)->for($user)->create(['hourly_rate' => 150]);
        $timeEntry = TimeEntry::factory()->for($project)->for($user)->create(['hourly_rate' => 200]);

        $rate = $this->billingService->resolveHourlyRate($timeEntry);

        $this->assertEquals(200, $rate);
    }

    public function test_resolves_rate_from_project_when_entry_null()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create(['hourly_rate' => 100]);
        $project = Project::factory()->for($client)->for($user)->create(['hourly_rate' => 150]);
        $timeEntry = TimeEntry::factory()->for($project)->for($user)->create(['hourly_rate' => null]);

        $rate = $this->billingService->resolveHourlyRate($timeEntry);

        $this->assertEquals(150, $rate);
    }

    public function test_resolves_rate_from_client_when_project_and_entry_null()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create(['hourly_rate' => 100]);
        $project = Project::factory()->for($client)->for($user)->create(['hourly_rate' => null]);
        $timeEntry = TimeEntry::factory()->for($project)->for($user)->create(['hourly_rate' => null]);

        $rate = $this->billingService->resolveHourlyRate($timeEntry);

        $this->assertEquals(100, $rate);
    }

    public function test_resolves_rate_to_zero_when_all_null()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create(['hourly_rate' => null]);
        $project = Project::factory()->for($client)->for($user)->create(['hourly_rate' => null]);
        $timeEntry = TimeEntry::factory()->for($project)->for($user)->create(['hourly_rate' => null]);

        $rate = $this->billingService->resolveHourlyRate($timeEntry);

        $this->assertEquals(0, $rate);
    }

    public function test_calculates_amount_correctly()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create(['hourly_rate' => 100]);
        $project = Project::factory()->for($client)->for($user)->create(['hourly_rate' => null]);
        $timeEntry = TimeEntry::factory()->for($project)->for($user)->create([
            'hourly_rate' => null,
            'duration' => 150, // 2.5 hours
        ]);

        $amount = $this->billingService->calculateAmount($timeEntry);

        $this->assertEquals(250, $amount); // 2.5 * 100
    }

    public function test_gets_unbilled_time_entries_for_client()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $project = Project::factory()->for($client)->for($user)->create();

        // Create billable, uninvoiced entry (should be included)
        $unbilled = TimeEntry::factory()->for($project)->for($user)->create([
            'is_billable' => true,
            'is_invoiced' => false,
            'end_time' => now(),
        ]);

        // Create invoiced entry (should be excluded)
        TimeEntry::factory()->for($project)->for($user)->create([
            'is_billable' => true,
            'is_invoiced' => true,
            'end_time' => now(),
        ]);

        // Create non-billable entry (should be excluded)
        TimeEntry::factory()->for($project)->for($user)->create([
            'is_billable' => false,
            'is_invoiced' => false,
            'end_time' => now(),
        ]);

        // Create running timer (should be excluded)
        TimeEntry::factory()->for($project)->for($user)->create([
            'is_billable' => true,
            'is_invoiced' => false,
            'end_time' => null,
        ]);

        $unbilledEntries = $this->billingService->getUnbilledTimeEntries($client->id, $user->id);

        $this->assertCount(1, $unbilledEntries);
        $this->assertEquals($unbilled->id, $unbilledEntries->first()->id);
    }

    public function test_calculates_total_amount_for_collection()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create(['hourly_rate' => 100]);
        $project = Project::factory()->for($client)->for($user)->create(['hourly_rate' => null]);

        $entry1 = TimeEntry::factory()->for($project)->for($user)->create([
            'hourly_rate' => null,
            'duration' => 60,  // 1 hour
        ]);
        $entry2 = TimeEntry::factory()->for($project)->for($user)->create([
            'hourly_rate' => null,
            'duration' => 120, // 2 hours
        ]);

        $entries = collect([$entry1, $entry2]);
        $total = $this->billingService->calculateTotalAmount($entries);

        $this->assertEquals(300, $total); // (1 + 2) * 100
    }

    public function test_calculates_total_hours_for_collection()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $project = Project::factory()->for($client)->for($user)->create();

        $entry1 = TimeEntry::factory()->for($project)->for($user)->create(['duration' => 60]); // 1 hour
        $entry2 = TimeEntry::factory()->for($project)->for($user)->create(['duration' => 150]); // 2.5 hours

        $entries = collect([$entry1, $entry2]);
        $totalHours = $this->billingService->calculateTotalHours($entries);

        $this->assertEquals(3.5, $totalHours);
    }

    public function test_marks_entries_as_invoiced()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $project = Project::factory()->for($client)->for($user)->create();

        $entry1 = TimeEntry::factory()->for($project)->for($user)->create(['is_invoiced' => false]);
        $entry2 = TimeEntry::factory()->for($project)->for($user)->create(['is_invoiced' => false]);

        $entries = collect([$entry1, $entry2]);
        $this->billingService->markAsInvoiced($entries);

        $this->assertTrue($entry1->fresh()->is_invoiced);
        $this->assertTrue($entry2->fresh()->is_invoiced);
    }

    public function test_marks_entries_as_not_invoiced()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $project = Project::factory()->for($client)->for($user)->create();

        $entry1 = TimeEntry::factory()->for($project)->for($user)->create(['is_invoiced' => true]);
        $entry2 = TimeEntry::factory()->for($project)->for($user)->create(['is_invoiced' => true]);

        $entries = collect([$entry1, $entry2]);
        $this->billingService->markAsNotInvoiced($entries);

        $this->assertFalse($entry1->fresh()->is_invoiced);
        $this->assertFalse($entry2->fresh()->is_invoiced);
    }
}
