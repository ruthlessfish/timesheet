<?php

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function user_can_list_their_time_entries_via_api()
    {
        $timeEntry = TimeEntry::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/time-entries');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'description',
                    'start_time',
                    'end_time',
                    'duration',
                    'amount',
                    'is_billable',
                    'is_invoiced',
                ],
            ],
        ]);
    }

    /** @test */
    public function user_can_create_time_entry_via_api()
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/time-entries', [
                'project_id' => $project->id,
                'description' => 'Test work',
                'start_time' => now()->subHours(2)->toIso8601String(),
                'end_time' => now()->toIso8601String(),
                'is_billable' => true,
            ]);

        $response->assertCreated(); // 201 status code for resource creation
        $response->assertJsonStructure([
            'data' => [
                'id',
                'description',
                'duration',
                'amount',
            ],
        ]);
    }

    /** @test */
    public function user_can_get_active_timer_via_api()
    {
        $activeTimer = TimeEntry::factory()->running()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/time-entries/active');

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $activeTimer->id,
            ],
        ]);
    }

    /** @test */
    public function returns_404_when_no_active_timer_via_api()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/time-entries/active');

        $response->assertStatus(404);
    }

    /** @test */
    public function user_can_stop_timer_via_api()
    {
        $activeTimer = TimeEntry::factory()->running()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/v1/time-entries/{$activeTimer->id}/stop");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'end_time',
                'duration',
            ],
        ]);

        $this->assertNotNull($activeTimer->fresh()->end_time);
    }

    /** @test */
    public function user_cannot_access_other_users_time_entries()
    {
        $otherUser = User::factory()->create();
        $timeEntry = TimeEntry::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/time-entries/{$timeEntry->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_filter_time_entries_by_project()
    {
        $project1 = Project::factory()->create(['user_id' => $this->user->id]);
        $project2 = Project::factory()->create(['user_id' => $this->user->id]);
        
        TimeEntry::factory()->create(['user_id' => $this->user->id, 'project_id' => $project1->id]);
        TimeEntry::factory()->create(['user_id' => $this->user->id, 'project_id' => $project2->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/time-entries?project_id={$project1->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
