<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_view_their_projects()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertOk();
        $response->assertSee($project->name);
    }

    /** @test */
    public function user_cannot_view_other_users_project()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('projects.show', $otherProject));

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_create_project()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'client_id' => $client->id,
            'name' => 'Website Redesign',
            'description' => 'Complete website overhaul',
            'hourly_rate' => 175,
            'budget' => 5000,
            'status' => 'active',
            'start_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'client_id' => $client->id,
            'name' => 'Website Redesign',
            'hourly_rate' => 175,
        ]);
    }

    /** @test */
    public function user_can_update_their_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patch(route('projects.update', $project), [
            'client_id' => $project->client_id,
            'name' => 'Updated Project Name',
            'status' => 'completed',
            'hourly_rate' => 200,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project Name',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function user_cannot_update_other_users_project()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->patch(route('projects.update', $otherProject), [
            'client_id' => $otherProject->client_id,
            'name' => 'Hacked!',
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_delete_their_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('projects.destroy', $project));

        $response->assertRedirect();
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    /** @test */
    public function user_cannot_delete_other_users_project()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete(route('projects.destroy', $otherProject));

        $response->assertForbidden();
        $this->assertDatabaseHas('projects', ['id' => $otherProject->id]);
    }

    /** @test */
    public function validation_requires_project_name_and_client()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['name', 'client_id']);
    }

    /** @test */
    public function project_displays_client_relationship()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id, 'name' => 'Test Client']);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.show', $project));

        $response->assertOk();
        $response->assertSee('Test Client');
    }
}
