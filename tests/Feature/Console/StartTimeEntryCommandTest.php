<?php

namespace Tests\Feature\Console;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartTimeEntryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_starts_timer_happy_path(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $result = $this->artisan('time:start', ['--user' => $user->id, '--project-id' => $project->id, '--description' => 'CLI test'])->run();

        $this->assertEquals(0, $result);
        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'description' => 'CLI test',
        ]);
    }
}
