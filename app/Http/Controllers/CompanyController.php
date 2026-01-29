<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    use AuthorizesRequests;

    /**
     * Show the form for creating a new company.
     */
    public function create(): View
    {
        $companies = auth()->user()->companies;

        return view('profile.company-form', compact('companies'));
    }

    /**
     * Store a newly created company.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $isDefault = $request->has('is_default') && $request->input('is_default') == '1';

        $company = auth()->user()->companies()->create([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
            'is_default' => $isDefault,
        ]);

        // If marked as default or if it's the first company, set as default
        if ($isDefault || auth()->user()->companies()->count() === 1) {
            $company->setAsDefault();
        }

        return redirect()->route('profile.edit')
            ->with('status', 'company-created')
            ->with('success', 'Company added successfully.');
    }

    /**
     * Show the form for editing the company.
     */
    public function edit(Company $company): View
    {
        $this->authorize('update', $company);

        $companies = auth()->user()->companies;

        return view('profile.company-form', compact('company', 'companies'));
    }

    /**
     * Update the specified company.
     */
    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $company->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
        ]);

        $isDefault = $request->has('is_default') && $request->input('is_default') == '1';
        if ($isDefault) {
            $company->setAsDefault();
        }

        return redirect()->route('profile.edit')
            ->with('status', 'company-updated')
            ->with('success', 'Company updated successfully.');
    }

    /**
     * Set the company as default.
     */
    public function setDefault(Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $company->setAsDefault();

        return back()->with('success', 'Default company updated.');
    }

    /**
     * Remove the specified company.
     */
    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        // Prevent deleting the default company if it's the only one
        if ($company->is_default && auth()->user()->companies()->count() === 1) {
            return back()->with('error', 'Cannot delete your only company.');
        }

        // If deleting the default company, set another as default
        if ($company->is_default) {
            $newDefault = auth()->user()->companies()
                ->where('id', '!=', $company->id)
                ->first();

            if ($newDefault) {
                $newDefault->setAsDefault();
            }
        }

        $company->delete();

        return back()->with('success', 'Company deleted successfully.');
    }
}
