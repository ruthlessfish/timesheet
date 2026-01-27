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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5.2
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `tailwindcss-development` — Styles applications using Tailwind CSS v3 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.
</laravel-boost-guidelines>
