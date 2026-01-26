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

## Phase 1: Extract Services (Week 1-3) ✅ COMPLETE

### 1.1 Create Service Structure ✅
```
app/Services/
├── BillingService.php      # ✅ Rate resolution, amount calculations (8 methods)
├── TimeEntryService.php    # ✅ Timer management, duration calculation (10 methods)
├── InvoiceService.php      # ✅ Invoice creation, PDF generation (11 methods)
└── AnalyticsService.php    # ✅ Dashboard stats, charts data (11 methods)
```

**Test Coverage**: 51 service tests, 156 assertions, 100% passing

### 1.2 Extract Billing Logic ✅
**Implemented**: `app/Services/BillingService.php`
- ✅ `resolveHourlyRate($timeEntry)` - 4-level cascade: entry → project → client → 0
- ✅ `calculateAmount($timeEntry)` - Duration * resolved rate with hours conversion
- ✅ `getUnbilledTimeEntries($clientId, $userId)` - Filters: billable, not invoiced, has end_time
- ✅ `calculateTotalAmount($timeEntries)` - Collection aggregation using rate cascade
- ✅ `calculateTotalHours($timeEntries)` - Minutes to hours conversion
- ✅ `markAsInvoiced($timeEntryIds)` - Bulk update for invoice workflow
- ✅ `markAsNotInvoiced($timeEntryIds)` - Bulk update for invoice deletion

**Tests**: 10 tests covering all rate cascade paths, unbilled filtering, bulk operations

### 1.3 Extract Time Entry Logic ✅
**Implemented**: `app/Services/TimeEntryService.php`
- ✅ `startTimer($userId, $projectId, $data)` - Validates no active timer, throws exception if exists
- ✅ `stopTimer($timeEntry)` - Sets end_time, calculates duration via model method
- ✅ `createManualEntry($userId, $data)` - Creates with or without end_time, auto-calculates duration
- ✅ `updateEntry($timeEntry, $data)` - Updates and recalculates duration if times changed
- ✅ `getActiveTimer($userId)` - Returns single active timer or null
- ✅ `getEntriesForUser($userId, $filters)` - Supports project, date range, billable filtering
- ✅ `calculateTotalHours($userId, $filters)` - Delegates to BillingService
- ✅ `calculateTotalAmount($userId, $filters)` - Delegates to BillingService

**Tests**: 15 tests covering timer workflow, active constraint, filtering, edge cases

### 1.4 Extract Invoice Logic ✅
**Implemented**: `app/Services/InvoiceService.php`
- ✅ `createFromTimeEntries($userId, $clientId, $timeEntryIds, $data)` - Full workflow with marking
- ✅ `addTimeEntryToInvoice($invoice, $timeEntry)` - Creates InvoiceItem with rate resolution
- ✅ `updateTotals($invoice)` - Recalculates subtotal, tax, total
- ✅ `updateInvoice($invoice, $data)` - Updates invoice fields
- ✅ `deleteInvoice($invoice)` - Deletes and unmarks time entries via BillingService
- ✅ `getUnbilledEntriesForClient($clientId, $userId)` - Delegates to BillingService
- ✅ `calculatePreviewTotals($timeEntryIds, $taxRate)` - Pre-creation totals calculation
- ✅ `generatePDF($invoice)` - Returns Dompdf instance for download
- ✅ `markAsSent($invoice)` - Status transition to 'sent'
- ✅ `markAsPaid($invoice)` - Status transition to 'paid'
- ✅ `markAsOverdue($invoice)` - Status transition to 'overdue'

**Tests**: 13 tests covering creation workflow, PDF generation, deletion unmarking, status transitions

### 1.5 Extract Analytics Logic ✅
**Implemented**: `app/Services/AnalyticsService.php`
- ✅ `getDashboardStats($userId)` - Returns clients, projects, hours, revenue
- ✅ `getTotalClients($userId)` - Active clients count
- ✅ `getActiveProjects($userId)` - Active projects count
- ✅ `getMonthlyHours($userId, $month)` - Total hours for specified month
- ✅ `getMonthlyRevenue($userId, $month)` - Billable revenue via BillingService
- ✅ `getDailyHoursTimeSeries($userId, $days)` - Last N days data for charts
- ✅ `getProjectHoursBreakdown($userId, $limit)` - Top N projects by hours
- ✅ `getBillableRatio($userId, $startDate, $endDate)` - Billable vs non-billable breakdown
- ✅ `getRevenueByClient($userId, $startDate, $endDate)` - Client revenue ranking
- ✅ `getTimeEntriesStats($userId, $startDate, $endDate)` - Comprehensive entry statistics
- ✅ `getAverageHourlyRate($userId)` - Weighted average across all work

**Tests**: 13 tests covering all analytics methods, edge cases, zero-entry handling

## Phase 2: Implement Testing Strategy ✅ COMPLETE

**Current Status**: 147 tests, 313 assertions, 100% passing

### 2.1 Test Structure ✅
```
tests/
├── Feature/
│   ├── Auth/              # ✅ Complete (Breeze - 17 tests)
│   ├── ClientTest.php     # ✅ Complete (8 tests) - CRUD, authorization
│   ├── ProjectTest.php    # ✅ Complete (9 tests) - CRUD, authorization, relationships
│   ├── TimeEntryTest.php  # ✅ Complete (14 tests) - Timer workflow, validation, filtering
│   └── InvoiceTest.php    # ✅ Complete (14 tests) - Workflow, PDF, unbilled filtering
├── Unit/
│   ├── Models/
│   │   ├── TimeEntryTest.php    # ✅ Complete (11 tests) - Rate cascade, duration, amount
│   │   ├── ProjectTest.php      # ✅ Complete (6 tests) - Aggregation attributes
│   │   └── InvoiceTest.php      # ✅ Complete (9 tests) - Auto-numbering, totals, tax
│   └── Services/
│       ├── BillingServiceTest.php    # ✅ Complete (10 tests)
│       ├── TimeEntryServiceTest.php  # ✅ Complete (15 tests)
│       ├── InvoiceServiceTest.php    # ✅ Complete (13 tests)
│       └── AnalyticsServiceTest.php  # ✅ Complete (13 tests)
```

### 2.2 Testing Conventions ✅
- ✅ **Use `RefreshDatabase` trait** - All tests use this
- ✅ **Factories**: Client, Project, TimeEntry, Invoice factories exist and working
- ✅ **Authorization**: Policy checks tested in feature tests with multiple users
- ✅ **Edge Cases Covered**: 
  - ✅ Multiple active timers (throws exception)
  - ✅ Rate cascade with nulls at each level (4 test scenarios)
  - ✅ Invoice creation workflow (marks entries, calculates totals)
  - ✅ Duration calculation with various time ranges

### 2.3 Priority Test Cases ✅ All Implemented

**Critical Path Tests**:
1. ✅ `TimeEntryServiceTest::test_throws_exception_if_user_has_active_timer()` - Constraint verified
2. ✅ `BillingServiceTest::test_resolves_rate_*` (4 tests) - All cascade levels covered
3. ✅ `InvoiceServiceTest::test_marks_time_entries_as_invoiced()` - Workflow integrity
4. ✅ `InvoiceServiceTest::test_creates_invoice_from_time_entries()` - Full workflow
5. ✅ `InvoiceTest::it_auto_generates_invoice_number_on_creation()` - Format INV-YYYY-0001

**Model Tests** (unit):
6. ✅ `ProjectTest::total_amount_respects_rate_cascade()` - Aggregate with cascade
7. ✅ `InvoiceTest::calculate_totals_applies_tax_correctly()` - Subtotal + tax = total

**Controller Tests** (feature):
8. ✅ `TimeEntryTest::user_can_view_their_time_entries()` - Authorization working
9. ✅ `InvoiceTest::user_can_generate_pdf_for_their_invoice()` - PDF generation verified

## Phase 3: Refactor Controllers to Use Services (Week 4) 🔄 IN PROGRESS

### 3.1 Approach
- **Incremental**: Refactor one controller at a time
- **Test-First**: Verify existing tests still pass after refactoring
- **Backward Compatible**: Keep controller methods working during transition
- **No Breaking Changes**: HTTP API stays the same
- **Dependency Injection**: Inject services via constructor

### 3.2 Controllers to Refactor
1. ⏳ **DashboardController** - Replace inline analytics with AnalyticsService
2. ⏳ **InvoiceController** - Replace store/destroy/pdf logic with InvoiceService
3. ⏳ **TimeEntryController** - Replace timer/duration logic with TimeEntryService
4. ✅ **Already service-based**: ClientController, ProjectController (minimal logic)

### 3.3 Example Refactor (DashboardController::index)

**Before**:
```php
public function index() {
    $user = auth()->user();
    
    // 80+ lines of inline queries and calculations
    $totalClients = $user->clients()->where('is_active', true)->count();
    $monthlyMinutes = $user->timeEntries()
        ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
        ->sum('duration');
    // ... many more queries
}
```

**After**:
```php
public function __construct(
    private AnalyticsService $analyticsService,
    private TimeEntryService $timeEntryService
) {}

public function index() {
    $user = auth()->user();
    
    $stats = $this->analyticsService->getDashboardStats($user->id);
    $last7Days = $this->analyticsService->getDailyHoursTimeSeries($user->id, 7);
    $projectHours = $this->analyticsService->getProjectHoursBreakdown($user->id, 5);
    $billableRatio = $this->analyticsService->getBillableRatio($user->id);
    $activeTimer = $this->timeEntryService->getActiveTimer($user->id);
    $recentTimeEntries = $this->timeEntryService->getEntriesForUser($user->id, ['limit' => 10]);
    
    return view('dashboard', compact(
        'stats', 'last7Days', 'projectHours', 'billableRatio',
        'activeTimer', 'recentTimeEntries'
    ));
}
```

### 3.4 Example Refactor (InvoiceController::store)

**Before**:
```php
public function store(Request $request) {
    // 80 lines of business logic mixed with HTTP concerns
    $timeEntries = TimeEntry::whereIn('id', $request->time_entries)->get();
    foreach ($timeEntries as $entry) {
        $rate = $entry->hourly_rate ?? $entry->project->hourly_rate ?? ...
        // Manual rate resolution, item creation, marking...
    }
}
```

**After**:
```php
public function __construct(private InvoiceService $invoiceService) {}

public function store(Request $request) {
    $validated = $request->validate([
        'client_id' => 'required|exists:clients,id',
        'time_entries' => 'nullable|array',
        'issue_date' => 'required|date',
        'due_date' => 'required|date|after:issue_date',
        'tax_rate' => 'nullable|numeric|min:0|max:100',
        'notes' => 'nullable|string',
    ]);
    
    $invoice = $this->invoiceService->createFromTimeEntries(
        userId: auth()->id(),
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

**Completed**:
- ✅ Week 1: BillingService + 10 tests (Jan 24, 2026)
- ✅ Week 2: TimeEntryService + 15 tests (Jan 24, 2026)
- ✅ Week 2: InvoiceService + 13 tests (Jan 25, 2026)
- ✅ Week 3: AnalyticsService + 13 tests (Jan 25, 2026)

**Remaining**:
- 🔄 Week 4 (Current): Refactor web controllers to use services
  - DashboardController → AnalyticsService
  - InvoiceController → InvoiceService + BillingService
  - TimeEntryController → TimeEntryService
  - Verify all 147 tests still pass

- Week 5: REST API - Controllers & Resources
  - API controllers using existing services
  - JSON resources for transformations
  - Sanctum authentication setup

- Week 6: REST API - Testing & Documentation
  - Feature tests for all API endpoints
  - Interactive API documentation (Scribe/Swagger)
  - Postman collection export
  - Rate limiting & error handling

## Updated Success Metrics

**Completed** ✅:
- ✅ 100% test coverage for business logic (51 service tests + 26 model tests)
- ✅ All rate calculations use BillingService (4-level cascade tested)
- ✅ Zero direct model manipulation in service layer
- ✅ Test suite runs in <3 seconds (currently 2.07s)
- ✅ 147 tests passing, 313 assertions

**In Progress** 🔄:
- 🔄 <50 lines per controller method (refactoring in progress)
- 🔄 Zero business logic in controllers (moving to services)

**Pending** ⏳:
- ⏳ Web and API controllers share 100% of business logic via services
- ⏳ API test coverage (target: 50+ API tests)
- ⏳ Interactive API documentation available
- ⏳ PHPStan level 5 compliance
- ⏳ API versioning strategy implemented
