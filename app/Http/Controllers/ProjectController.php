<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = auth()->user()->projects()
            ->with('client')
            ->withCount('timeEntries')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = auth()->user()->clients()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('projects.create', compact('clients'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric|min:0',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,on_hold,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $validated['user_id'] = auth()->id();

        Project::create($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Project created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load(['client', 'timeEntries' => function ($query) {
            $query->orderBy('start_time', 'desc')->limit(20);
        }]);

        $totalHours = round($project->timeEntries()->sum('duration') / 60, 2);

        $totalAmount = $project->timeEntries()
            ->where('is_billable', true)
            ->get()
            ->sum(function ($entry) use ($project) {
                $rate = $entry->hourly_rate ?? $project->hourly_rate ?? $project->client->hourly_rate ?? 0;

                return ($entry->duration / 60) * $rate;
            });

        return view('projects.show', compact('project', 'totalHours', 'totalAmount'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        $clients = auth()->user()->clients()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('projects.edit', compact('project', 'clients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric|min:0',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,on_hold,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $project->update($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}
