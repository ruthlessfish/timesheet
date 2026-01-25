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

## Phase 2: Implement Testing Strategy (Week 2-3)

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

## Success Metrics

- [ ] 100% test coverage for business logic (services + models)
- [ ] <50 lines per controller method
- [ ] All rate calculations use BillingService
- [ ] Zero direct model manipulation in controllers (use services)
- [ ] PHPStan level 5 compliance
- [ ] Test suite runs in <30 seconds

## Timeline

- **Week 1**: BillingService + tests
- **Week 2**: TimeEntryService + InvoiceService + tests
- **Week 3**: AnalyticsService + tests, refactor controllers
- **Week 4**: Documentation, edge cases, CI/CD integration
