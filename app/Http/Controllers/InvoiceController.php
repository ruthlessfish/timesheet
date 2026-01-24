<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TimeEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use AuthorizesRequests;
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
            $unbilledTimeEntries = TimeEntry::whereHas('project', function ($query) use ($request) {
                $query->where('client_id', $request->client_id);
            })
            ->where('user_id', auth()->id())
            ->where('is_billable', true)
            ->where('is_invoiced', false)
            ->whereNotNull('end_time')
            ->with('project')
            ->orderBy('start_time', 'desc')
            ->get();
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
        
        $validated['user_id'] = auth()->id();
        $validated['tax_rate'] = $validated['tax_rate'] ?? 0;
        
        $invoice = Invoice::create($validated);
        
        // Add time entries as invoice items
        if ($request->has('time_entries')) {
            foreach ($request->time_entries as $timeEntryId) {
                $timeEntry = TimeEntry::with('project.client')->find($timeEntryId);
                
                if ($timeEntry && $timeEntry->user_id === auth()->id()) {
                    $rate = $timeEntry->hourly_rate 
                        ?? $timeEntry->project->hourly_rate 
                        ?? $timeEntry->project->client->hourly_rate 
                        ?? 0;
                    
                    $hours = $timeEntry->duration / 60;
                    
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'time_entry_id' => $timeEntry->id,
                        'description' => $timeEntry->description ?? $timeEntry->project->name,
                        'quantity' => round($hours, 2),
                        'rate' => $rate,
                        'amount' => $hours * $rate,
                    ]);
                    
                    // Mark time entry as invoiced
                    $timeEntry->update(['is_invoiced' => true]);
                }
            }
        }
        
        // Calculate totals
        $invoice->calculateTotals();
        $invoice->save();
        
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
        
        $validated['tax_rate'] = $validated['tax_rate'] ?? 0;
        
        $invoice->update($validated);
        
        // Recalculate totals
        $invoice->calculateTotals();
        $invoice->save();
        
        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);
        
        // Mark time entries as not invoiced
        foreach ($invoice->items as $item) {
            if ($item->time_entry_id) {
                TimeEntry::find($item->time_entry_id)->update(['is_invoiced' => false]);
            }
        }
        
        $invoice->delete();
        
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
        
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        
        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }
}
