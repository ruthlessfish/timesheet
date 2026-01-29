<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\TimeEntryResource;
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
            ->get();

        return InvoiceResource::collection($invoices);
    }

    /**
     * Get unbilled time entries for a client
     */
    public function unbilledEntries(Request $request, int $clientId)
    {
        $unbilledEntries = $this->invoiceService->getUnbilledEntriesForClient(
            $clientId,
            auth()->id()
        );

        return TimeEntryResource::collection($unbilledEntries);
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

        return new InvoiceResource($invoice->load(['client', 'items']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items.timeEntry']);

        return new InvoiceResource($invoice);
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

        return new InvoiceResource($invoice->fresh(['client', 'items']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $this->invoiceService->deleteInvoice($invoice);

        return response()->json([
            'message' => 'Invoice deleted successfully',
        ]);
    }

    /**
     * Download PDF for the invoice
     */
    public function downloadPdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items', 'user']);

        $pdf = $this->invoiceService->generatePDF($invoice);

        return $pdf->download('invoice-'.$invoice->invoice_number.'.pdf');
    }
}
