<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = auth()->user()->clients()
            ->withCount('projects')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('clients.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['is_active'] = $request->has('is_active');

        Client::create($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Client created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        $this->authorize('view', $client);

        $client->load(['projects' => function ($query) {
            $query->withCount('timeEntries');
        }]);

        $totalHours = $client->projects->sum(function ($project) {
            return $project->timeEntries->sum('duration') / 60;
        });

        $totalRevenue = $client->invoices()
            ->where('status', 'paid')
            ->sum('total');

        $recentInvoices = $client->invoices()
            ->orderBy('issue_date', 'desc')
            ->limit(5)
            ->get();

        return view('clients.show', compact('client', 'totalHours', 'totalRevenue', 'recentInvoices'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        $this->authorize('update', $client);

        return view('clients.edit', compact('client'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $client->update($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Client updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $this->authorize('delete', $client);

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted successfully.');
    }
}
