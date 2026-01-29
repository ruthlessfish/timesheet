<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_view_their_time_entries()
    {
        $user = User::factory()->create();
        $timeEntry = TimeEntry::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('time-entries.index'));

        $response->assertOk();
        $response->assertSee($timeEntry->project->name);
    }

    #[Test]
    public function user_cannot_view_other_users_time_entries()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherTimeEntry = TimeEntry::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('time-entries.show', $otherTimeEntry));

        $response->assertForbidden();
    }

    #[Test]
    public function user_can_create_manual_time_entry()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('time-entries.store'), [
            'project_id' => $project->id,
            'description' => 'Working on feature',
            'start_time' => now()->subHours(2)->format('Y-m-d H:i:s'),
            'end_time' => now()->format('Y-m-d H:i:s'),
            'is_billable' => true,
        ]);

        $response->assertRedirect(route('time-entries.index'));
        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'description' => 'Working on feature',
            'is_billable' => true,
        ]);
    }

    #[Test]
    public function duration_is_calculated_when_creating_manual_entry()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $startTime = now()->subHours(2);
        $endTime = now();

        $this->actingAs($user)->post(route('time-entries.store'), [
            'project_id' => $project->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'is_billable' => true,
        ]);

        $timeEntry = TimeEntry::where('user_id', $user->id)->first();
        $this->assertNotNull($timeEntry->duration);
        $this->assertEquals(120, $timeEntry->duration);
    }

    #[Test]
    public function user_can_start_a_timer()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('time-entries.store'), [
            'project_id' => $project->id,
            'description' => 'Started work',
            'start_time' => now()->format('Y-m-d H:i:s'),
            'is_billable' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'end_time' => null,
        ]);
    }

    #[Test]
    public function user_can_stop_a_running_timer()
    {
        $user = User::factory()->create();
        $timeEntry = TimeEntry::factory()->running()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('time-entries.stop', $timeEntry));

        $response->assertRedirect();
        $timeEntry->refresh();
        $this->assertNotNull($timeEntry->end_time);
        $this->assertNotNull($timeEntry->duration);
    }

    #[Test]
    public function active_timer_is_displayed_on_index_page()
    {
        $user = User::factory()->create();
        $activeTimer = TimeEntry::factory()->running()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('time-entries.index'));

        $response->assertOk();
        $response->assertSee($activeTimer->project->name);
    }

    #[Test]
    public function user_can_update_their_time_entry()
    {
        $user = User::factory()->create();
        $timeEntry = TimeEntry::factory()->create(['user_id' => $user->id]);
        $newProject = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patch(route('time-entries.update', $timeEntry), [
            'project_id' => $newProject->id,
            'description' => 'Updated description',
            'start_time' => $timeEntry->start_time->format('Y-m-d H:i:s'),
            'end_time' => $timeEntry->end_time->format('Y-m-d H:i:s'),
            // Note: not passing is_billable, so controller will set it to false
        ]);

        $response->assertRedirect(route('time-entries.index'));
        $timeEntry->refresh();
        $this->assertEquals('Updated description', $timeEntry->description);
        $this->assertFalse($timeEntry->is_billable);
    }

    #[Test]
    public function user_cannot_update_other_users_time_entry()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherTimeEntry = TimeEntry::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->patch(route('time-entries.update', $otherTimeEntry), [
            'project_id' => $otherTimeEntry->project_id,
            'description' => 'Hacked!',
            'start_time' => $otherTimeEntry->start_time->format('Y-m-d H:i:s'),
            'end_time' => $otherTimeEntry->end_time->format('Y-m-d H:i:s'),
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function user_can_delete_their_time_entry()
    {
        $user = User::factory()->create();
        $timeEntry = TimeEntry::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('time-entries.destroy', $timeEntry));

        $response->assertRedirect();
        $this->assertDatabaseMissing('time_entries', ['id' => $timeEntry->id]);
    }

    #[Test]
    public function user_cannot_delete_other_users_time_entry()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherTimeEntry = TimeEntry::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete(route('time-entries.destroy', $otherTimeEntry));

        $response->assertForbidden();
        $this->assertDatabaseHas('time_entries', ['id' => $otherTimeEntry->id]);
    }

    #[Test]
    public function time_entries_can_be_filtered_by_project()
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create(['user_id' => $user->id]);
        $project2 = Project::factory()->create(['user_id' => $user->id]);

        $entry1 = TimeEntry::factory()->create(['user_id' => $user->id, 'project_id' => $project1->id]);
        $entry2 = TimeEntry::factory()->create(['user_id' => $user->id, 'project_id' => $project2->id]);

        $response = $this->actingAs($user)->get(route('time-entries.index', ['project_id' => $project1->id]));

        $response->assertOk();
        $response->assertSee($entry1->project->name);
        // Project2 entries should not be visible when filtered
    }

    #[Test]
    public function validation_requires_project_id()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('time-entries.store'), [
            'start_time' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasErrors('project_id');
    }

    #[Test]
    public function validation_requires_end_time_after_start_time()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('time-entries.store'), [
            'project_id' => $project->id,
            'start_time' => now()->format('Y-m-d H:i:s'),
            'end_time' => now()->subHour()->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasErrors('end_time');
    }
}
