<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function total_hours_sums_all_time_entry_durations()
    {
        $project = Project::factory()->create();

        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'duration' => 120, // 2 hours
        ]);
        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'duration' => 90, // 1.5 hours
        ]);

        $this->assertEquals(3.5, $project->total_hours);
    }

    #[Test]
    public function total_amount_only_includes_billable_entries()
    {
        $project = Project::factory()->create(['hourly_rate' => 100]);

        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'duration' => 120, // 2 hours
            'is_billable' => true,
        ]);
        TimeEntry::factory()->nonBillable()->create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'duration' => 60, // 1 hour - should not be counted
        ]);

        $this->assertEquals(200.00, $project->total_amount);
    }

    #[Test]
    public function total_amount_respects_rate_cascade()
    {
        $client = Client::factory()->create(['hourly_rate' => 100]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $client->user_id,
            'hourly_rate' => 150,
        ]);

        // Entry with its own rate
        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'hourly_rate' => 200,
            'duration' => 60, // 1 hour
            'is_billable' => true,
        ]);

        // Entry using project rate
        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'hourly_rate' => null,
            'duration' => 60, // 1 hour
            'is_billable' => true,
        ]);

        // Total: 200 + 150 = 350
        $this->assertEquals(350.00, $project->total_amount);
    }

    #[Test]
    public function it_belongs_to_a_client()
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(Client::class, $project->client);
    }

    #[Test]
    public function it_has_many_time_entries()
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->count(5)->create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
        ]);

        $this->assertCount(5, $project->timeEntries);
    }

    #[Test]
    public function it_casts_dates_correctly()
    {
        $project = Project::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $project->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $project->end_date);
    }
}
