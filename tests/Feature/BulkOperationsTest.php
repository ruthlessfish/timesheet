<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_bulk_delete_time_entries(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        $entry1 = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'is_invoiced' => false,
        ]);
        $entry2 = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'is_invoiced' => false,
        ]);

        $response = $this->actingAs($user)->postJson(route('time-entries.bulk-delete'), [
            'ids' => [$entry1->id, $entry2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'deleted' => 2]);
        $this->assertDatabaseMissing('time_entries', ['id' => $entry1->id]);
        $this->assertDatabaseMissing('time_entries', ['id' => $entry2->id]);
    }

    public function test_bulk_delete_prevents_deleting_invoiced_entries(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        $invoicedEntry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'is_invoiced' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('time-entries.bulk-delete'), [
            'ids' => [$invoicedEntry->id],
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
        $this->assertDatabaseHas('time_entries', ['id' => $invoicedEntry->id]);
    }

    public function test_bulk_delete_requires_ownership(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $client = Client::factory()->create(['user_id' => $user2->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user2->id,
        ]);

        $entry = TimeEntry::factory()->create([
            'user_id' => $user2->id,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($user1)->postJson(route('time-entries.bulk-delete'), [
            'ids' => [$entry->id],
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id]);
    }

    public function test_bulk_edit_form_loads_correctly(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        $entry1 = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
        $entry2 = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($user)->get(route('time-entries.bulk-edit', [
            'ids' => "{$entry1->id},{$entry2->id}",
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('time-entries.bulk-edit');
        $response->assertViewHas('entries');
        $response->assertViewHas('projects');
    }

    public function test_bulk_edit_redirects_with_no_ids(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('time-entries.bulk-edit', ['ids' => '']));

        $response->assertRedirect(route('time-entries.index'));
        $response->assertSessionHas('error');
    }

    public function test_user_can_bulk_update_project(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project1 = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);
        $project2 = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        $entry1 = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project1->id,
        ]);
        $entry2 = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project1->id,
        ]);

        $response = $this->actingAs($user)->patch(route('time-entries.bulk-update'), [
            'ids' => [$entry1->id, $entry2->id],
            'project_id' => $project2->id,
        ]);

        $response->assertRedirect(route('time-entries.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('time_entries', [
            'id' => $entry1->id,
            'project_id' => $project2->id,
        ]);
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry2->id,
            'project_id' => $project2->id,
        ]);
    }

    public function test_user_can_bulk_update_billable_status(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        $entry1 = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'is_billable' => true,
        ]);
        $entry2 = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'is_billable' => true,
        ]);

        $response = $this->actingAs($user)->patch(route('time-entries.bulk-update'), [
            'ids' => [$entry1->id, $entry2->id],
            'is_billable' => false,
        ]);

        $response->assertRedirect(route('time-entries.index'));

        $this->assertDatabaseHas('time_entries', [
            'id' => $entry1->id,
            'is_billable' => false,
        ]);
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry2->id,
            'is_billable' => false,
        ]);
    }

    public function test_user_can_bulk_update_hourly_rate(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        $entry1 = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'hourly_rate' => null,
        ]);
        $entry2 = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'hourly_rate' => null,
        ]);

        $response = $this->actingAs($user)->patch(route('time-entries.bulk-update'), [
            'ids' => [$entry1->id, $entry2->id],
            'hourly_rate' => 125.50,
        ]);

        $response->assertRedirect(route('time-entries.index'));

        $this->assertDatabaseHas('time_entries', [
            'id' => $entry1->id,
            'hourly_rate' => 125.50,
        ]);
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry2->id,
            'hourly_rate' => 125.50,
        ]);
    }

    public function test_bulk_update_requires_ownership(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $client = Client::factory()->create(['user_id' => $user2->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user2->id,
        ]);

        $entry = TimeEntry::factory()->create([
            'user_id' => $user2->id,
            'project_id' => $project->id,
            'is_billable' => true,
        ]);

        $response = $this->actingAs($user1)->patch(route('time-entries.bulk-update'), [
            'ids' => [$entry->id],
            'is_billable' => false,
        ]);

        $response->assertRedirect(route('time-entries.index'));

        // Entry should not be updated
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'is_billable' => true,
        ]);
    }

    public function test_bulk_update_validates_hourly_rate(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        $entry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($user)->patch(route('time-entries.bulk-update'), [
            'ids' => [$entry->id],
            'hourly_rate' => -10,
        ]);

        $response->assertSessionHasErrors('hourly_rate');
    }
}
