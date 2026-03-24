<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $showTrashed = $request->boolean('trashed');

        $query = auth()->user()->invoices()
            ->with('client')
            ->orderBy('issue_date', 'desc');

        if ($showTrashed) {
            $query->onlyTrashed();
        }

        $invoices = $query->paginate(15)->withQueryString();

        return view('invoices.index', compact('invoices', 'showTrashed'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $clients = auth()->user()->clients()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get unbilled time entries and expenses if client is selected
        $unbilledTimeEntries = collect();
        $unbilledExpenses = collect();
        if ($request->has('client_id')) {
            $unbilledTimeEntries = $this->invoiceService->getUnbilledEntriesForClient(
                $request->client_id,
                auth()->id()
            );
            $unbilledExpenses = $this->invoiceService->getUnbilledExpensesForClient(
                $request->client_id,
                auth()->id()
            );
        }

        return view('invoices.create', compact('clients', 'unbilledTimeEntries', 'unbilledExpenses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'time_entries' => 'nullable|array',
            'time_entries.*' => 'exists:time_entries,id',
            'expenses' => 'nullable|array',
            'expenses.*' => 'exists:expenses,id',
        ]);

        $invoice = $this->invoiceService->createFromTimeEntries(
            userId: auth()->id(),
            clientId: $validated['client_id'],
            timeEntryIds: $validated['time_entries'] ?? [],
            data: $validated,
            expenseIds: $validated['expenses'] ?? [],
        );

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items.timeEntry.project', 'items.expense']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $clients = auth()->user()->clients()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $invoice->load('items');

        return view('invoices.edit', compact('invoice', 'clients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
        ]);

        $this->invoiceService->updateInvoice($invoice, $validated);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $this->invoiceService->deleteInvoice($invoice);

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Restore a soft-deleted invoice.
     */
    public function restore(Invoice $invoice)
    {
        $this->authorize('restore', $invoice);

        $this->invoiceService->restoreInvoice($invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice restored successfully.');
    }

    /**
     * Permanently delete a soft-deleted invoice.
     */
    public function forceDelete(Invoice $invoice)
    {
        $this->authorize('forceDelete', $invoice);

        $this->invoiceService->forceDeleteInvoice($invoice);

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice permanently deleted.');
    }

    /**
     * Generate PDF for the invoice.
     */
    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items.timeEntry.project', 'items.expense', 'user']);

        $pdf = $this->invoiceService->generatePDF($invoice);

        return $pdf->download('invoice-'.$invoice->invoice_number.'.pdf');
    }
}
