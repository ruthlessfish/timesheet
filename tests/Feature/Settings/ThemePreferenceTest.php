<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_theme_preference(): void
    {
        $user = User::factory()->create(['theme_preference' => 'light']);

        $response = $this->actingAs($user)
            ->patch('/settings/theme', [
                'theme' => 'dark',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'theme_preference' => 'dark',
        ]);
    }

    public function test_user_can_set_theme_to_system_preference(): void
    {
        $user = User::factory()->create(['theme_preference' => 'light']);

        $response = $this->actingAs($user)
            ->patch('/settings/theme', [
                'theme' => 'system',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'theme_preference' => 'system',
        ]);
    }

    public function test_theme_preference_validation(): void
    {
        $user = User::factory()->create();

        // Invalid theme value
        $response = $this->actingAs($user)
            ->patch('/settings/theme', [
                'theme' => 'invalid',
            ]);

        $response->assertSessionHasErrors('theme');

        // Missing theme parameter
        $response = $this->actingAs($user)
            ->patch('/settings/theme', []);

        $response->assertSessionHasErrors('theme');
    }

    public function test_theme_persists_across_sessions(): void
    {
        $user = User::factory()->create(['theme_preference' => 'light']);

        // Update theme
        $this->actingAs($user)
            ->patch('/settings/theme', [
                'theme' => 'dark',
            ]);

        // Simulate new session - logout and login
        $this->post('/logout');
        
        // Login again
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password', // Default factory password
        ]);

        // Fresh user instance from database
        $freshUser = User::find($user->id);
        
        $this->assertEquals('dark', $freshUser->theme_preference);
    }

    public function test_unauthorized_user_cannot_update_theme(): void
    {
        $response = $this->patch('/settings/theme', [
            'theme' => 'dark',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_default_theme_preference_is_system(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('system', $user->theme_preference);
    }

    public function test_theme_toggle_responds_immediately(): void
    {
        $user = User::factory()->create(['theme_preference' => 'light']);

        $response = $this->actingAs($user)
            ->patch('/settings/theme', [
                'theme' => 'dark',
            ]);

        // Verify response is successful (redirect)
        $response->assertStatus(302);
        
        // Verify database updated immediately
        $user->refresh();
        $this->assertEquals('dark', $user->theme_preference);
    }
}
