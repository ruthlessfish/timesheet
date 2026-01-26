# Documentation

This folder contains comprehensive documentation for the Time Tracking application.

## Files

### [REFACTORING_PLAN.md](./REFACTORING_PLAN.md)
Complete refactoring plan for extracting business logic to a service layer.

**Contents**:
- Phase 1: Service extraction (BillingService, TimeEntryService, InvoiceService, AnalyticsService)
- Phase 2: Testing strategy (147 tests, 313 assertions)
- Phase 3: Controller refactoring
- Phase 4: REST API development
- Timeline and success metrics

**Status**: Phase 1 & 2 complete (51 service tests implemented)

### [TEST_SUMMARY.md](./TEST_SUMMARY.md)
Comprehensive overview of the test suite.

**Contents**:
- Test structure (Feature vs Unit tests)
- Test coverage breakdown by area
- Critical test cases documentation
- Factory usage patterns
- Testing conventions and best practices

**Current Status**: 147 tests passing, 313 assertions, 100% pass rate

### [API_AUTHENTICATION.md](./API_AUTHENTICATION.md)
Complete guide for implementing and using the REST API with Laravel Sanctum.

**Contents**:
- Authentication flows (registration, login, logout)
- Token management and abilities
- Security best practices
- CORS configuration
- Rate limiting strategies
- Client examples (JavaScript, cURL, PHP, React Native)
- Testing patterns for API endpoints

**Status**: Documentation complete, implementation pending (Phase 4)

## Quick Reference

**Service Layer** (`app/Services/`):
- `BillingService.php` - Rate resolution, amount calculations
- `TimeEntryService.php` - Timer workflows, duration calculations  
- `InvoiceService.php` - Invoice creation, PDF generation
- `AnalyticsService.php` - Dashboard statistics, revenue analysis

**Testing**:
```bash
composer run test              # Run full test suite
php artisan test --filter=X    # Run specific test class
```

**Development**:
```bash
composer run setup    # Initial setup
composer run dev      # Run all development servers
```

## Related Files

- `.github/copilot-instructions.md` - Main development guide with architecture overview
- `composer.json` - Custom scripts for setup, dev, and testing
- `phpunit.xml` - Test configuration
