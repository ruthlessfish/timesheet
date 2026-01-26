<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
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
    public function user_can_list_their_invoices_via_api()
    {
        Invoice::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/invoices');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'invoice_number',
                    'issue_date',
                    'due_date',
                    'status',
                    'subtotal',
                    'tax_rate',
                    'tax_amount',
                    'total',
                ],
            ],
        ]);
    }

    /** @test */
    public function user_can_create_invoice_from_time_entries_via_api()
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
        ]);
        
        $timeEntry = TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/invoices', [
                'client_id' => $client->id,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'status' => 'draft',
                'time_entries' => [$timeEntry->id],
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'invoice_number',
                'total',
                'items',
            ],
        ]);

        $this->assertTrue($timeEntry->fresh()->is_invoiced);
    }

    /** @test */
    public function user_can_get_unbilled_entries_for_client_via_api()
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
        ]);
        
        $unbilledEntry = TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/clients/{$client->id}/unbilled-entries");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'description',
                    'amount',
                ],
            ],
        ]);
    }

    /** @test */
    public function user_cannot_access_other_users_invoices()
    {
        $otherUser = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/invoices/{$invoice->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_delete_invoice_via_api()
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/v1/invoices/{$invoice->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }
}
