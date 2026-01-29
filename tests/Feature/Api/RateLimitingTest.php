<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_endpoints_are_rate_limited()
    {
        $user = User::factory()->create();

        // Make 60 requests (the limit)
        for ($i = 0; $i < 60; $i++) {
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/v1/user');

            $response->assertStatus(200);
        }

        // 61st request should be rate limited
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user');

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_auth_endpoints_have_stricter_rate_limiting()
    {
        // Make 5 login attempts (the limit for auth endpoints)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

            // Will fail auth, but not rate limited yet
            $this->assertContains($response->status(), [401, 422]);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_rate_limit_is_per_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1 makes 60 requests
        for ($i = 0; $i < 60; $i++) {
            $response = $this->actingAs($user1, 'sanctum')
                ->getJson('/api/v1/user');

            $response->assertStatus(200);
        }

        // User 2 should still be able to make requests
        $response = $this->actingAs($user2, 'sanctum')
            ->getJson('/api/v1/user');

        $response->assertStatus(200);
    }

    public function test_rate_limit_headers_are_present()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user');

        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining');
    }
}
