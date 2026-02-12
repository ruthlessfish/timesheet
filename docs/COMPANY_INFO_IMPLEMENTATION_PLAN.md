# Company Information on Invoices - Implementation Plan

**Feature**: Add user's company information to invoices with editable settings  
**Estimated Time**: 3-4 days  
**Priority**: High (improves professional invoice appearance)  
**Complexity**: Low-Medium

---

## 📋 Overview

Allow users to add their company information (name, address, phone) to invoices, making them more professional and complete. This information will be editable in the Settings/Profile section and automatically appear on all generated invoices.

### Goals
- Store company information in user profile
- Provide UI for editing company settings
- Display company info on invoice PDFs
- Make all fields optional (users may be freelancers without a company)
- Support multi-line addresses

---

## 🗄️ Database Changes

### Migration 1: Create Companies Table

**File**: `database/migrations/YYYY_MM_DD_create_companies_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            // Index for faster lookups
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
```

**Fields Breakdown**:
- `id` - Primary key
- `user_id` - Foreign key to users table (with cascade delete)
- `name` - Business/company name (required, e.g., "Smith Consulting LLC")
- `address` - Full address (multi-line text for street, city, state, zip)
- `phone` - Contact phone number
- `email` - Company email (optional, can differ from user email)
- `website` - Company website URL (optional, for branding)
- `is_default` - Boolean flag for the default company (used on invoices)
- `timestamps` - Created at / Updated at

**Design Decisions**:
- One user can have multiple companies (future-proofed)
- One company is marked as `is_default` (used automatically on new invoices)
- Foreign key with cascade delete (if user deleted, their companies are too)
- Indexed on `user_id` and `is_default` for fast queries

---

## 📝 Model Updates

### Company Model

**File**: `app/Models/Company.php`

Create a new Company model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'phone',
        'email',
        'website',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the company.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted company information for invoices.
     */
    public function getFormattedInfoAttribute(): string
    {
        $info = [$this->name];
        
        if ($this->address) {
            $info[] = $this->address;
        }
        
        if ($this->phone) {
            $info[] = 'Phone: ' . $this->phone;
        }
        
        if ($this->email) {
            $info[] = 'Email: ' . $this->email;
        }
        
        if ($this->website) {
            $info[] = 'Web: ' . $this->website;
        }
        
        return implode("\n", $info);
    }

    /**
     * Set this company as the default for the user.
     */
    public function setAsDefault(): void
    {
        // Remove default flag from all user's companies
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
        
        // Set this company as default
        $this->update(['is_default' => true]);
    }
}
```

### User Model Updates

**File**: `app/Models/User.php`

Add relationship to companies:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Get all companies for the user.
 */
public function companies(): HasMany
{
    return $this->hasMany(Company::class);
}

/**
 * Get the user's default company.
 */
public function defaultCompany(): ?Company
{
    return $this->companies()->where('is_default', true)->first();
}
```

---

## 🎨 Frontend Implementation

### 1. Settings/Profile Section

Create a new partial form for company information settings.

**File**: `resources/views/profile/partials/update-company-information-form.blade.php`

```php
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Company Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Manage your company information to display on invoices.') }}
        </p>
    </header>

    <div class="mt-6">
        @if($companies->isEmpty())
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                No company information added yet. Add your company details to make your invoices more professional.
            </p>
        @else
            <div class="space-y-4 mb-6">
                @foreach($companies as $company)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 {{ $company->is_default ? 'bg-indigo-50 dark:bg-indigo-900/10 border-indigo-300 dark:border-indigo-700' : '' }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $company->name }}</h3>
                                @if($company->is_default)
                                    <span class="px-2 py-1 text-xs font-medium bg-indigo-600 text-white rounded">Default</span>
                                @endif
                            </div>
                            @if($company->address)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 whitespace-pre-line">{{ $company->address }}</p>
                            @endif
                            <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
                                @if($company->phone)
                                    <span>📞 {{ $company->phone }}</span>
                                @endif
                                @if($company->email)
                                    <span>✉️ {{ $company->email }}</span>
                                @endif
                                @if($company->website)
                                    <span>🌐 {{ $company->website }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 ml-4">
                            <a href="{{ route('profile.company.edit', $company) }}" 
                               class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm font-medium">
                                Edit
                            </a>
                            @if(!$company->is_default)
                                <form method="POST" action="{{ route('profile.company.set-default', $company) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 text-sm font-medium">
                                        Set Default
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('profile.company.destroy', $company) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this company?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 text-sm font-medium">
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif

        <a href="{{ route('profile.company.create') }}" 
           class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
            {{ $companies->isEmpty() ? 'Add Company' : 'Add Another Company' }}
        </a>
    </div>
</section>
```

### 2. Company Create/Edit Form

**File**: `resources/views/profile/company-form.blade.php`

```php
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ isset($company) ? __('Edit Company') : __('Add Company') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ isset($company) ? route('profile.company.update', $company) : route('profile.company.store') }}" class="space-y-6">
                        @csrf
                        @if(isset($company))
                            @method('PATCH')
                        @endif

                        <!-- Company Name -->
                        <div>
                            <x-input-label for="name" :value="__('Company Name')" />
                            <x-text-input 
                                id="name" 
                                name="name" 
                                type="text" 
                                class="mt-1 block w-full" 
                                :value="old('name', $company->name ?? '')" 
                                required
                                autocomplete="organization" 
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Your business or freelance company name
                            </p>
                        </div>

                        <!-- Company Address -->
                        <div>
                            <x-input-label for="address" :value="__('Address')" />
                            <textarea 
                                id="address" 
                                name="address" 
                                rows="3"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            >{{ old('address', $company->address ?? '') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('address')" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Full address including street, city, state/province, and postal code
                            </p>
                        </div>

                        <!-- Company Phone -->
                        <div>
                            <x-input-label for="phone" :value="__('Phone Number')" />
                            <x-text-input 
                                id="phone" 
                                name="phone" 
                                type="tel" 
                                class="mt-1 block w-full" 
                                :value="old('phone', $company->phone ?? '')" 
                                autocomplete="tel" 
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                        </div>

                        <!-- Company Email -->
                        <div>
                            <x-input-label for="email" :value="__('Email Address')" />
                            <x-text-input 
                                id="email" 
                                name="email" 
                                type="email" 
                                class="mt-1 block w-full" 
                                :value="old('email', $company->email ?? '')" 
                                autocomplete="email" 
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Leave empty to use your account email on invoices
                            </p>
                        </div>

                        <!-- Company Website -->
                        <div>
                            <x-input-label for="website" :value="__('Website')" />
                            <x-text-input 
                                id="website" 
                                name="website" 
                                type="url" 
                                class="mt-1 block w-full" 
                                :value="old('website', $company->website ?? '')" 
                                placeholder="https://example.com"
                                autocomplete="url" 
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('website')" />
                        </div>

                        <!-- Set as Default -->
                        @if(!isset($company) || !$company->is_default)
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_default" value="1" 
                                       {{ old('is_default', !isset($company) && $companies->isEmpty()) ? 'checked' : '' }}
                                       class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Set as default company (used on new invoices)</span>
                            </label>
                        </div>
                        @endif

                        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('profile.edit') }}#company" 
                               class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300">
                                Cancel
                            </a>
                            <x-primary-button type="submit">
                                {{ isset($company) ? __('Update Company') : __('Add Company') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

**Update**: `resources/views/profile/edit.blade.php`

Add the company information section:

```php
<div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg" id="company">
    <div class="max-w-xl">
        @include('profile.partials.update-company-information-form', ['companies' => auth()->user()->companies])
    </div>
</div>
```

Insert this between the profile information and password sections.

---

## 🔧 Backend Implementation

### Controller: CompanyController

**File**: `app/Http/Controllers/CompanyController.php`

Create a new controller for managing companies:

```bash
php artisan make:controller CompanyController
```

```php
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
            'is_default' => ['nullable', 'boolean'],
        ]);

        $company = auth()->user()->companies()->create([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        // If marked as default or if it's the first company, set as default
        if (($validated['is_default'] ?? false) || auth()->user()->companies()->count() === 1) {
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
            'is_default' => ['nullable', 'boolean'],
        ]);

        $company->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
        ]);

        if ($validated['is_default'] ?? false) {
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
```

### Policy: CompanyPolicy

**File**: `app/Policies/CompanyPolicy.php`

```bash
php artisan make:policy CompanyPolicy --model=Company
```

```php
<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Company $company): bool
    {
        return $user->id === $company->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Company $company): bool
    {
        return $user->id === $company->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Company $company): bool
    {
        return $user->id === $company->user_id;
    }
}
```

**Routes**: `routes/web.php`

Add routes for company management:

```php
Route::middleware('auth')->group(function () {
    // ... existing routes ...
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Company routes
    Route::get('/profile/company/create', [CompanyController::class, 'create'])->name('profile.company.create');
    Route::post('/profile/company', [CompanyController::class, 'store'])->name('profile.company.store');
    Route::get('/profile/company/{company}/edit', [CompanyController::class, 'edit'])->name('profile.company.edit');
    Route::patch('/profile/company/{company}', [CompanyController::class, 'update'])->name('profile.company.update');
    Route::patch('/profile/company/{company}/set-default', [CompanyController::class, 'setDefault'])->name('profile.company.set-default');
    Route::delete('/profile/company/{company}', [CompanyController::class, 'destroy'])->name('profile.company.destroy');
    
    Route::patch('/settings/theme', [ThemeController::class, 'update'])->name('settings.theme.update');
});
```

---

## 📄 Invoice PDF Updates

### Update Invoice PDF Template

**File**: `resources/views/invoices/pdf.blade.php`

Add company information section at the top of the invoice:

```php
<div class="info-section">
    <table class="info-grid">
        <tr>
            <td>
                @php
                    $company = $invoice->user->defaultCompany();
                @endphp
                
                @if($company)
                    <div class="info-title">From</div>
                    <div class="info-content">
                        <strong>{{ $company->name }}</strong><br>
                        @if($company->address)
                            {!! nl2br(e($company->address)) !!}<br>
                        @endif
                        @if($company->phone)
                            Phone: {{ $company->phone }}<br>
                        @endif
                        @if($company->email)
                            Email: {{ $company->email }}<br>
                        @endif
                        @if($company->website)
                            Web: {{ $company->website }}
                        @endif
                    </div>
                @else
                    <div class="info-title">From</div>
                    <div class="info-content">
                        <strong>{{ $invoice->user->name }}</strong><br>
                        Email: {{ $invoice->user->email }}
                    </div>
                @endif
            </td>
            <td>
                <div class="info-title">Bill To</div>
                <div class="info-content">
                    <strong>{{ $invoice->client->name }}</strong><br>
                    <!-- existing client info -->
                </div>
            </td>
        </tr>
    </table>
</div>
```

**Conditional Logic**:
- Uses `defaultCompany()` method to get the user's default company
- If company exists, display full company information
- If no company set, fall back to user name and email
- Each field is optional and only displayed if populated

---

## ✅ Testing

### Feature Tests

**File**: `tests/Feature/CompanyControllerTest.php`

```bash
php artisan make:test CompanyControllerTest
```

```php
<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_company_create_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.company.create'));

        $response->assertStatus(200);
        $response->assertSee('Add Company');
    }

    public function test_user_can_create_company(): void
    {
        $user = User::factory()->create();

        $companyData = [
            'name' => 'Test Company Inc.',
            'address' => '123 Main St',
            'phone' => '555-1234',
            'email' => 'info@testcompany.com',
            'website' => 'https://testcompany.com',
        ];

        $response = $this->actingAs($user)->post(route('profile.company.store'), $companyData);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'company-created');
        
        $this->assertDatabaseHas('companies', [
            'user_id' => $user->id,
            'name' => 'Test Company Inc.',
            'address' => '123 Main St',
            'phone' => '555-1234',
            'email' => 'info@testcompany.com',
            'website' => 'https://testcompany.com',
        ]);
    }

    public function test_first_company_becomes_default(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.company.store'), [
            'name' => 'First Company',
        ]);

        $company = Company::where('user_id', $user->id)->first();
        $this->assertTrue($company->is_default);
    }

    public function test_user_can_set_default_company(): void
    {
        $user = User::factory()->create();
        $company1 = Company::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $company2 = Company::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $response = $this->actingAs($user)->patch(route('profile.company.set-default', $company2));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Default company updated.');
        
        $this->assertTrue($company2->fresh()->is_default);
        $this->assertFalse($company1->fresh()->is_default);
    }

    public function test_user_cannot_view_other_users_companies(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCompany = Company::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('profile.company.edit', $otherCompany));

        $response->assertStatus(403);
    }

    public function test_user_can_view_edit_form_for_their_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('profile.company.edit', $company));

        $response->assertStatus(200);
        $response->assertSee('Edit Company');
        $response->assertSee($company->name);
    }

    public function test_user_can_update_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patch(route('profile.company.update', $company), [
            'name' => 'Updated Company Name',
            'address' => 'Updated Address',
            'phone' => '555-9999',
            'email' => 'updated@company.com',
            'website' => 'https://updated.com',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'company-updated');
        
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Company Name',
            'address' => 'Updated Address',
            'phone' => '555-9999',
            'email' => 'updated@company.com',
            'website' => 'https://updated.com',
        ]);
    }

    public function test_user_cannot_update_other_users_company(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCompany = Company::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->patch(route('profile.company.update', $otherCompany), [
            'name' => 'Hacked Company',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_non_default_company(): void
    {
        $user = User::factory()->create();
        $company1 = Company::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $company2 = Company::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $response = $this->actingAs($user)->delete(route('profile.company.destroy', $company2));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Company deleted successfully.');
        $this->assertDatabaseMissing('companies', ['id' => $company2->id]);
    }

    public function test_cannot_delete_only_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id, 'is_default' => true]);

        $response = $this->actingAs($user)->delete(route('profile.company.destroy', $company));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Cannot delete your only company.');
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    public function test_deleting_default_company_sets_new_default(): void
    {
        $user = User::factory()->create();
        $company1 = Company::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $company2 = Company::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $response = $this->actingAs($user)->delete(route('profile.company.destroy', $company1));

        $response->assertRedirect();
        $this->assertTrue($company2->fresh()->is_default);
    }

    public function test_company_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.company.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_company_email_must_be_valid(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.company.store'), [
            'name' => 'Test Company',
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_company_website_must_be_valid_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.company.store'), [
            'name' => 'Test Company',
            'website' => 'not-a-url',
        ]);

        $response->assertSessionHasErrors('website');
    }
}
```

### Invoice PDF Tests

**File**: `tests/Feature/InvoicePdfTest.php`

Add these test methods to the existing `InvoicePdfTest` class:

```php
public function test_invoice_pdf_includes_company_information(): void
{
    $user = User::factory()->create();
    $company = Company::factory()->create([
        'user_id' => $user->id,
        'is_default' => true,
        'name' => 'Test Company LLC',
        'address' => '456 Business Ave',
        'phone' => '555-1234',
        'email' => 'info@testcompany.com',
        'website' => 'https://testcompany.com',
    ]);

    $client = \App\Models\Client::factory()->create(['user_id' => $user->id]);
    $invoice = \App\Models\Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
    ]);

    $response = $this->actingAs($user)->get(route('invoices.pdf', $invoice));

    $response->assertStatus(200);
    $content = $response->getContent();
    $this->assertStringContainsString('Test Company LLC', $content);
    $this->assertStringContainsString('456 Business Ave', $content);
    $this->assertStringContainsString('555-1234', $content);
    $this->assertStringContainsString('info@testcompany.com', $content);
    $this->assertStringContainsString('https://testcompany.com', $content);
}

public function test_invoice_pdf_falls_back_to_user_info_without_company(): void
{
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $client = \App\Models\Client::factory()->create(['user_id' => $user->id]);
    $invoice = \App\Models\Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
    ]);

    $response = $this->actingAs($user)->get(route('invoices.pdf', $invoice));

    $response->assertStatus(200);
    $content = $response->getContent();
    $this->assertStringContainsString('John Doe', $content);
    $this->assertStringContainsString('john@example.com', $content);
}
```

---

## 🗂️ Database Factory

### Company Factory

**File**: `database/factories/CompanyFactory.php`

```bash
php artisan make:factory CompanyFactory
```

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => fake()->url(),
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the company is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the company has minimal information.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'address' => null,
            'phone' => null,
            'email' => null,
            'website' => null,
        ]);
    }
}
```

---

## 📊 Implementation Checklist

### Phase 1: Database & Models (2-3 hours)
- [ ] Create companies migration: `php artisan make:migration create_companies_table`
- [ ] Define schema: id, user_id, name, address, phone, email, website, is_default, timestamps
- [ ] Run migration: `php artisan migrate`
- [ ] Create Company model: `php artisan make:model Company`
- [ ] Add `$fillable`, `$casts`, relationships to Company model
- [ ] Add `setAsDefault()` method to Company model
- [ ] Add `companies()` and `defaultCompany()` methods to User model
- [ ] Create CompanyFactory: `php artisan make:factory CompanyFactory`
- [ ] Test migration rollback/re-run

### Phase 2: Backend & Policy (3-4 hours)
- [ ] Create CompanyController: `php artisan make:controller CompanyController`
- [ ] Implement create(), store(), edit(), update(), setDefault(), destroy() methods
- [ ] Create CompanyPolicy: `php artisan make:policy CompanyPolicy --model=Company`
- [ ] Implement view, update, delete methods in policy
- [ ] Add routes to `routes/web.php` within auth middleware
- [ ] Test authorization (users can only manage their own companies)

### Phase 3: Frontend UI (4-5 hours)
- [ ] Create `resources/views/profile/partials/update-company-information-form.blade.php`
- [ ] Build company list with cards showing all companies
- [ ] Add "Add Company" / "Add Another Company" button
- [ ] Create `resources/views/profile/company-form.blade.php` for create/edit
- [ ] Implement form with name, address, phone, email, website fields
- [ ] Add is_default checkbox to form
- [ ] Include in `resources/views/profile/edit.blade.php`
- [ ] Style with Tailwind CSS (match existing design)
- [ ] Add dark mode support
- [ ] Test responsive design on mobile

### Phase 4: Invoice PDF Integration (2-3 hours)
- [ ] Update `resources/views/invoices/pdf.blade.php`
- [ ] Add company information section at top
- [ ] Use `$invoice->user->defaultCompany()` to retrieve company
- [ ] Implement conditional display (company info vs user fallback)
- [ ] Format address with `nl2br(e())`
- [ ] Test PDF generation with company
- [ ] Test PDF generation without company (fallback)

### Phase 5: Testing (4-5 hours)
- [ ] Create CompanyControllerTest: `php artisan make:test CompanyControllerTest`
- [ ] Write test: user can view create form
- [ ] Write test: user can create company
- [ ] Write test: first company becomes default
- [ ] Write test: user can set default company
- [ ] Write test: user cannot view other users' companies
- [ ] Write test: user can view edit form for their company
- [ ] Write test: user can update company
- [ ] Write test: user cannot update other users' company
- [ ] Write test: user can delete non-default company
- [ ] Write test: cannot delete only company
- [ ] Write test: deleting default company sets new default
- [ ] Write test: company name is required
- [ ] Write test: email must be valid
- [ ] Write test: website must be valid URL
- [ ] Add invoice PDF tests to existing InvoicePdfTest
- [ ] Write test: PDF includes company information
- [ ] Write test: PDF falls back to user info without company
- [ ] Run tests: `php artisan test --filter=Company`
- [ ] Run full test suite: `php artisan test`

### Phase 6: Polish & Documentation (2-3 hours)
- [ ] Run Pint for code formatting: `vendor/bin/pint`
- [ ] Verify all tests pass
- [ ] Test full user workflow manually
- [ ] Take screenshots for documentation
- [ ] Update user documentation
- [ ] Add migration to deployment checklist

---

## 🎨 UI/UX Considerations

### Settings Page Layout
Place company information section in profile edit page:
1. Profile Information (name, email)
2. **Company Information** ← NEW (shows list + add button)
3. Theme Preference
4. Password
5. Delete Account

### Company List Display
```
┌─────────────────────────────────────────────────────┐
│ Company Information                                 │
├─────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────┐│
│ │ Test Company LLC                   [DEFAULT]    ││
│ │ 123 Main Street                                 ││
│ │ New York, NY 10001                              ││
│ │ 📞 555-1234  ✉️ info@test.com  🌐 test.com     ││
│ │                          [Edit] [Set Default] [X]││
│ └─────────────────────────────────────────────────┘│
│                                                     │
│ [+ Add Another Company]                             │
└─────────────────────────────────────────────────────┘
```

### Invoice PDF Layout
```
┌─────────────────────────────────────────────────┐
│ INVOICE                            [Status Badge]│
│ INV-2026-0001                                   │
├─────────────────────────────────────────────────┤
│ FROM:                    │ BILL TO:             │
│ Smith Consulting LLC     │ Acme Corporation     │
│ 123 Main Street          │ 456 Oak Avenue       │
│ New York, NY 10001       │ Los Angeles, CA      │
│ Phone: (555) 123-4567    │ 90001                │
│ Email: contact@...       │                      │
│ Web: smithconsulting.com │                      │
├─────────────────────────────────────────────────┤
│ INVOICE DATE: Jan 28, 2026                      │
│ DUE DATE: Feb 27, 2026                          │
└─────────────────────────────────────────────────┘
```

---

## 🔄 Future Enhancements (Optional)

### Phase 2 (Future)
- [ ] Company logo upload and display on invoices
- [ ] Invoice template customization per company
- [ ] Tax ID / VAT number field
- [ ] Bank account details for payment instructions
- [ ] Custom invoice footer text per company
- [ ] Company branding colors for invoice theming
- [ ] Multiple invoice templates to choose from

### Phase 3 (Advanced)
- [ ] Letterhead template for other documents
- [ ] Email signature with company info
- [ ] QR code with company contact info
- [ ] Digital business card generation
- [ ] Company-specific hourly rates (override project/client rates)

---

## 📝 Notes

### Design Decisions
1. **Separate companies table** - Allows users to have multiple companies (future-proof for multi-brand freelancers)
2. **Default company per user** - One company marked as default for automatic invoice selection
3. **All fields optional except name** - Not all companies need full contact details
4. **Text area for address** - Allows flexible multi-line formatting
5. **Fallback to user info** - Graceful degradation if no company set
6. **No logo yet** - Keep simple for v1, logo upload can be Phase 2
7. **User ownership** - Companies belong to users via foreign key

### Data Validation
- Company name: required, max 255 chars
- Address: optional, max 1000 chars (text field)
- Phone: optional, max 50 chars (allows international formats)
- Email: optional, valid email format
- Website: optional, valid URL format
- is_default: boolean, only one default per user

### Security
- All fields sanitized via Laravel validation
- HTML escaped in PDF output (`e()` helper)
- Newlines preserved via `nl2br()` for addresses
- Authorization via CompanyPolicy (users can only manage their own companies)
- Foreign key constraint ensures data integrity

### Database Architecture
- `companies` table with user_id foreign key
- One-to-many relationship: User hasMany Companies
- Soft deletes NOT used (hard delete with ownership check)
- `is_default` flag with automatic management via `setAsDefault()` method

---

## 🚀 Deployment Notes

1. Run migration in production: `php artisan migrate`
2. Clear config cache: `php artisan config:clear`
3. Clear view cache: `php artisan view:clear`
4. No breaking changes - all fields are optional, fallback to user info works
5. Existing invoices will show user name/email as fallback (no company)
6. No data migration needed - new table starts empty

---

**Estimated Total Time**: 17-23 hours (4-5 working days)  
**Difficulty**: Medium  
**Dependencies**: None  
**Breaking Changes**: None (backward compatible)

---

*Plan created: January 29, 2026*  
*Updated: January 29, 2026 (restructured to use companies table)*  
*Ready for implementation*
