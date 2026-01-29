<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TimeEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

/**
 * Service for managing invoices
 */
class InvoiceService
{
    public function __construct(
        private BillingService $billingService
    ) {}

    /**
     * Create invoice from time entries
     *
     * @param  array<int>  $timeEntryIds
     * @param  array<string, mixed>  $data
     */
    public function createFromTimeEntries(int $userId, int $clientId, array $timeEntryIds, array $data): Invoice
    {
        // Create the invoice
        $invoice = Invoice::create([
            'user_id' => $userId,
            'client_id' => $clientId,
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'tax_rate' => $data['tax_rate'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ]);

        // Add time entries as invoice items
        if (! empty($timeEntryIds)) {
            $timeEntries = TimeEntry::with('project.client')
                ->whereIn('id', $timeEntryIds)
                ->where('user_id', $userId)
                ->get();

            foreach ($timeEntries as $timeEntry) {
                $this->addTimeEntryToInvoice($invoice, $timeEntry);
            }

            // Mark time entries as invoiced
            $this->billingService->markAsInvoiced($timeEntries);
        }

        // Calculate and save totals
        $this->updateTotals($invoice);

        return $invoice->fresh(['client', 'items']);
    }

    /**
     * Add a time entry as an invoice item
     */
    protected function addTimeEntryToInvoice(Invoice $invoice, TimeEntry $timeEntry): InvoiceItem
    {
        $rate = $this->billingService->resolveHourlyRate($timeEntry);
        $hours = $timeEntry->duration / 60;
        $amount = $this->billingService->calculateAmount($timeEntry);

        return InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'time_entry_id' => $timeEntry->id,
            'description' => $timeEntry->description ?? $timeEntry->project->name,
            'quantity' => round($hours, 2),
            'rate' => $rate,
            'amount' => $amount,
        ]);
    }

    /**
     * Update invoice totals
     */
    public function updateTotals(Invoice $invoice): Invoice
    {
        $invoice->calculateTotals();
        $invoice->save();

        return $invoice->fresh();
    }

    /**
     * Update an existing invoice
     *
     * @param  array<string, mixed>  $data
     */
    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        $invoice->update([
            'client_id' => $data['client_id'] ?? $invoice->client_id,
            'issue_date' => $data['issue_date'] ?? $invoice->issue_date,
            'due_date' => $data['due_date'] ?? $invoice->due_date,
            'tax_rate' => $data['tax_rate'] ?? $invoice->tax_rate,
            'notes' => $data['notes'] ?? $invoice->notes,
            'status' => $data['status'] ?? $invoice->status,
        ]);

        return $invoice->fresh();
    }

    /**
     * Delete invoice and unmark time entries
     */
    public function deleteInvoice(Invoice $invoice): void
    {
        // Get time entries before deleting items
        $timeEntryIds = $invoice->items()
            ->whereNotNull('time_entry_id')
            ->pluck('time_entry_id');

        // Unmark time entries
        if ($timeEntryIds->isNotEmpty()) {
            $timeEntries = TimeEntry::whereIn('id', $timeEntryIds)->get();
            $this->billingService->markAsNotInvoiced($timeEntries);
        }

        // Delete the invoice (cascade will delete items)
        $invoice->delete();
    }

    /**
     * Generate PDF for invoice
     */
    public function generatePDF(Invoice $invoice)
    {
        $invoice->load(['client', 'items', 'user']);

        return Pdf::loadView('invoices.pdf', compact('invoice'));
    }

    /**
     * Get unbilled time entries for a client
     */
    public function getUnbilledEntriesForClient(int $clientId, int $userId): Collection
    {
        return $this->billingService->getUnbilledTimeEntries($clientId, $userId);
    }

    /**
     * Calculate invoice totals preview without saving
     *
     * @param  array<int>  $timeEntryIds
     * @return array<string, float>
     */
    public function calculatePreviewTotals(array $timeEntryIds, float $taxRate = 0): array
    {
        $timeEntries = TimeEntry::with('project.client')
            ->whereIn('id', $timeEntryIds)
            ->get();

        $subtotal = $this->billingService->calculateTotalAmount($timeEntries);
        $totalHours = $this->billingService->calculateTotalHours($timeEntries);
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
            'total_hours' => round($totalHours, 2),
            'item_count' => $timeEntries->count(),
        ];
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => 'sent']);

        return $invoice->fresh();
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => 'paid']);

        return $invoice->fresh();
    }

    /**
     * Mark invoice as overdue
     */
    public function markAsOverdue(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => 'overdue']);

        return $invoice->fresh();
    }
}
