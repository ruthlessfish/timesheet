<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_auto_generates_invoice_number_on_creation()
    {
        $invoice = Invoice::factory()->create();

        $this->assertNotNull($invoice->invoice_number);
        $this->assertStringStartsWith('INV-' . date('Y') . '-', $invoice->invoice_number);
    }

    #[Test]
    public function invoice_numbers_are_sequential_within_year()
    {
        $invoice1 = Invoice::factory()->create();
        $invoice2 = Invoice::factory()->create();

        $this->assertEquals('INV-' . date('Y') . '-0001', $invoice1->invoice_number);
        $this->assertEquals('INV-' . date('Y') . '-0002', $invoice2->invoice_number);
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

        $invoice->refresh(); // Refresh to load the items relationship
        $invoice->calculateTotals();
        $invoice->save();
        $invoice->refresh(); // Refresh again to get updated values

        $this->assertEquals(250.00, $invoice->subtotal);
        $this->assertEquals(0.00, $invoice->tax_amount);
        $this->assertEquals(250.00, $invoice->total);
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

        $invoice->refresh(); // Refresh to load the items relationship
        $invoice->calculateTotals();
        $invoice->save();
        $invoice->refresh(); // Refresh again to get updated values

        $this->assertEquals(100.00, $invoice->subtotal);
        $this->assertEquals(10.00, $invoice->tax_amount);
        $this->assertEquals(110.00, $invoice->total);
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
            'subtotal' => 100.50,
            'tax_rate' => 10.25,
            'tax_amount' => 10.30,
            'total' => 110.80,
        ]);

        $this->assertEquals('100.50', $invoice->subtotal);
        $this->assertEquals('10.25', $invoice->tax_rate);
        $this->assertEquals('10.30', $invoice->tax_amount);
        $this->assertEquals('110.80', $invoice->total);
    }
}
