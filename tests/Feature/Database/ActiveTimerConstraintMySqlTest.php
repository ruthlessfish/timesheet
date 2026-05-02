<?php

namespace Tests\Feature\Database;

use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActiveTimerConstraintMySqlTest extends TestCase
{
    use RefreshDatabase;

    public function test_mysql_prevents_two_active_timers_via_generated_column(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL-only test.');
        }

        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'start_time' => now()->subMinutes(20),
            'end_time' => null,
        ]);

        $this->expectException(QueryException::class);

        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'start_time' => now(),
            'end_time' => null,
        ]);
    }
}
