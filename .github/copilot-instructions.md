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
- **Service Layer**: Business logic extracted to services (see `docs/REFACTORING_PLAN.md`)
  - Inject services via constructor dependency injection
  - Controllers handle HTTP concerns only (validation, responses, redirects)
  - Services handle business logic (calculations, workflows, data manipulation)

**Services** (app/Services/):
- **BillingService**: Rate resolution (4-level cascade), amount calculations, unbilled entry queries
- **TimeEntryService**: Timer management, active timer constraint, duration calculations
- **InvoiceService**: Invoice creation workflow, PDF generation, time entry marking/unmarking
- **AnalyticsService**: Dashboard statistics, time series data, project breakdowns, revenue analysis
- All services use dependency injection and delegate to each other (composition over duplication)
- Services maintain critical business rules (rate cascade, invoice workflow, active timer constraint)

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

**Service Layer Architecture**:
- `app/Services/BillingService.php` - Core billing calculations, rate cascade implementation
- `app/Services/TimeEntryService.php` - Timer workflows, active timer validation
- `app/Services/InvoiceService.php` - Invoice creation, PDF generation, time entry marking
- `app/Services/AnalyticsService.php` - Dashboard stats, charts, revenue analytics
- See `docs/REFACTORING_PLAN.md` for full service documentation and architecture

**Rate Calculation Examples**:
- `app/Services/BillingService.php:resolveHourlyRate()` - 4-level cascade: entry → project → client → 0
- `app/Services/BillingService.php:calculateAmount()` - Duration (minutes) → hours * rate
- `app/Models/TimeEntry.php:52-56` - `getAmountAttribute()` delegates to service (legacy)
- `app/Models/Project.php:47-54` - `getTotalAmountAttribute()` aggregates time entries

**Dashboard Analytics**: 
- `app/Services/AnalyticsService.php` - All dashboard calculations extracted to service
- `getDashboardStats()` - Clients, projects, monthly hours, monthly revenue
- `getDailyHoursTimeSeries()` - Last N days data for line chart
- `getProjectHoursBreakdown()` - Top N projects by hours for bar chart
- `getBillableRatio()` - Billable vs non-billable breakdown for doughnut chart
- Legacy: `app/Http/Controllers/DashboardController.php` (being refactored to use service)

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

**Current Status**: 147 tests, 313 assertions, 100% passing (see `docs/TEST_SUMMARY.md`)

**Test Structure**:
```
tests/Feature/     # HTTP, authorization, workflows (62 tests)
tests/Unit/        # Models (26 tests), Services (51 tests)
```

**Conventions**:
- Use `RefreshDatabase` trait for all database-touching tests
- Factories: Client, Project, TimeEntry, Invoice all implemented
- Test authorization with multiple users via policies
- Service tests cover all business logic in isolation
- Feature tests verify HTTP layer and integration

**Critical Test Coverage** ✅:
1. ✅ Active timer constraint (TimeEntryServiceTest)
2. ✅ Rate cascade resolution - all 4 levels (BillingServiceTest)
3. ✅ Invoice workflow - marks entries as invoiced (InvoiceServiceTest)
4. ✅ Auto-generated invoice numbers INV-YYYY-0001 (InvoiceTest)
5. ✅ Unbilled entry filtering (BillingServiceTest, InvoiceTest)

**Service Tests** (51 tests):
- `BillingServiceTest.php` - 10 tests: rate cascade, calculations, unbilled queries
- `TimeEntryServiceTest.php` - 15 tests: timer workflows, active constraint, filtering
- `InvoiceServiceTest.php` - 13 tests: invoice creation, PDF, marking/unmarking
- `AnalyticsServiceTest.php` - 13 tests: dashboard stats, charts, revenue analysis

**Running Tests**:
```bash
composer run test    # Clears config cache, runs PHPUnit
php artisan test     # Direct PHPUnit execution
php artisan test --filter=ServiceName  # Run specific test class
```

**Documentation**:
- Full test documentation: `docs/TEST_SUMMARY.md`
- Refactoring plan: `docs/REFACTORING_PLAN.md`
- API authentication guide: `docs/API_AUTHENTICATION.md`

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
