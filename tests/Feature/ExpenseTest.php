<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_view_their_expenses()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $expense = Expense::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSee($expense->description);
    }

    #[Test]
    public function user_can_view_create_expense_form()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('expenses.create'));

        $response->assertOk();
    }

    #[Test]
    public function user_can_create_an_expense()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'client_id' => $client->id,
            'description' => 'Software License',
            'amount' => 99.99,
            'expense_date' => now()->format('Y-m-d'),
            'category' => 'Software',
            'is_billable' => true,
        ]);

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'client_id' => $client->id,
            'description' => 'Software License',
            'amount' => 99.99,
            'category' => 'Software',
            'is_billable' => true,
            'is_invoiced' => false,
        ]);
    }

    #[Test]
    public function user_can_create_non_billable_expense()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'client_id' => $client->id,
            'description' => 'Internal Tool',
            'amount' => 50.00,
            'expense_date' => now()->format('Y-m-d'),
            'is_billable' => false,
        ]);

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'description' => 'Internal Tool',
            'is_billable' => false,
        ]);
    }

    #[Test]
    public function user_can_view_their_expense()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $expense = Expense::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('expenses.show', $expense));

        $response->assertOk();
        $response->assertSee($expense->description);
    }

    #[Test]
    public function user_cannot_view_other_users_expense()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $otherUser->id]);
        $expense = Expense::factory()->create(['user_id' => $otherUser->id, 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('expenses.show', $expense));

        $response->assertForbidden();
    }

    #[Test]
    public function user_can_update_their_expense()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $expense = Expense::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);

        $response = $this->actingAs($user)->put(route('expenses.update', $expense), [
            'client_id' => $client->id,
            'description' => 'Updated Description',
            'amount' => 150.00,
            'expense_date' => now()->format('Y-m-d'),
            'category' => 'Travel',
            'is_billable' => true,
        ]);

        $response->assertRedirect(route('expenses.show', $expense));
        $expense->refresh();
        $this->assertEquals('Updated Description', $expense->description);
        $this->assertEquals(150.00, $expense->amount);
        $this->assertEquals('Travel', $expense->category);
    }

    #[Test]
    public function user_cannot_update_other_users_expense()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $otherUser->id]);
        $expense = Expense::factory()->create(['user_id' => $otherUser->id, 'client_id' => $client->id]);

        $response = $this->actingAs($user)->put(route('expenses.update', $expense), [
            'client_id' => $client->id,
            'description' => 'Hacked',
            'amount' => 1.00,
            'expense_date' => now()->format('Y-m-d'),
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function user_can_delete_their_expense()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $expense = Expense::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);

        $response = $this->actingAs($user)->delete(route('expenses.destroy', $expense));

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    #[Test]
    public function user_cannot_delete_other_users_expense()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $otherUser->id]);
        $expense = Expense::factory()->create(['user_id' => $otherUser->id, 'client_id' => $client->id]);

        $response = $this->actingAs($user)->delete(route('expenses.destroy', $expense));

        $response->assertForbidden();
    }

    #[Test]
    public function expense_requires_description_and_amount()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'client_id' => $client->id,
            'expense_date' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['description', 'amount']);
    }

    #[Test]
    public function expense_amount_must_be_positive()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'client_id' => $client->id,
            'description' => 'Test',
            'amount' => 0,
            'expense_date' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('amount');
    }

    #[Test]
    public function invoice_can_include_expenses()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'amount' => 100.00,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'client_id' => $client->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'tax_rate' => 0,
            'status' => 'draft',
            'expenses' => [$expense->id],
        ]);

        $response->assertRedirect();
        $expense->refresh();
        $this->assertTrue($expense->is_invoiced);

        $invoice = Invoice::where('user_id', $user->id)->first();
        $this->assertCount(1, $invoice->items);
        $this->assertEquals($expense->id, $invoice->items->first()->expense_id);
        $this->assertEquals(100.00, $invoice->subtotal);
    }

    #[Test]
    public function invoice_can_include_both_time_entries_and_expenses()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id, 'hourly_rate' => 100]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'hourly_rate' => null,
        ]);

        $timeEntry = TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'hourly_rate' => null,
            'duration' => 60,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'amount' => 50.00,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'client_id' => $client->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'tax_rate' => 10,
            'status' => 'draft',
            'time_entries' => [$timeEntry->id],
            'expenses' => [$expense->id],
        ]);

        $response->assertRedirect();
        $invoice = Invoice::where('user_id', $user->id)->first();

        $this->assertCount(2, $invoice->items);
        $this->assertEquals(150.00, $invoice->subtotal);
        $this->assertEquals(15.00, $invoice->tax_amount);
        $this->assertEquals(165.00, $invoice->total);
    }

    #[Test]
    public function deleting_invoice_unmarks_expenses()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $expense = Expense::factory()->invoiced()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'amount' => 100.00,
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
        ]);

        $invoice->items()->create([
            'expense_id' => $expense->id,
            'description' => $expense->description,
            'quantity' => 1,
            'rate' => $expense->amount,
            'amount' => $expense->amount,
        ]);

        $this->actingAs($user)->delete(route('invoices.destroy', $invoice));

        $expense->refresh();
        $this->assertFalse($expense->is_invoiced);
    }

    #[Test]
    public function unbilled_expenses_shown_on_invoice_create()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $unbilledExpense = Expense::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $invoicedExpense = Expense::factory()->invoiced()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'is_billable' => true,
        ]);

        $response = $this->actingAs($user)->get(route('invoices.create', ['client_id' => $client->id]));

        $response->assertOk();
        $response->assertViewHas('unbilledExpenses', function ($expenses) use ($unbilledExpense, $invoicedExpense) {
            return $expenses->contains($unbilledExpense) && ! $expenses->contains($invoicedExpense);
        });
    }

    #[Test]
    public function non_billable_expenses_not_shown_on_invoice_create()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $billableExpense = Expense::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $nonBillableExpense = Expense::factory()->nonBillable()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'is_invoiced' => false,
        ]);

        $response = $this->actingAs($user)->get(route('invoices.create', ['client_id' => $client->id]));

        $response->assertViewHas('unbilledExpenses', function ($expenses) use ($billableExpense, $nonBillableExpense) {
            return $expenses->contains($billableExpense) && ! $expenses->contains($nonBillableExpense);
        });
    }

    #[Test]
    public function guest_cannot_access_expenses()
    {
        $response = $this->get(route('expenses.index'));

        $response->assertRedirect(route('login'));
    }
}
