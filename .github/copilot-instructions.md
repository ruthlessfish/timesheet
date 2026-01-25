# Laravel Time Tracking Application

Time-tracking system for freelance web developers built with Laravel 12, Breeze authentication, and Tailwind CSS.

## Architecture & Data Flow

**Hierarchy**: `User` → `Client` → `Project` → `TimeEntry` → `Invoice` → `InvoiceItem`

**Hourly Rate Cascading**: The app uses a fallback chain for billing rates:
```
TimeEntry.hourly_rate ?? Project.hourly_rate ?? Client.hourly_rate ?? 0
```
This pattern is critical in `TimeEntry::getAmountAttribute()`, `Project::getTotalAmountAttribute()`, and invoice generation. Always respect this cascade when calculating amounts.

**Invoice Workflow**:
1. Time entries are marked `is_billable=true` and tracked per project
2. Invoice creation queries unbilled entries: `is_billable=true AND is_invoiced=false AND end_time NOT NULL`
3. Selected entries become `InvoiceItem` records with calculated amounts
4. Time entries are marked `is_invoiced=true` to prevent double-billing
5. Invoice auto-generates number on creation: `INV-{YEAR}-{0001}` (see `Invoice::boot()`)

**Active Timer Pattern**: Only one active timer per user allowed. Active = `end_time IS NULL`. 
- Controllers check for active timers before starting new ones
- Dashboard and time-entries.index display active timer prominently
- Stopping a timer sets `end_time`, calculates `duration` via `TimeEntry::calculateDuration()`

## Development Workflow

**Setup & Running**:
```bash
composer run setup          # Full setup: deps, .env, key, migrate, npm
composer run dev            # Runs 4 concurrent processes: serve, queue, pail, vite
composer run test           # Clear config cache, run PHPUnit
```

The `dev` script uses `concurrently` to manage Laravel server (8000), queue worker, Pail logs, and Vite dev server simultaneously. Never run these individually—use the composer script.

**Database**: Defaults to SQLite (`database/database.sqlite`). Migrations use `onDelete('cascade')` for foreign keys. Models use soft deletes sparingly—most use hard deletes with cascade.

## Code Conventions

**Controllers**: 
- Standard resource controllers with `use AuthorizesRequests` trait
- Always eager load relationships in index/show: `->with('project.client')`
- Authorization via `$this->authorize('view', $model)` in show/edit/update/delete
- Validation inline in controller methods (no FormRequest classes except Auth)
- **Current Pattern**: Business logic in controllers (invoice creation, analytics queries)
- **Planned Refactor**: Extract to service layer (see `REFACTORING_PLAN.md` for migration strategy)

**Models**:
- All relationships explicitly typed: `BelongsTo`, `HasMany`
- Computed attributes via `getXxxAttribute()`: `getTotalHoursAttribute()`, `getAmountAttribute()`
- Model methods for business logic: `TimeEntry::stop()`, `Invoice::calculateTotals()`
- Casts for decimals, dates, booleans defined in `$casts` array

**Views**:
- Use `<x-app-layout>` for authenticated pages, `<x-guest-layout>` for auth pages
- Blade components in `resources/views/components/` (primary-button, text-input, etc.)
- Dashboard uses Chart.js for visualizations (imported in blade, data from controller)
- Forms use Tailwind CSS + `@tailwindcss/forms` plugin

**Policies**: 
- All resources have policies (ClientPolicy, ProjectPolicy, TimeEntryPolicy, InvoicePolicy)
- Standard pattern: users can only view/edit/delete their own resources via `$user->id === $model->user_id`
- Registered in `AppServiceProvider` (currently empty but policies auto-discovered)

## Key Files & Patterns

**Rate Calculation Examples**:
- `app/Models/TimeEntry.php:52-56` - `getAmountAttribute()` with fallback chain
- `app/Models/Project.php:47-54` - `getTotalAmountAttribute()` aggregates time entries
- `app/Http/Controllers/InvoiceController.php:85-91` - Invoice item creation with rates

**Dashboard Analytics**: `app/Http/Controllers/DashboardController.php` demonstrates:
- Carbon date range queries for monthly stats
- Aggregations with `sum('duration')` converted to hours
- Chart data structures for last 7 days and top 5 projects
- Billable vs non-billable comparisons

**PDF Generation**: 
- Uses `barryvdh/laravel-dompdf`
- Route: `GET /invoices/{invoice}/pdf` → `InvoiceController::pdf()`
- Generate with: `Pdf::loadView('invoices.pdf', compact('invoice'))->download()`

**Frontend Build**:
- Vite bundles `resources/css/app.css` and `resources/js/app.js`
- Alpine.js available globally for interactive components
- Chart.js as npm dependency for dashboard visualizations

**Chart.js Implementation Pattern**:
- CDN loaded via `@push('scripts')` in blade templates
- Controller prepares data as Laravel collections transformed to JSON
- Three chart types on dashboard: line (daily hours), bar (project hours), doughnut (billable ratio)
- Data flow: Controller aggregates → `compact()` to view → `{!! json_encode($collection->pluck('field')) !!}` in JS
- Example: `DashboardController` creates `$last7Days` collection with `['date', 'hours']`, plucked separately for labels/data
- Charts use responsive config with `aspectRatio: 2` for consistent sizing

## Testing Strategy

**Test Structure**:
```
tests/Feature/     # HTTP, authorization, workflows
tests/Unit/        # Models, services, business logic
```

**Conventions**:
- Use `RefreshDatabase` trait for all database-touching tests
- Factories needed: Client, Project, TimeEntry, Invoice (see `REFACTORING_PLAN.md`)
- Test authorization with multiple users via policies
- Critical test cases: rate cascade, active timer constraint, invoice workflow, unbilled entry filtering

**Priority Tests** (not yet implemented):
1. Active timer constraint (only one per user)
2. Rate cascade resolution (TimeEntry → Project → Client → 0)
3. Invoice workflow (marks entries as invoiced, prevents double-billing)
4. Auto-generated invoice numbers (INV-YYYY-0001 format)

**Running Tests**:
```bash
composer run test    # Clears config cache, runs PHPUnit
php artisan test     # Direct PHPUnit execution
```

## Common Tasks

**Adding a new resource**:
1. Create migration with proper foreign keys and cascade deletes
2. Create model with fillable, casts, relationships, and computed attributes
3. Create policy extending the user ownership pattern
4. Create resource controller with eager loading and authorization
5. Add routes to `routes/web.php` within auth middleware group
6. Create views using `<x-app-layout>` and existing components

**Time Entry Flow**:
- Start timer: Create with `start_time=now()`, `end_time=null`
- Stop timer: Call `TimeEntry::stop()` which sets end_time and calculates duration
- Manual entry: Provide both start_time and end_time, controller calls `calculateDuration()`

**Invoice Creation**:
- Get unbilled entries via project->client relationship
- Create invoice, add selected time entries as invoice items
- Mark entries as invoiced, calculate totals via `Invoice::calculateTotals()`
- Export PDF with `Pdf::loadView()`
