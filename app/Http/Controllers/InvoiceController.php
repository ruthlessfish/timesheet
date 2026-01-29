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
    public function index()
    {
        $invoices = auth()->user()->invoices()
            ->with('client')
            ->orderBy('issue_date', 'desc')
            ->paginate(15);

        return view('invoices.index', compact('invoices'));
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

        // Get unbilled time entries if client is selected
        $unbilledTimeEntries = collect();
        if ($request->has('client_id')) {
            $unbilledTimeEntries = $this->invoiceService->getUnbilledEntriesForClient(
                $request->client_id,
                auth()->id()
            );
        }

        return view('invoices.create', compact('clients', 'unbilledTimeEntries'));
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
        ]);

        $invoice = $this->invoiceService->createFromTimeEntries(
            userId: auth()->id(),
            clientId: $validated['client_id'],
            timeEntryIds: $validated['time_entries'] ?? [],
            data: $validated
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

        $invoice->load(['client', 'items.timeEntry']);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
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
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $this->invoiceService->deleteInvoice($invoice);

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Generate PDF for the invoice.
     */
    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items', 'user']);

        $pdf = $this->invoiceService->generatePDF($invoice);

        return $pdf->download('invoice-'.$invoice->invoice_number.'.pdf');
    }
}
