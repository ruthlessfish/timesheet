<?php

namespace Tests\Feature\Database;

use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveTimerConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_prevents_two_active_timers_for_same_user(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        // create first active timer
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'start_time' => now()->subMinutes(20),
            'end_time' => null,
        ]);

        $this->expectException(QueryException::class);

        // Attempt to create a second active timer directly at DB level
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'start_time' => now(),
            'end_time' => null,
        ]);
    }
}
