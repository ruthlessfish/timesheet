<?php

namespace Tests\Feature\Console;

use App\Models\Project;
use App\Models\TimeEntry;
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

    public function test_start_fails_if_active_timer_exists(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        // create active timer
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'start_time' => now()->subMinutes(30),
            'end_time' => null,
        ]);

        $result = $this->artisan('time:start', ['--user' => $user->id, '--project-id' => $project->id])->run();

        $this->assertEquals(1, $result);
        $this->assertDatabaseCount('time_entries', 1);
    }

    public function test_interactive_project_choice_creates_entry(): void
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->for($user)->create(['name' => 'A Project']);
        $project2 = Project::factory()->for($user)->create(['name' => 'B Project']);

        $label1 = 'A Project ('.($project1->client->name ?? 'No client').')';
        $label2 = 'B Project ('.($project2->client->name ?? 'No client').')';

        $this->artisan('time:start', ['--user' => $user->id])
            ->expectsSearch(
                'Select a project',
                answer: (string) $project1->id,
                search: '',
                answers: [$project1->id, $project2->id, $label1, $label2],
            )
            ->run();

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'project_id' => $project1->id,
        ]);
    }

    public function test_start_fails_for_invalid_project(): void
    {
        $user = User::factory()->create();

        $result = $this->artisan('time:start', ['--user' => $user->id, '--project-id' => 99999])->run();

        $this->assertEquals(1, $result);
    }
}
