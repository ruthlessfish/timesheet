<?php

namespace Tests\Feature\Console;

use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StopTimeEntryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_stops_active_timer_happy_path(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $entry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'start_time' => now()->subHour(),
            'end_time' => null,
        ]);

        $result = $this->artisan('time:stop', ['--user' => $user->id, '--confirm' => true])->run();

        $this->assertEquals(0, $result);
        $this->assertNotNull($entry->fresh()->end_time);
    }
}
