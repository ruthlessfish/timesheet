# API Rate Limiting

## Overview

The Timeshit API implements rate limiting to prevent abuse and ensure fair usage across all users. Rate limits are applied per user (for authenticated requests) or per IP address (for unauthenticated requests).

## Rate Limits

### General API Endpoints
- **Limit**: 60 requests per minute
- **Applies to**: All `/api/v1/*` endpoints
- **Tracking**: By authenticated user ID or IP address (for guests)

### Authentication Endpoints
- **Limit**: 5 requests per minute (stricter to prevent brute force attacks)
- **Applies to**: 
  - `POST /api/v1/register`
  - `POST /api/v1/login`
- **Tracking**: By IP address only

## HTTP Response Headers

All API responses include rate limit information in the headers:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```

- `X-RateLimit-Limit`: Total requests allowed in the time window
- `X-RateLimit-Remaining`: Number of requests remaining in current window

## HTTP 429 Response

When rate limit is exceeded, the API returns a `429 Too Many Requests` status:

```http
HTTP/1.1 429 Too Many Requests
Content-Type: application/json

{
  "message": "Too Many Requests"
}
```

## Implementation Details

### Configuration

Rate limiters are configured in `app/Providers/AppServiceProvider.php`:

```php
protected function configureRateLimiting(): void
{
    // General API rate limiter
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    // Stricter limit for authentication endpoints
    RateLimiter::for('auth', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });
}
```

### Route Middleware

Routes apply rate limiting via middleware in `routes/api.php`:

```php
// All API routes limited to 60/min
Route::prefix('v1')->middleware('throttle:api')->group(function () {
    
    // Auth routes limited to 5/min
    Route::middleware('throttle:auth')->group(function () {
        Route::post('register', [Api\AuthController::class, 'register']);
        Route::post('login', [Api\AuthController::class, 'login']);
    });
    
    // Protected routes (limited by user ID when authenticated)
    Route::middleware('auth:sanctum')->group(function () {
        // ... resources
    });
});
```

## Per-User Isolation

Authenticated users have their own rate limit quota:

- User A can make 60 requests/minute
- User B can make 60 requests/minute
- Limits are independent (User A's usage doesn't affect User B)

## Best Practices for API Clients

1. **Monitor Headers**: Check `X-RateLimit-Remaining` to avoid hitting limits
2. **Implement Backoff**: If you receive 429, wait before retrying
3. **Cache Responses**: Reduce unnecessary API calls by caching data
4. **Batch Operations**: Group multiple updates when possible
5. **Authenticate Requests**: Authenticated users get per-user limits (better than shared IP limits)

## Testing

Rate limiting behavior is verified in `tests/Feature/Api/RateLimitingTest.php`:

- ✅ API endpoints return 429 after 60 requests
- ✅ Auth endpoints return 429 after 5 requests  
- ✅ Rate limits are isolated per user
- ✅ Rate limit headers are present in all responses

## Customization

To adjust rate limits, modify `app/Providers/AppServiceProvider.php`:

```php
// Increase general API limit to 120/min
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
});

// Add per-hour limit alongside per-minute
RateLimiter::for('api', function (Request $request) {
    return [
        Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()),
        Limit::perHour(1000)->by($request->user()?->id ?: $request->ip()),
    ];
});
```

## Production Considerations

1. **Cache Driver**: Ensure Laravel cache is configured (Redis recommended for production)
2. **Load Balancer**: If behind load balancer, configure trusted proxies to get real IP addresses
3. **Monitoring**: Track 429 responses to identify potential abuse or legitimate high-usage scenarios
4. **Gradual Limits**: Consider implementing graduated limits (e.g., 60/min, 1000/hour, 10000/day)

## Security Benefits

- **Brute Force Protection**: Auth endpoints limited to 5/min prevents password guessing
- **DoS Prevention**: General 60/min limit prevents resource exhaustion
- **Fair Usage**: Per-user limits ensure no single user monopolizes API resources
- **IP-based Fallback**: Unauthenticated requests tracked by IP to prevent anonymous abuse
