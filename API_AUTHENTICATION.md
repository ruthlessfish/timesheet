# API Authentication Flow - Laravel Sanctum

## Overview

The API uses **Laravel Sanctum** for token-based authentication. This provides a simple way to authenticate SPA applications, mobile apps, and third-party integrations.

## Authentication Flows

### 1. Registration Flow

**Endpoint**: `POST /api/register`

**Request**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secure-password",
  "password_confirmation": "secure-password"
}
```

**Response** (201 Created):
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2026-01-25T10:00:00.000000Z"
  },
  "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz123456789",
  "token_type": "Bearer"
}
```

**Implementation**:
```php
// app/Http/Controllers/Api/AuthController.php
public function register(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
    ]);

    // Create token with abilities
    $token = $user->createToken('api-token', [
        'client:read', 'client:write',
        'project:read', 'project:write',
        'time-entry:read', 'time-entry:write',
        'invoice:read', 'invoice:write',
    ])->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token,
        'token_type' => 'Bearer',
    ], 201);
}
```

---

### 2. Login Flow

**Endpoint**: `POST /api/login`

**Request**:
```json
{
  "email": "john@example.com",
  "password": "secure-password"
}
```

**Response** (200 OK):
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "2|XyZaBcDeFgHiJkLmNoPqRsTuVwXyZ987654321",
  "token_type": "Bearer"
}
```

**Error Response** (401 Unauthorized):
```json
{
  "message": "The provided credentials are incorrect."
}
```

**Implementation**:
```php
public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($credentials)) {
        return response()->json([
            'message' => 'The provided credentials are incorrect.'
        ], 401);
    }

    $user = Auth::user();

    // Optionally revoke previous tokens (single session)
    // $user->tokens()->delete();

    $token = $user->createToken('api-token', [
        'client:read', 'client:write',
        'project:read', 'project:write',
        'time-entry:read', 'time-entry:write',
        'invoice:read', 'invoice:write',
    ])->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token,
        'token_type' => 'Bearer',
    ]);
}
```

---

### 3. Logout Flow

**Endpoint**: `POST /api/logout`

**Headers**:
```
Authorization: Bearer 2|XyZaBcDeFgHiJkLmNoPqRsTuVwXyZ987654321
```

**Response** (200 OK):
```json
{
  "message": "Successfully logged out"
}
```

**Implementation**:
```php
public function logout(Request $request)
{
    // Revoke current token
    $request->user()->currentAccessToken()->delete();

    // Or revoke all tokens
    // $request->user()->tokens()->delete();

    return response()->json([
        'message' => 'Successfully logged out'
    ]);
}
```

---

### 4. Get Current User

**Endpoint**: `GET /api/user`

**Headers**:
```
Authorization: Bearer 2|XyZaBcDeFgHiJkLmNoPqRsTuVwXyZ987654321
```

**Response** (200 OK):
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "created_at": "2026-01-25T10:00:00.000000Z"
}
```

**Implementation**:
```php
public function user(Request $request)
{
    return response()->json($request->user());
}
```

---

## Token Abilities (Permissions)

### Ability-Based Authorization

**Define Token Abilities**:
```php
// Creating token with specific abilities
$token = $user->createToken('mobile-app', [
    'client:read',
    'project:read',
    'time-entry:read',
    'time-entry:write',
])->plainTextToken;
```

**Check Abilities in Controller**:
```php
// app/Http/Controllers/Api/ClientController.php
public function destroy(Client $client)
{
    // Check if token has delete ability
    if (!auth()->user()->tokenCan('client:write')) {
        return response()->json([
            'message' => 'Unauthorized - insufficient token permissions'
        ], 403);
    }

    $this->authorize('delete', $client);
    $client->delete();

    return response()->json(null, 204);
}
```

**Middleware Approach**:
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'abilities:client:write'])
    ->delete('/clients/{client}', [ClientController::class, 'destroy']);
```

### Ability Examples

| Ability | Description | Use Case |
|---------|-------------|----------|
| `client:read` | View clients | Mobile app read-only |
| `client:write` | Create/update/delete clients | Full admin access |
| `time-entry:read` | View time entries | Reporting integration |
| `time-entry:write` | Start/stop timers | Mobile timer app |
| `invoice:read` | View invoices | Accounting software |
| `invoice:write` | Create invoices | Automated billing |

---

## Token Types & Use Cases

### 1. Full Access Token (Web SPA)
```php
$token = $user->createToken('spa-token', ['*'])->plainTextToken;
```
- **Use Case**: Single-page application (React, Vue)
- **Abilities**: All (`*`)
- **Expiration**: Session-based or long-lived

### 2. Mobile App Token
```php
$token = $user->createToken('mobile-app', [
    'client:read',
    'project:read',
    'time-entry:read', 'time-entry:write',
])->plainTextToken;
```
- **Use Case**: iOS/Android time tracking app
- **Abilities**: Limited to time entry management
- **Expiration**: 30 days

### 3. Third-Party Integration Token
```php
$token = $user->createToken('zapier-integration', [
    'invoice:read',
])->plainTextToken;
```
- **Use Case**: Zapier, Make.com automation
- **Abilities**: Read-only for invoices
- **Expiration**: Never (until manually revoked)

### 4. Reporting Token
```php
$token = $user->createToken('reporting-dashboard', [
    'dashboard:read',
    'time-entry:read',
    'project:read',
])->plainTextToken;
```
- **Use Case**: Analytics dashboard
- **Abilities**: Read-only for stats
- **Expiration**: 90 days

---

## Token Management

### List User Tokens

**Endpoint**: `GET /api/tokens`

**Response**:
```json
{
  "tokens": [
    {
      "id": 1,
      "name": "mobile-app",
      "abilities": ["client:read", "time-entry:write"],
      "last_used_at": "2026-01-25T14:30:00.000000Z",
      "created_at": "2026-01-20T10:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "spa-token",
      "abilities": ["*"],
      "last_used_at": "2026-01-25T15:00:00.000000Z",
      "created_at": "2026-01-25T09:00:00.000000Z"
    }
  ]
}
```

**Implementation**:
```php
public function tokens(Request $request)
{
    return response()->json([
        'tokens' => $request->user()->tokens->map(fn($token) => [
            'id' => $token->id,
            'name' => $token->name,
            'abilities' => $token->abilities,
            'last_used_at' => $token->last_used_at,
            'created_at' => $token->created_at,
        ])
    ]);
}
```

### Revoke Specific Token

**Endpoint**: `DELETE /api/tokens/{tokenId}`

**Implementation**:
```php
public function revokeToken(Request $request, $tokenId)
{
    $request->user()->tokens()->where('id', $tokenId)->delete();

    return response()->json([
        'message' => 'Token revoked successfully'
    ]);
}
```

### Revoke All Tokens (Logout from all devices)

**Endpoint**: `POST /api/tokens/revoke-all`

**Implementation**:
```php
public function revokeAllTokens(Request $request)
{
    $request->user()->tokens()->delete();

    return response()->json([
        'message' => 'All tokens revoked successfully'
    ]);
}
```

---

## Security Best Practices

### 1. Token Storage

**Mobile Apps (React Native, Flutter)**:
```javascript
// Store in secure storage
import AsyncStorage from '@react-native-async-storage/async-storage';

await AsyncStorage.setItem('api_token', token);
```

**Web SPA (React, Vue)**:
```javascript
// Store in httpOnly cookie (preferred) or localStorage
localStorage.setItem('api_token', token);

// Include in all requests
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
```

**Never**:
- ❌ Store tokens in cookies without `httpOnly` flag
- ❌ Expose tokens in URL parameters
- ❌ Log tokens to console in production
- ❌ Share tokens between users

### 2. Token Expiration

**Configure in `config/sanctum.php`**:
```php
'expiration' => 60 * 24 * 30, // 30 days
```

**Custom Expiration per Token**:
```php
$token = $user->createToken('short-lived', ['*'], now()->addHours(2));
```

### 3. Rate Limiting

**Protect authentication endpoints**:
```php
// app/Providers/RouteServiceProvider.php
RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});
```

**Apply to routes**:
```php
// routes/api.php
Route::middleware('throttle:auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});
```

### 4. HTTPS Only

**Enforce in production**:
```php
// app/Providers/AppServiceProvider.php
if ($this->app->environment('production')) {
    URL::forceScheme('https');
}
```

### 5. CORS Configuration

**Configure in `config/cors.php`**:
```php
'paths' => ['api/*'],
'allowed_origins' => [
    'https://app.yourdomain.com',
    'capacitor://localhost', // Mobile apps
],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

---

## Client Examples

### JavaScript (Fetch)
```javascript
// Login
const response = await fetch('https://api.example.com/api/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify({
    email: 'john@example.com',
    password: 'secure-password'
  })
});

const { token, user } = await response.json();

// Use token for authenticated requests
const timeEntries = await fetch('https://api.example.com/api/time-entries', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  }
});
```

### cURL
```bash
# Login
curl -X POST https://api.example.com/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"john@example.com","password":"secure-password"}'

# Use token
curl https://api.example.com/api/time-entries \
  -H "Authorization: Bearer 2|XyZaBcDeFgHiJkLmNoPqRsTuVwXyZ987654321" \
  -H "Accept: application/json"
```

### PHP (Guzzle)
```php
use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'https://api.example.com']);

// Login
$response = $client->post('/api/login', [
    'json' => [
        'email' => 'john@example.com',
        'password' => 'secure-password',
    ]
]);

$data = json_decode($response->getBody(), true);
$token = $data['token'];

// Authenticated request
$response = $client->get('/api/time-entries', [
    'headers' => [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ]
]);
```

### React Native
```javascript
import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

// Login
async function login(email, password) {
  const { data } = await axios.post('https://api.example.com/api/login', {
    email,
    password
  });
  
  await AsyncStorage.setItem('api_token', data.token);
  axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
  
  return data.user;
}

// Load saved token on app start
async function initializeAuth() {
  const token = await AsyncStorage.getItem('api_token');
  if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
  }
}
```

---

## Testing Authentication

```php
// tests/Feature/Api/AuthTest.php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
                'token_type'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'token', 'token_type']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'The provided credentials are incorrect.'
            ]);
    }

    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $response->assertStatus(200);
        
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_token_abilities_are_enforced()
    {
        $user = User::factory()->create();
        $token = $user->createToken('limited', ['time-entry:read'])
            ->plainTextToken;

        // Can read
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/time-entries');
        $response->assertStatus(200);

        // Cannot write
        $project = Project::factory()->for($user)->create();
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/time-entries', [
                'project_id' => $project->id,
            ]);
        $response->assertStatus(403);
    }
}
```

---

## Migration Steps

### 1. Install Sanctum
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 2. Configure Sanctum

**In `app/Http/Kernel.php`**:
```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

**In `config/sanctum.php`**:
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:3000,127.0.0.1')),
'expiration' => 60 * 24 * 30, // 30 days
```

### 3. Add HasApiTokens Trait

**In `app/Models/User.php`**:
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

### 4. Create Auth Controller
```bash
php artisan make:controller Api/AuthController
```

### 5. Add Routes
```php
// routes/api.php
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
});
```
