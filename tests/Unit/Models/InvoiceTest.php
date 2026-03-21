<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_auto_generates_invoice_number_on_creation()
    {
        $invoice = Invoice::factory()->create();

        $this->assertNotNull($invoice->invoice_number);
        $this->assertStringStartsWith('INV-'.date('Y').'-', $invoice->invoice_number);
    }

    #[Test]
    public function invoice_numbers_are_sequential_within_year()
    {
        $invoice1 = Invoice::factory()->create();
        $invoice2 = Invoice::factory()->create();

        $this->assertEquals('INV-'.date('Y').'-0001', $invoice1->invoice_number);
        $this->assertEquals('INV-'.date('Y').'-0002', $invoice2->invoice_number);
    }

    #[Test]
    public function it_does_not_override_manually_set_invoice_number()
    {
        $invoice = Invoice::factory()->create([
            'invoice_number' => 'CUSTOM-001',
        ]);

        $this->assertEquals('CUSTOM-001', $invoice->invoice_number);
    }

    #[Test]
    public function calculate_totals_sums_invoice_items()
    {
        $invoice = Invoice::factory()->create([
            'tax_rate' => 0,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Item 1',
            'quantity' => 1,
            'rate' => 100,
            'amount' => 100,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Item 2',
            'quantity' => 1,
            'rate' => 150,
            'amount' => 150,
        ]);

        $this->assertEquals(250.00, $invoice->subtotal);
        $this->assertEquals(0.00, $invoice->tax_amount);
        $this->assertEquals(250.00, $invoice->total);
        $this->assertEquals(0, $invoice->total_hours);
    }

    #[Test]
    public function calculate_totals_applies_tax_correctly()
    {
        $invoice = Invoice::factory()->create([
            'tax_rate' => 10,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Service',
            'quantity' => 1,
            'rate' => 100,
            'amount' => 100,
        ]);

        $this->assertEquals(100.00, $invoice->subtotal);
        $this->assertEquals(10.00, $invoice->tax_amount);
        $this->assertEquals(110.00, $invoice->total);
        $this->assertEquals(0, $invoice->total_hours);
    }

    #[Test]
    public function it_belongs_to_a_client()
    {
        $invoice = Invoice::factory()->create();

        $this->assertInstanceOf(Client::class, $invoice->client);
    }

    #[Test]
    public function it_has_many_items()
    {
        $invoice = Invoice::factory()->create();
        InvoiceItem::factory()->count(3)->create([
            'invoice_id' => $invoice->id,
        ]);

        $this->assertCount(3, $invoice->items);
    }

    #[Test]
    public function it_casts_dates_correctly()
    {
        $invoice = Invoice::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invoice->issue_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invoice->due_date);
    }

    #[Test]
    public function it_casts_decimals_correctly()
    {
        $invoice = Invoice::factory()->create([
            'tax_rate' => 10.25,
        ]);

        $this->assertEquals('10.25', $invoice->tax_rate);
    }

    #[Test]
    public function consolidated_items_groups_time_entries_by_project()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create(['hourly_rate' => 100]);
        $project = Project::factory()->for($client)->for($user)->create(['hourly_rate' => null]);

        $invoice = Invoice::factory()->for($user)->for($client)->create();

        $entry1 = TimeEntry::factory()->for($project)->for($user)->create([
            'duration' => 120,
            'end_time' => now(),
        ]);
        $entry2 = TimeEntry::factory()->for($project)->for($user)->create([
            'duration' => 120,
            'end_time' => now(),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'time_entry_id' => $entry1->id,
            'description' => $project->name,
            'quantity' => 2.00,
            'rate' => 100,
            'amount' => 200,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'time_entry_id' => $entry2->id,
            'description' => $project->name,
            'quantity' => 2.00,
            'rate' => 100,
            'amount' => 200,
        ]);

        $consolidated = $invoice->consolidated_items;

        $this->assertCount(1, $consolidated);
        $this->assertEquals('service', $consolidated->first()->type);
        $this->assertEquals($project->name, $consolidated->first()->description);
        $this->assertEquals(4.00, $consolidated->first()->quantity);
        $this->assertEquals(100.00, $consolidated->first()->rate);
        $this->assertEquals(400.00, $consolidated->first()->amount);
    }

    #[Test]
    public function consolidated_items_separates_different_projects()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $projectA = Project::factory()->for($client)->for($user)->create(['name' => 'Project A']);
        $projectB = Project::factory()->for($client)->for($user)->create(['name' => 'Project B']);

        $invoice = Invoice::factory()->for($user)->for($client)->create();

        $entryA = TimeEntry::factory()->for($projectA)->for($user)->create([
            'duration' => 60,
            'end_time' => now(),
        ]);
        $entryB = TimeEntry::factory()->for($projectB)->for($user)->create([
            'duration' => 90,
            'end_time' => now(),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'time_entry_id' => $entryA->id,
            'description' => 'Project A',
            'quantity' => 1.00,
            'rate' => 100,
            'amount' => 100,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'time_entry_id' => $entryB->id,
            'description' => 'Project B',
            'quantity' => 1.50,
            'rate' => 80,
            'amount' => 120,
        ]);

        $consolidated = $invoice->consolidated_items;

        $this->assertCount(2, $consolidated);
        $serviceItems = $consolidated->where('type', 'service');
        $this->assertCount(2, $serviceItems);
    }

    #[Test]
    public function consolidated_items_keeps_expenses_individual()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $invoice = Invoice::factory()->for($user)->for($client)->create();

        $expense1 = Expense::factory()->for($client)->for($user)->create(['amount' => 50]);
        $expense2 = Expense::factory()->for($client)->for($user)->create(['amount' => 75]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'expense_id' => $expense1->id,
            'description' => 'Expense 1',
            'quantity' => 1,
            'rate' => 50,
            'amount' => 50,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'expense_id' => $expense2->id,
            'description' => 'Expense 2',
            'quantity' => 1,
            'rate' => 75,
            'amount' => 75,
        ]);

        $consolidated = $invoice->consolidated_items;

        $this->assertCount(2, $consolidated);
        $this->assertTrue($consolidated->every(fn ($item) => $item->type === 'expense'));
    }

    #[Test]
    public function consolidated_items_mixes_services_and_expenses()
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $project = Project::factory()->for($client)->for($user)->create();

        $invoice = Invoice::factory()->for($user)->for($client)->create();

        $entry1 = TimeEntry::factory()->for($project)->for($user)->create(['duration' => 60, 'end_time' => now()]);
        $entry2 = TimeEntry::factory()->for($project)->for($user)->create(['duration' => 60, 'end_time' => now()]);
        $expense = Expense::factory()->for($client)->for($user)->create(['amount' => 25]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'time_entry_id' => $entry1->id,
            'description' => $project->name,
            'quantity' => 1.00,
            'rate' => 100,
            'amount' => 100,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'time_entry_id' => $entry2->id,
            'description' => $project->name,
            'quantity' => 1.00,
            'rate' => 100,
            'amount' => 100,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'expense_id' => $expense->id,
            'description' => 'Office supplies',
            'quantity' => 1,
            'rate' => 25,
            'amount' => 25,
        ]);

        $consolidated = $invoice->consolidated_items;

        $this->assertCount(2, $consolidated);

        $service = $consolidated->firstWhere('type', 'service');
        $this->assertEquals(2.00, $service->quantity);
        $this->assertEquals(200.00, $service->amount);

        $expenseItem = $consolidated->firstWhere('type', 'expense');
        $this->assertEquals(25.00, $expenseItem->amount);
    }
}
