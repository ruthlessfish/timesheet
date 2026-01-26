# API Versioning

## Current Version: v1

All API endpoints are versioned and accessed via the `/api/v1/` prefix.

### Base URL Structure
```
https://yourdomain.com/api/v1/{endpoint}
```

### Example Endpoints

#### Authentication
```
POST /api/v1/register
POST /api/v1/login
POST /api/v1/logout
GET  /api/v1/user
```

#### Clients
```
GET    /api/v1/clients
POST   /api/v1/clients
GET    /api/v1/clients/{id}
PUT    /api/v1/clients/{id}
DELETE /api/v1/clients/{id}
GET    /api/v1/clients/{id}/projects
GET    /api/v1/clients/{id}/unbilled-entries
```

#### Projects
```
GET    /api/v1/projects
POST   /api/v1/projects
GET    /api/v1/projects/{id}
PUT    /api/v1/projects/{id}
DELETE /api/v1/projects/{id}
```

#### Time Entries
```
GET    /api/v1/time-entries
POST   /api/v1/time-entries
GET    /api/v1/time-entries/{id}
PUT    /api/v1/time-entries/{id}
DELETE /api/v1/time-entries/{id}
GET    /api/v1/time-entries/active
POST   /api/v1/time-entries/{id}/stop
```

#### Invoices
```
GET    /api/v1/invoices
POST   /api/v1/invoices
GET    /api/v1/invoices/{id}
PUT    /api/v1/invoices/{id}
DELETE /api/v1/invoices/{id}
GET    /api/v1/invoices/{id}/pdf
```

#### Dashboard
```
GET /api/v1/dashboard/stats
GET /api/v1/dashboard/charts
```

## Versioning Strategy

### Why Version v1?
- **Backward Compatibility**: Future breaking changes won't affect existing API consumers
- **Migration Path**: Users can migrate from v1 to v2 at their own pace
- **Clear Communication**: Version in URL makes API contract explicit
- **Industry Standard**: Follows REST API best practices

### Breaking Changes
Breaking changes will result in a new API version (v2, v3, etc.). Examples of breaking changes:
- Removing endpoints
- Changing request/response structure
- Modifying authentication mechanism
- Changing data types of existing fields
- Renaming fields

### Non-Breaking Changes
These can be added to the current version without incrementing:
- Adding new endpoints
- Adding optional fields to requests
- Adding new fields to responses
- Adding new query parameters
- Deprecating (but not removing) endpoints

### Future Versions
When v2 is needed, it will be added alongside v1:

```php
// routes/api.php

// API Version 1 (current)
Route::prefix('v1')->name('api.v1.')->group(function () {
    // ... existing routes
});

// API Version 2 (future)
Route::prefix('v2')->name('api.v2.')->group(function () {
    // ... new/updated routes
});
```

Both versions can coexist, allowing gradual migration.

### Version Lifecycle
1. **Active**: Current version receiving new features (v1)
2. **Maintained**: Previous version receiving bug fixes only (future: when v2 is released)
3. **Deprecated**: Version will be removed in X months (announce in advance)
4. **Retired**: Version no longer available

### Route Naming Convention
All routes follow the pattern: `api.v{version}.{resource}.{action}`

Examples:
- `api.v1.clients.index`
- `api.v1.time-entries.store`
- `api.v1.invoices.pdf`

This allows programmatic route generation:
```php
route('api.v1.clients.show', ['client' => $clientId])
// => /api/v1/clients/{id}
```

## Migration from Unversioned Routes

Previous API calls (if any existed at `/api/`) should be updated to `/api/v1/`:

```diff
- POST /api/login
+ POST /api/v1/login

- GET /api/time-entries
+ GET /api/v1/time-entries

- POST /api/invoices
+ POST /api/v1/invoices
```

## Testing
All API tests have been updated to use the v1 prefix:
- `tests/Feature/Api/AuthTest.php` - 6 tests
- `tests/Feature/Api/TimeEntryApiTest.php` - 7 tests  
- `tests/Feature/Api/InvoiceApiTest.php` - 5 tests

Run API tests:
```bash
php artisan test --filter=Api
```

## Implementation Details

### Route Structure
Located in `routes/api.php`:
```php
Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public routes
    Route::post('register', [Api\AuthController::class, 'register']);
    Route::post('login', [Api\AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // All authenticated endpoints
    });
});
```

### Named Routes
All routes are prefixed with `api.v1.` for consistency and future-proofing.

### Controllers
No controller changes required - versioning is handled at the route level.

### Resources
JSON resources remain version-agnostic. Version-specific transformations can be added later if needed.
