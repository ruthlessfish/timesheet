<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_view_their_clients()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee($client->name);
    }

    /** @test */
    public function user_cannot_view_other_users_client()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherClient = Client::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('clients.show', $otherClient));

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_create_client()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('clients.store'), [
            'name' => 'Acme Corp',
            'email' => 'contact@acme.com',
            'phone' => '555-1234',
            'company' => 'Acme Corporation',
            'address' => '123 Main St',
            'hourly_rate' => 150,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', [
            'user_id' => $user->id,
            'name' => 'Acme Corp',
            'email' => 'contact@acme.com',
            'hourly_rate' => 150,
        ]);
    }

    /** @test */
    public function user_can_update_their_client()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patch(route('clients.update', $client), [
            'name' => 'Updated Name',
            'email' => $client->email,
            'hourly_rate' => 200,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Name',
            'hourly_rate' => 200,
        ]);
    }

    /** @test */
    public function user_cannot_update_other_users_client()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherClient = Client::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->patch(route('clients.update', $otherClient), [
            'name' => 'Hacked!',
            'email' => $otherClient->email,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_delete_their_client()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('clients.destroy', $client));

        $response->assertRedirect();
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    /** @test */
    public function user_cannot_delete_other_users_client()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherClient = Client::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete(route('clients.destroy', $otherClient));

        $response->assertForbidden();
        $this->assertDatabaseHas('clients', ['id' => $otherClient->id]);
    }

    /** @test */
    public function validation_requires_client_name()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('clients.store'), [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors('name');
    }
}
