<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_view_their_invoices()
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('invoices.index'));

        $response->assertOk();
        $response->assertSee($invoice->invoice_number);
    }

    #[Test]
    public function user_cannot_view_other_users_invoices()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherInvoice = Invoice::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('invoices.show', $otherInvoice));

        $response->assertForbidden();
    }

    #[Test]
    public function user_can_create_invoice_from_unbilled_time_entries()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);
        
        $timeEntry1 = TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);
        $timeEntry2 = TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'client_id' => $client->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'tax_rate' => 10,
            'status' => 'draft',
            'time_entries' => [$timeEntry1->id, $timeEntry2->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'user_id' => $user->id,
            'client_id' => $client->id,
        ]);
    }

    #[Test]
    public function creating_invoice_marks_time_entries_as_invoiced()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);
        
        $timeEntry = TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $this->actingAs($user)->post(route('invoices.store'), [
            'client_id' => $client->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'tax_rate' => 0,
            'status' => 'draft',
            'time_entries' => [$timeEntry->id],
        ]);

        $timeEntry->refresh();
        $this->assertTrue($timeEntry->is_invoiced);
    }

    #[Test]
    public function only_unbilled_entries_are_available_for_invoicing()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);
        
        $unbilledEntry = TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);
        
        $billedEntry = TimeEntry::factory()->invoiced()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'is_billable' => true,
        ]);

        $response = $this->actingAs($user)->get(route('invoices.create', ['client_id' => $client->id]));

        $response->assertOk();
        // The view should have unbilled entries available
        $response->assertViewHas('unbilledTimeEntries', function ($entries) use ($unbilledEntry, $billedEntry) {
            return $entries->contains($unbilledEntry) && !$entries->contains($billedEntry);
        });
    }

    #[Test]
    public function non_billable_entries_are_not_available_for_invoicing()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);
        
        $billableEntry = TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);
        
        $nonBillableEntry = TimeEntry::factory()->nonBillable()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'is_invoiced' => false,
        ]);

        $response = $this->actingAs($user)->get(route('invoices.create', ['client_id' => $client->id]));

        $response->assertViewHas('unbilledTimeEntries', function ($entries) use ($billableEntry, $nonBillableEntry) {
            return $entries->contains($billableEntry) && !$entries->contains($nonBillableEntry);
        });
    }

    #[Test]
    public function running_timers_are_not_available_for_invoicing()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);
        
        $completedEntry = TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);
        
        $runningEntry = TimeEntry::factory()->running()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $response = $this->actingAs($user)->get(route('invoices.create', ['client_id' => $client->id]));

        $response->assertViewHas('unbilledTimeEntries', function ($entries) use ($completedEntry, $runningEntry) {
            return $entries->contains($completedEntry) && !$entries->contains($runningEntry);
        });
    }

    #[Test]
    public function invoice_totals_are_calculated_on_creation()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id, 'hourly_rate' => 100]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'hourly_rate' => null, // Will use client rate
        ]);
        
        // 2 hours at $100/hr = $200
        $timeEntry = TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'hourly_rate' => null, // Will cascade to client rate
            'start_time' => now()->subHours(2),
            'end_time' => now(),
            'duration' => 120, // Explicitly set 2 hours
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $this->actingAs($user)->post(route('invoices.store'), [
            'client_id' => $client->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'tax_rate' => 10,
            'status' => 'draft',
            'time_entries' => [$timeEntry->id],
        ]);

        $invoice = Invoice::where('user_id', $user->id)->first();
        $this->assertEquals(200.00, $invoice->subtotal);
        $this->assertEquals(20.00, $invoice->tax_amount);
        $this->assertEquals(220.00, $invoice->total);
    }

    #[Test]
    public function user_can_update_invoice()
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

        $response = $this->actingAs($user)->patch(route('invoices.update', $invoice), [
            'client_id' => $invoice->client_id,
            'issue_date' => $invoice->issue_date->format('Y-m-d'),
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'tax_rate' => 15,
            'status' => 'sent',
            'notes' => 'Updated notes',
        ]);

        $response->assertRedirect();
        $invoice->refresh();
        $this->assertEquals('sent', $invoice->status);
        $this->assertEquals('Updated notes', $invoice->notes);
    }

    #[Test]
    public function user_cannot_update_other_users_invoice()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherInvoice = Invoice::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->patch(route('invoices.update', $otherInvoice), [
            'client_id' => $otherInvoice->client_id,
            'issue_date' => $otherInvoice->issue_date->format('Y-m-d'),
            'due_date' => $otherInvoice->due_date->format('Y-m-d'),
            'status' => 'paid',
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function deleting_invoice_unmarks_time_entries()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);
        
        $timeEntry = TimeEntry::factory()->invoiced()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
        ]);

        $invoice->items()->create([
            'time_entry_id' => $timeEntry->id,
            'description' => 'Test',
            'quantity' => 1,
            'rate' => 100,
            'amount' => 100,
        ]);

        $this->actingAs($user)->delete(route('invoices.destroy', $invoice));

        $timeEntry->refresh();
        $this->assertFalse($timeEntry->is_invoiced);
    }

    #[Test]
    public function user_can_generate_pdf_for_their_invoice()
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    #[Test]
    public function user_cannot_generate_pdf_for_other_users_invoice()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherInvoice = Invoice::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('invoices.pdf', $otherInvoice));

        $response->assertForbidden();
    }

    #[Test]
    public function validation_requires_due_date_after_issue_date()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'client_id' => $client->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->subDay()->format('Y-m-d'),
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors('due_date');
    }
}
