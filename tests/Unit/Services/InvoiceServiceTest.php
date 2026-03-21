<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\BillingService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $invoiceService;

    private BillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billingService = new BillingService;
        $this->invoiceService = new InvoiceService($this->billingService);
    }

    public function test_creates_invoice_from_time_entries()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create(['hourly_rate' => 100]);
        $project = Project::factory()->for($client)->for($user)->create(['hourly_rate' => null]);

        $entry1 = TimeEntry::factory()->for($project)->for($user)->create([
            'hourly_rate' => null,
            'duration' => 60,
            'is_billable' => true,
            'is_invoiced' => false,
            'end_time' => now(),
        ]);

        $entry2 = TimeEntry::factory()->for($project)->for($user)->create([
            'hourly_rate' => null,
            'duration' => 120,
            'is_billable' => true,
            'is_invoiced' => false,
            'end_time' => now(),
        ]);

        $invoice = $this->invoiceService->createFromTimeEntries(
            userId: $user->id,
            clientId: $client->id,
            timeEntryIds: [$entry1->id, $entry2->id],
            data: [
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'tax_rate' => 10,
                'status' => 'draft',
            ]
        );

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($client->id, $invoice->client_id);
        $this->assertEquals(10, $invoice->tax_rate);
        $this->assertCount(2, $invoice->items);

        // Verify totals
        $this->assertEquals(300, $invoice->subtotal); // (1 + 2) * 100
        $this->assertEquals(30, $invoice->tax_amount); // 300 * 0.10
        $this->assertEquals(330, $invoice->total); // 300 + 30
    }

    public function test_marks_time_entries_as_invoiced()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $project = Project::factory()->for($client)->for($user)->create();

        $entry = TimeEntry::factory()->for($project)->for($user)->create([
            'is_invoiced' => false,
            'end_time' => now(),
        ]);

        $this->assertFalse($entry->is_invoiced);

        $this->invoiceService->createFromTimeEntries(
            userId: $user->id,
            clientId: $client->id,
            timeEntryIds: [$entry->id],
            data: [
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
            ]
        );

        $this->assertTrue($entry->fresh()->is_invoiced);
    }

    public function test_creates_invoice_without_time_entries()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $invoice = $this->invoiceService->createFromTimeEntries(
            userId: $user->id,
            clientId: $client->id,
            timeEntryIds: [],
            data: [
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
            ]
        );

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertCount(0, $invoice->items);
        $this->assertEquals(0, $invoice->subtotal);
    }

    public function test_updates_invoice_totals()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $invoice = Invoice::factory()->for($client)->for($user)->create([
            'subtotal' => 0,
            'total' => 0,
            'tax_rate' => 0,
        ]);

        // Manually add an item
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Test item',
            'quantity' => 2,
            'rate' => 100,
            'amount' => 200,
        ]);

        $this->assertEquals(200, $invoice->subtotal);
        $this->assertEquals(0, $invoice->tax_amount);
        $this->assertEquals(200, $invoice->total);
    }

    public function test_updates_invoice_data()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $invoice = Invoice::factory()->for($client)->for($user)->create([
            'status' => 'draft',
        ]);

        $updated = $this->invoiceService->updateInvoice($invoice, [
            'status' => 'sent',
            'notes' => 'Payment terms: Net 30',
        ]);

        $this->assertEquals('sent', $updated->status);
        $this->assertEquals('Payment terms: Net 30', $updated->notes);
    }

    public function test_deletes_invoice_and_unmarks_time_entries()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $project = Project::factory()->for($client)->for($user)->create();

        $entry1 = TimeEntry::factory()->for($project)->for($user)->create(['is_invoiced' => false]);
        $entry2 = TimeEntry::factory()->for($project)->for($user)->create(['is_invoiced' => false]);

        $invoice = $this->invoiceService->createFromTimeEntries(
            userId: $user->id,
            clientId: $client->id,
            timeEntryIds: [$entry1->id, $entry2->id],
            data: [
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
            ]
        );

        $this->assertTrue($entry1->fresh()->is_invoiced);
        $this->assertTrue($entry2->fresh()->is_invoiced);

        $this->invoiceService->deleteInvoice($invoice);

        $this->assertNull(Invoice::find($invoice->id));
        $this->assertFalse($entry1->fresh()->is_invoiced);
        $this->assertFalse($entry2->fresh()->is_invoiced);
    }

    public function test_gets_unbilled_entries_for_client()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $project = Project::factory()->for($client)->for($user)->create();

        // Billable, unbilled entry
        $unbilled = TimeEntry::factory()->for($project)->for($user)->create([
            'is_billable' => true,
            'is_invoiced' => false,
            'end_time' => now(),
        ]);

        // Already invoiced
        TimeEntry::factory()->for($project)->for($user)->create([
            'is_billable' => true,
            'is_invoiced' => true,
            'end_time' => now(),
        ]);

        $entries = $this->invoiceService->getUnbilledEntriesForClient($client->id, $user->id);

        $this->assertCount(1, $entries);
        $this->assertEquals($unbilled->id, $entries->first()->id);
    }

    public function test_calculates_preview_totals()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create(['hourly_rate' => 100]);
        $project = Project::factory()->for($client)->for($user)->create(['hourly_rate' => null]);

        $entry1 = TimeEntry::factory()->for($project)->for($user)->create([
            'hourly_rate' => null,
            'duration' => 60, // 1 hour
        ]);
        $entry2 = TimeEntry::factory()->for($project)->for($user)->create([
            'hourly_rate' => null,
            'duration' => 120, // 2 hours
        ]);

        $preview = $this->invoiceService->calculatePreviewTotals(
            timeEntryIds: [$entry1->id, $entry2->id],
            taxRate: 10
        );

        $this->assertEquals(300, $preview['subtotal']); // (1 + 2) * 100
        $this->assertEquals(10, $preview['tax_rate']);
        $this->assertEquals(30, $preview['tax_amount']); // 300 * 0.10
        $this->assertEquals(330, $preview['total']); // 300 + 30
        $this->assertEquals(3, $preview['total_hours']); // 1 + 2
        $this->assertEquals(2, $preview['item_count']);
    }

    public function test_marks_invoice_as_sent()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $invoice = Invoice::factory()->for($client)->for($user)->create(['status' => 'draft']);

        $updated = $this->invoiceService->markAsSent($invoice);

        $this->assertEquals('sent', $updated->status);
    }

    public function test_marks_invoice_as_paid()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $invoice = Invoice::factory()->for($client)->for($user)->create(['status' => 'sent']);

        $updated = $this->invoiceService->markAsPaid($invoice);

        $this->assertEquals('paid', $updated->status);
    }

    public function test_marks_invoice_as_overdue()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $invoice = Invoice::factory()->for($client)->for($user)->create(['status' => 'sent']);

        $updated = $this->invoiceService->markAsOverdue($invoice);

        $this->assertEquals('overdue', $updated->status);
    }

    public function test_generates_pdf()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $invoice = Invoice::factory()->for($client)->for($user)->create();

        $pdf = $this->invoiceService->generatePDF($invoice);

        $this->assertNotNull($pdf);
        // PDF generation returns a Dompdf instance
        $this->assertInstanceOf(\Barryvdh\DomPDF\PDF::class, $pdf);
    }

    public function test_only_includes_user_time_entries()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $project = Project::factory()->for($client)->for($user)->create();

        // User's entry
        $userEntry = TimeEntry::factory()->for($project)->for($user)->create();

        // Other user's entry (shouldn't be included)
        $otherEntry = TimeEntry::factory()->for($project)->for($otherUser)->create();

        $invoice = $this->invoiceService->createFromTimeEntries(
            userId: $user->id,
            clientId: $client->id,
            timeEntryIds: [$userEntry->id, $otherEntry->id],
            data: [
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
            ]
        );

        // Should only have 1 item (user's entry)
        $this->assertCount(1, $invoice->items);
        $this->assertEquals($userEntry->id, $invoice->items->first()->time_entry_id);
    }

    public function test_creates_invoice_with_expenses()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $expense = Expense::factory()->for($client)->for($user)->create([
            'amount' => 150.00,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $invoice = $this->invoiceService->createFromTimeEntries(
            userId: $user->id,
            clientId: $client->id,
            timeEntryIds: [],
            data: [
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'tax_rate' => 0,
            ],
            expenseIds: [$expense->id],
        );

        $this->assertCount(1, $invoice->items);
        $this->assertEquals($expense->id, $invoice->items->first()->expense_id);
        $this->assertEquals(150.00, $invoice->subtotal);
    }

    public function test_creates_invoice_with_time_entries_and_expenses()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create(['hourly_rate' => 100]);
        $project = Project::factory()->for($client)->for($user)->create(['hourly_rate' => null]);

        $entry = TimeEntry::factory()->for($project)->for($user)->create([
            'hourly_rate' => null,
            'duration' => 60,
            'is_billable' => true,
            'is_invoiced' => false,
            'end_time' => now(),
        ]);

        $expense = Expense::factory()->for($client)->for($user)->create([
            'amount' => 50.00,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $invoice = $this->invoiceService->createFromTimeEntries(
            userId: $user->id,
            clientId: $client->id,
            timeEntryIds: [$entry->id],
            data: [
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'tax_rate' => 10,
            ],
            expenseIds: [$expense->id],
        );

        $this->assertCount(2, $invoice->items);
        $this->assertEquals(150.00, $invoice->subtotal);
        $this->assertEquals(15.00, $invoice->tax_amount);
        $this->assertEquals(165.00, $invoice->total);
    }

    public function test_marks_expenses_as_invoiced()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $expense = Expense::factory()->for($client)->for($user)->create([
            'is_invoiced' => false,
        ]);

        $this->assertFalse($expense->is_invoiced);

        $this->invoiceService->createFromTimeEntries(
            userId: $user->id,
            clientId: $client->id,
            timeEntryIds: [],
            data: [
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
            ],
            expenseIds: [$expense->id],
        );

        $this->assertTrue($expense->fresh()->is_invoiced);
    }

    public function test_deletes_invoice_and_unmarks_expenses()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $expense = Expense::factory()->for($client)->for($user)->create([
            'is_invoiced' => false,
        ]);

        $invoice = $this->invoiceService->createFromTimeEntries(
            userId: $user->id,
            clientId: $client->id,
            timeEntryIds: [],
            data: [
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
            ],
            expenseIds: [$expense->id],
        );

        $this->assertTrue($expense->fresh()->is_invoiced);

        $this->invoiceService->deleteInvoice($invoice);

        $this->assertNull(Invoice::find($invoice->id));
        $this->assertFalse($expense->fresh()->is_invoiced);
    }

    public function test_gets_unbilled_expenses_for_client()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $unbilled = Expense::factory()->for($client)->for($user)->create([
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        Expense::factory()->for($client)->for($user)->create([
            'is_billable' => true,
            'is_invoiced' => true,
        ]);

        Expense::factory()->for($client)->for($user)->create([
            'is_billable' => false,
            'is_invoiced' => false,
        ]);

        $expenses = $this->invoiceService->getUnbilledExpensesForClient($client->id, $user->id);

        $this->assertCount(1, $expenses);
        $this->assertEquals($unbilled->id, $expenses->first()->id);
    }

    public function test_only_includes_client_expenses_on_invoice()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $otherClient = Client::factory()->for($user)->create();

        $clientExpense = Expense::factory()->for($client)->for($user)->create([
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $otherExpense = Expense::factory()->for($otherClient)->for($user)->create([
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $invoice = $this->invoiceService->createFromTimeEntries(
            userId: $user->id,
            clientId: $client->id,
            timeEntryIds: [],
            data: [
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
            ],
            expenseIds: [$clientExpense->id, $otherExpense->id],
        );

        $this->assertCount(1, $invoice->items);
        $this->assertEquals($clientExpense->id, $invoice->items->first()->expense_id);
    }
}
