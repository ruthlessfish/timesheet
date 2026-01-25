# Refactoring Plan: Service Layer & Testing Strategy

## Current State Analysis

**Business Logic Location**: Currently scattered across controllers, specifically:
- `InvoiceController::store()` - Invoice/InvoiceItem creation, time entry marking (lines 85-107)
- `InvoiceController::destroy()` - Cascade deletion with time entry unmarking (line 180)
- `TimeEntryController` - Duration calculation logic
- `DashboardController` - Complex analytics queries (lines 15-95)

**Testing Coverage**: 
- Auth tests complete (ProfileTest)
- No domain-specific tests for Client, Project, TimeEntry, Invoice
- No test coverage for critical business logic (rate cascading, invoice workflow)

## Phase 1: Extract Services (Week 1-2)

### 1.1 Create Service Structure
```
app/Services/
├── InvoiceService.php      # Invoice creation, PDF generation
├── TimeEntryService.php    # Timer management, duration calculation
├── AnalyticsService.php    # Dashboard stats, charts data
└── BillingService.php      # Rate resolution, amount calculations
```

### 1.2 Extract Invoice Logic
**Target**: `app/Services/InvoiceService.php`
- `createFromTimeEntries($clientId, $timeEntryIds, $data)` - Extract from InvoiceController::store
- `generatePDF($invoice)` - Extract from InvoiceController::pdf
- `deleteInvoice($invoice)` - Extract from InvoiceController::destroy (handles unmarking)
- `updateTotals($invoice)` - Wrapper for Invoice::calculateTotals()

### 1.3 Extract Billing Logic
**Target**: `app/Services/BillingService.php`
- `resolveHourlyRate($timeEntry)` - Implements cascade: entry → project → client → 0
- `calculateAmount($timeEntry)` - Duration * resolved rate
- `getUnbilledTimeEntries($clientId, $userId)` - Reusable query from InvoiceController

### 1.4 Extract Analytics Logic
**Target**: `app/Services/AnalyticsService.php`
- `getDashboardStats($user)` - All dashboard calculations
- `getTimeSeriesData($user, $days = 7)` - For daily hours chart
- `getProjectBreakdown($user, $limit = 5)` - Top projects chart
- `getBillableRatio($user, $startDate, $endDate)` - Billable vs non-billable

### 1.5 Extract Time Entry Logic
**Target**: `app/Services/TimeEntryService.php`
- `startTimer($userId, $projectId, $data)` - Validate no active timer
- `stopTimer($timeEntry)` - Call stop(), handle calculations
- `createManualEntry($userId, $data)` - With duration calculation

## Phase 2: Implement Testing Strategy (Complete)

### 2.1 Test Structure
```
tests/
├── Feature/
│   ├── Auth/              # ✓ Complete (Breeze)
│   ├── ClientTest.php     # CRUD, authorization
│   ├── ProjectTest.php    # CRUD, authorization, client relationship
│   ├── TimeEntryTest.php  # Timer workflow, manual entry, validation
│   └── InvoiceTest.php    # Creation workflow, PDF, unbilled filtering
├── Unit/
│   ├── Models/
│   │   ├── TimeEntryTest.php    # Rate cascade, duration calc, amount attr
│   │   ├── ProjectTest.php      # Total hours/amount attributes
│   │   └── InvoiceTest.php      # Auto-numbering, totals calculation
│   └── Services/
│       ├── BillingServiceTest.php
│       ├── InvoiceServiceTest.php
│       ├── TimeEntryServiceTest.php
│       └── AnalyticsServiceTest.php
```

### 2.2 Testing Conventions
- **Use `RefreshDatabase` trait** for all tests touching DB
- **Factories**: Already have UserFactory, need Client/Project/TimeEntry/Invoice factories
- **Authorization**: Test policy checks in feature tests with different users
- **Edge Cases**: 
  - Multiple active timers (should fail)
  - Rate cascade with nulls at each level
  - Invoice creation with already-invoiced entries
  - Duration calculation with various time ranges

### 2.3 Priority Test Cases

**Critical Path Tests** (implement first):
1. `TimeEntryTest::test_cannot_start_multiple_timers()` - One active timer constraint
2. `TimeEntryTest::test_rate_cascade_resolves_correctly()` - Entry → Project → Client → 0
3. `InvoiceTest::test_marks_time_entries_as_invoiced()` - Workflow integrity
4. `InvoiceTest::test_only_includes_unbilled_entries()` - No double billing
5. `InvoiceTest::test_auto_generates_invoice_number()` - Format INV-YYYY-0001

**Model Tests** (unit):
6. `ProjectTest::test_total_amount_calculation()` - Aggregate with rate cascade
7. `InvoiceTest::test_calculate_totals_with_tax()` - Subtotal + tax = total

**Controller Tests** (feature):
8. `TimeEntryControllerTest::test_authorized_user_can_view_own_entries()`
9. `InvoiceControllerTest::test_pdf_generation()`

### 2.4 Factory Definitions
Create in `database/factories/`:
```php
// ClientFactory.php
'user_id' => User::factory(),
'name' => fake()->company(),
'email' => fake()->companyEmail(),
'hourly_rate' => fake()->randomFloat(2, 50, 200),
'is_active' => true

// ProjectFactory.php
'client_id' => Client::factory(),
'user_id' => fn($attrs) => Client::find($attrs['client_id'])->user_id,
'name' => fake()->words(3, true),
'hourly_rate' => fake()->randomFloat(2, 75, 250),
'status' => 'active'

// TimeEntryFactory.php
'project_id' => Project::factory(),
'user_id' => fn($attrs) => Project::find($attrs['project_id'])->user_id,
'start_time' => now()->subHours(2),
'end_time' => now(),
'duration' => 120, // 2 hours
'is_billable' => true
```

## Phase 3: Migration Strategy

### 3.1 Approach
- **Incremental**: Refactor one service at a time
- **Test-First**: Write tests before extracting logic
- **Backward Compatible**: Keep controller methods working during transition
- **No Breaking Changes**: API stays the same

### 3.2 Rollout Order
1. BillingService (smallest, most reusable)
2. TimeEntryService (isolated, clear boundaries)
3. InvoiceService (depends on BillingService)
4. AnalyticsService (last, dashboard-only)

### 3.3 Example Refactor (InvoiceController::store)

**Before**:
```php
public function store(Request $request) {
    // 80 lines of business logic mixed with HTTP concerns
}
```

**After**:
```php
public function store(Request $request) {
    $validated = $request->validate([...]);
    
    $invoice = app(InvoiceService::class)->createFromTimeEntries(
        clientId: $validated['client_id'],
        timeEntryIds: $validated['time_entries'] ?? [],
        data: $validated
    );
    
    return redirect()->route('invoices.show', $invoice)
        ->with('success', 'Invoice created successfully.');
}
```

## Phase 4: REST API Development (Week 4-5)

### 4.1 Why API After Services?
- **Shared Business Logic**: Services ensure web and API controllers use identical workflows
- **Zero Duplication**: Rate cascading, invoice generation, analytics computed once
- **Consistency**: Same validation, authorization, and error handling across interfaces
- **Future-Proof**: GraphQL, CLI commands, webhooks can leverage same services

### 4.2 API Structure

```
app/Http/Controllers/Api/
├── AuthController.php # Sanctum token authentication
├── ClientController.php # Client CRUD (uses existing services)
├── ProjectController.php # Project CRUD
├── TimeEntryController.php # Timer + manual entry (uses TimeEntryService)
├── InvoiceController.php # Invoice workflow (uses InvoiceService)
└── DashboardController.php # Analytics (uses AnalyticsService)
```

### 4.3 API Resources (JSON Transformers)

```
app/Http/Resources/
├── ClientResource.php # Client JSON structure
├── ProjectResource.php # Project with nested client
├── TimeEntryResource.php # Entry with calculated amounts
├── InvoiceResource.php # Invoice with items collection
├── InvoiceItemResource.php # Individual line items
└── DashboardStatsResource.php # Stats + chart data
```


**Example Resource**:
```php
// TimeEntryResource.php
public function toArray($request) {
    return [
        'id' => $this->id,
        'project' => new ProjectResource($this->whenLoaded('project')),
        'description' => $this->description,
        'start_time' => $this->start_time,
        'end_time' => $this->end_time,
        'duration' => $this->duration,
        'hourly_rate' => $this->hourly_rate, // From cascade
        'amount' => $this->amount, // Calculated via BillingService
        'is_billable' => $this->is_billable,
        'is_invoiced' => $this->is_invoiced,
    ];
}
```

### 4.4 API Routes (routes/api.php)

```php
<?php
use App\Http\Controllers\Api;

Route::post('login', [Api\AuthController::class, 'login']);
Route::post('register', [Api\AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [Api\AuthController::class, 'logout']);
    Route::get('user', [Api\AuthController::class, 'user']);
    
    // Clients
    Route::apiResource('clients', Api\ClientController::class);
    
    // Projects
    Route::apiResource('projects', Api\ProjectController::class);
    Route::get('clients/{client}/projects', [Api\ProjectController::class, 'byClient']);
    
    // Time Entries
    Route::apiResource('time-entries', Api\TimeEntryController::class);
    Route::post('time-entries/{timeEntry}/stop', [Api\TimeEntryController::class, 'stop']);
    Route::get('time-entries/active', [Api\TimeEntryController::class, 'active']);
    
    // Invoices
    Route::apiResource('invoices', Api\InvoiceController::class);
    Route::get('clients/{client}/unbilled-entries', [Api\InvoiceController::class, 'unbilledEntries']);
    Route::get('invoices/{invoice}/pdf', [Api\InvoiceController::class, 'downloadPdf'])->name('api.invoices.pdf');
    
    // Dashboard
    Route::get('dashboard/stats', [Api\DashboardController::class, 'stats']);
    Route::get('dashboard/charts', [Api\DashboardController::class, 'charts']);
});
```

### 4.5 Example API Controller (Using Services)

```php
<?php
// Api\TimeEntryController.php
class TimeEntryController extends Controller
{
    public function __construct(
        private TimeEntryService $timeEntryService,
        private BillingService $billingService
    ) {}
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'description' => 'nullable|string',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
        ]);
        
        // Use service - same logic as web controller
        $timeEntry = $this->timeEntryService->createManualEntry(
            userId: auth()->id(),
            data: $validated
        );
        
        return new TimeEntryResource($timeEntry->load('project.client'));
    }
    
    public function stop(TimeEntry $timeEntry)
    {
        $this->authorize('update', $timeEntry);
        
        // Use service - ensures consistent duration calculation
        $timeEntry = $this->timeEntryService->stopTimer($timeEntry);
        
        return new TimeEntryResource($timeEntry);
    }
}
```

### 4.6 Authentication Strategy
 - Laravel Sanctum for API tokens
 - Token Abilities: Control permissions per token
    - client:read, client:write
    - project:read, project:write
    - time-entry:read, time-entry:write
    - invoice:read, invoice:write
 - Multiple Tokens: Mobile app, web SPA, third-party integrations
 - Token Expiration: Configurable per use case

### 4.7 API Testing Strategy

```
tests/Feature/Api/
├── AuthTest.php                 # Registration, login, logout, token management
├── ClientApiTest.php            # CRUD endpoints, authorization
├── ProjectApiTest.php           # CRUD + client relationship filtering
├── TimeEntryApiTest.php         # Timer workflow, active timer constraint
├── InvoiceApiTest.php           # Creation workflow, PDF download, unbilled queries
└── DashboardApiTest.php         # Stats calculations, chart data structure
```

**Testing Pattern:**

```php
<?php
// tests/Feature/Api/TimeEntryApiTest.php
public function test_can_start_timer_via_api()
{
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/time-entries', [
            'project_id' => $project->id,
            'description' => 'Working on feature',
        ]);
    
    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id', 'project', 'start_time', 'end_time', 'duration',
                'hourly_rate', 'amount', 'is_billable'
            ]
        ]);
        
    // Verify service was used correctly
    $this->assertDatabaseHas('time_entries', [
        'user_id' => $user->id,
        'project_id' => $project->id,
        'end_time' => null, // Timer is running
    ]);
}
```

### 4.8 API Documentation

 - Tool: Laravel Scribe or L5-Swagger
 - Auto-Generation: From routes, controllers, resources
 - Interactive Docs: /api/documentation endpoint
 - Postman Collection: Export for third-party developers
 - Examples: Include cURL, JavaScript fetch, PHP

### 4.9 API Versioning

```
routes/api.php
├── v1/
│   └── api.php    # Current stable API
└── v2/
    └── api.php    # Future breaking changes
```

URL Structure: `/api/v1/time-entries`

### 4.10 Rate Limiting

```php
<?php
// app/Providers/RouteServiceProvider.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### 4.11 Error Handling

```php
<?php
// app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    if ($request->is('api/*')) {
        return response()->json([
            'message' => $exception->getMessage(),
            'errors' => $exception instanceof ValidationException 
                ? $exception->errors() 
                : null,
        ], $this->getStatusCode($exception));
    }
    
    return parent::render($request, $exception);
}
```

### 4.12 Service Layer Benefits for API
- ✅ Invoice Creation: Both web form and API POST use InvoiceService::createFromTimeEntries()
- ✅ Rate Resolution: API responses include calculated rates via BillingService::resolveHourlyRate()
- ✅ Timer Logic: Active timer constraint enforced in TimeEntryService for both interfaces
- ✅ Analytics: Dashboard stats identical between web charts and API JSON via AnalyticsService
- ✅ Testing: Service tests cover both web and API - no duplicate test code

## Updated Timeline
- Week 1: BillingService + tests
- Week 2: TimeEntryService + InvoiceService + tests
- Week 3: AnalyticsService + tests, refactor web controllers
- Week 4: API controllers, resources, authentication setup
- Week 5: API testing, documentation, versioning, deployment

## Updated Success Metrics

 - [ ] 100% test coverage for business logic (services + models)
 - [ ] <50 lines per controller method (web + API)
 - [ ] All rate calculations use BillingService
 - [ ] Zero direct model manipulation in controllers (use services)
 - [ ] Web and API controllers share 100% of business logic via services
 - [ ] API test coverage matches web test coverage (96+ tests)
 - [ ] Interactive API documentation available
 - [ ] PHPStan level 5 compliance
 - [ ] Test suite runs in <30 seconds
