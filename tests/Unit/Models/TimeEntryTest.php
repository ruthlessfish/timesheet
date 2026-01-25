<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_calculates_duration_in_minutes()
    {
        $timeEntry = TimeEntry::factory()->make([
            'start_time' => now()->subHours(2),
            'end_time' => now(),
            'duration' => null,
        ]);

        $timeEntry->calculateDuration();

        $this->assertEquals(120, $timeEntry->duration);
    }

    /** @test */
    public function it_does_not_calculate_duration_without_end_time()
    {
        $timeEntry = TimeEntry::factory()->make([
            'start_time' => now()->subHours(2),
            'end_time' => null,
            'duration' => null,
        ]);

        $timeEntry->calculateDuration();

        $this->assertNull($timeEntry->duration);
    }

    /** @test */
    public function stop_method_sets_end_time_and_calculates_duration()
    {
        $timeEntry = TimeEntry::factory()->create([
            'start_time' => now()->subHours(2),
            'end_time' => null,
            'duration' => null,
        ]);

        $timeEntry->stop();

        $this->assertNotNull($timeEntry->end_time);
        $this->assertNotNull($timeEntry->duration);
        $this->assertGreaterThan(0, $timeEntry->duration);
    }

    /** @test */
    public function amount_uses_time_entry_hourly_rate_when_set()
    {
        $client = Client::factory()->create(['hourly_rate' => 100]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $client->user_id,
            'hourly_rate' => 150,
        ]);
        $timeEntry = TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $client->user_id,
            'hourly_rate' => 200,
            'duration' => 120, // 2 hours
        ]);

        $this->assertEquals(400.00, $timeEntry->amount);
    }

    /** @test */
    public function amount_cascades_to_project_rate_when_entry_rate_is_null()
    {
        $client = Client::factory()->create(['hourly_rate' => 100]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $client->user_id,
            'hourly_rate' => 150,
        ]);
        $timeEntry = TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $client->user_id,
            'hourly_rate' => null,
            'duration' => 120, // 2 hours
        ]);

        $this->assertEquals(300.00, $timeEntry->amount);
    }

    /** @test */
    public function amount_cascades_to_client_rate_when_project_and_entry_rates_are_null()
    {
        $client = Client::factory()->create(['hourly_rate' => 100]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $client->user_id,
            'hourly_rate' => null,
        ]);
        $timeEntry = TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $client->user_id,
            'hourly_rate' => null,
            'duration' => 120, // 2 hours
        ]);

        $this->assertEquals(200.00, $timeEntry->amount);
    }

    /** @test */
    public function amount_defaults_to_zero_when_all_rates_are_null()
    {
        $client = Client::factory()->withoutRate()->create();
        $project = Project::factory()->withoutRate()->create([
            'client_id' => $client->id,
            'user_id' => $client->user_id,
        ]);
        $timeEntry = TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $client->user_id,
            'hourly_rate' => null,
            'duration' => 120,
        ]);

        $this->assertEquals(0.00, $timeEntry->amount);
    }

    /** @test */
    public function amount_is_calculated_correctly_for_fractional_hours()
    {
        $timeEntry = TimeEntry::factory()->create([
            'hourly_rate' => 100,
            'duration' => 90, // 1.5 hours
        ]);

        $this->assertEquals(150.00, $timeEntry->amount);
    }

    /** @test */
    public function it_belongs_to_a_project()
    {
        $timeEntry = TimeEntry::factory()->create();

        $this->assertInstanceOf(Project::class, $timeEntry->project);
    }

    /** @test */
    public function it_casts_dates_correctly()
    {
        $timeEntry = TimeEntry::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $timeEntry->start_time);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $timeEntry->end_time);
    }

    /** @test */
    public function it_casts_booleans_correctly()
    {
        $timeEntry = TimeEntry::factory()->create([
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $this->assertIsBool($timeEntry->is_billable);
        $this->assertIsBool($timeEntry->is_invoiced);
        $this->assertTrue($timeEntry->is_billable);
        $this->assertFalse($timeEntry->is_invoiced);
    }
}
