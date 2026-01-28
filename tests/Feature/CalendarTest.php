<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('calendar.index'));

        $response->assertStatus(200);
        $response->assertViewIs('calendar.index');
    }

    public function test_calendar_page_redirects_for_guest(): void
    {
        $response = $this->get(route('calendar.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_calendar_entries_endpoint_returns_json(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        $timeEntry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'start_time' => now()->subHours(2),
            'end_time' => now()->subHour(),
            'duration' => 60,
        ]);

        $response = $this->actingAs($user)->getJson(route('calendar.entries'));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $timeEntry->id,
        ]);
    }

    public function test_calendar_entries_filters_by_date_range(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        // Create entry in range
        $inRangeEntry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'start_time' => now(),
            'end_time' => now()->addHour(),
            'duration' => 60,
        ]);

        // Create entry out of range
        $outOfRangeEntry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'start_time' => now()->addMonths(2),
            'end_time' => now()->addMonths(2)->addHour(),
            'duration' => 60,
        ]);

        $response = $this->actingAs($user)->getJson(route('calendar.entries', [
            'start' => now()->subDay()->toDateString(),
            'end' => now()->addDay()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $inRangeEntry->id]);
        $response->assertJsonMissing(['id' => $outOfRangeEntry->id]);
    }

    public function test_calendar_entries_only_returns_users_own_entries(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $client1 = Client::factory()->create(['user_id' => $user1->id]);
        $project1 = Project::factory()->create([
            'client_id' => $client1->id,
            'user_id' => $user1->id,
        ]);

        $client2 = Client::factory()->create(['user_id' => $user2->id]);
        $project2 = Project::factory()->create([
            'client_id' => $client2->id,
            'user_id' => $user2->id,
        ]);

        $user1Entry = TimeEntry::factory()->create([
            'user_id' => $user1->id,
            'project_id' => $project1->id,
        ]);

        $user2Entry = TimeEntry::factory()->create([
            'user_id' => $user2->id,
            'project_id' => $project2->id,
        ]);

        $response = $this->actingAs($user1)->getJson(route('calendar.entries'));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $user1Entry->id]);
        $response->assertJsonMissing(['id' => $user2Entry->id]);
    }

    public function test_calendar_entries_includes_project_and_client_data(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Client',
        ]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'name' => 'Test Project',
        ]);

        $timeEntry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'description' => 'Test Description',
        ]);

        $response = $this->actingAs($user)->getJson(route('calendar.entries'));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'projectName' => 'Test Project',
            'clientName' => 'Test Client',
        ]);
    }

    public function test_calendar_entries_calculates_colors_consistently(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        $timeEntry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('calendar.entries'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'title',
                'start',
                'end',
                'backgroundColor',
                'borderColor',
                'extendedProps' => [
                    'projectName',
                    'clientName',
                    'duration',
                    'amount',
                    'isBillable',
                ],
            ],
        ]);

        // Ensure backgroundColor and borderColor match
        $data = $response->json();
        $this->assertEquals($data[0]['backgroundColor'], $data[0]['borderColor']);
    }
}
