# flint-auth

Authentication middleware for the [Flint framework](https://github.com/Vancil/flint). Supports Bearer token, JWT, and API key strategies.

---

## Installation

```bash
composer require vancil/flint-auth
```

---

## Quick Start

Run the interactive installer to scaffold everything automatically:

```bash
php flint auth:install
```

Or pass a strategy flag to skip the prompt:

```bash
php flint auth:install --jwt
php flint auth:install --bearer
php flint auth:install --apikey
```

The installer will:
- Write the required secrets to your `.env`
- Create `config/auth.php` if it doesn't exist
- Register `FlintAuth` in `config/app.php` if it isn't already
- Generate a starter `AuthController` (JWT only)

---

## Manual Setup

Add `FlintAuth` to the `packages` array in `config/app.php` — no other registration needed:

```php
'packages' => [
    \Vancil\FlintAuth\FlintAuth::class,
],
```

Add the required config to your `.env`:

```env
# JWT
JWT_SECRET=your-secret-key
JWT_ALGORITHM=HS256

# API Key (comma-separated list of valid keys)
API_KEYS=key-one,key-two,key-three
```

Add `config/auth.php` to your project:

```php
return [
    'jwt_secret'     => env('JWT_SECRET', ''),
    'jwt_algorithm'  => env('JWT_ALGORITHM', 'HS256'),
    'api_key_header' => env('API_KEY_HEADER', 'X-API-Key'),
    'api_keys'       => array_filter(explode(',', env('API_KEYS', ''))),
];
```

---

## Usage

Apply middleware to routes using the registered aliases:

```php
// Bearer token (validated against APP_SECRET)
$router->group(['middleware' => ['auth.bearer']], function ($router) {
    $router->get('/admin/stats', [AdminController::class, 'stats']);
});

// JWT (access token required)
$router->post('/auth/login',   [AuthController::class, 'login']);
$router->post('/auth/refresh', [AuthController::class, 'refresh'], ['auth.refresh']);

$router->group(['middleware' => ['auth.jwt']], function ($router) {
    $router->get('/profile', [ProfileController::class, 'show']);
    $router->put('/profile', [ProfileController::class, 'update']);
});

// API key (X-API-Key header or ?api_key= query param)
$router->group(['middleware' => ['auth.apikey']], function ($router) {
    $router->get('/webhooks', [WebhookController::class, 'index']);
});
```

---

## Accessing the Authenticated User

After successful authentication, the resolved identity is available anywhere via `Auth::user()`:

```php
use Vancil\FlintAuth\Auth;

class ProfileController
{
    public function show(): Response
    {
        $claims = Auth::user(); // JWT stdClass, bearer token string, or API key string

        return Response::json([
            'sub'   => $claims->sub,
            'email' => $claims->email,
        ]);
    }
}
```

| Strategy | `Auth::user()` returns |
|----------|------------------------|
| `auth.bearer` | The bearer token string |
| `auth.jwt` | `stdClass` of decoded JWT claims |
| `auth.refresh` | `stdClass` of decoded refresh token claims |
| `auth.apikey` | The matched API key string |

---

## Strategies

### Bearer (`auth.bearer`)

Compares the `Authorization: Bearer <token>` header against `APP_SECRET` using a timing-safe comparison. Best for internal service-to-service calls.

### JWT (`auth.jwt`)

Validates a signed JWT from the `Authorization: Bearer <token>` header using [`firebase/php-jwt`](https://github.com/firebase/php-jwt). Verifies the signature, expiry, and algorithm. Decoded claims are passed to `Auth::user()`.

Access tokens must have `"type": "access"` (or no type claim). Refresh tokens passed to a JWT-protected route are rejected automatically.

### Token Refresh (`auth.refresh`)

Validates a long-lived refresh token and makes its claims available via `Auth::user()`. Use this on your `/auth/refresh` endpoint to issue new tokens without re-authenticating.

```php
$router->post('/auth/login',   [AuthController::class, 'login']);
$router->post('/auth/refresh', [AuthController::class, 'refresh'], ['auth.refresh']);
```

`auth:install --jwt` generates a starter `AuthController` with both `login()` and `refresh()` methods. The login response shape:

```json
{
  "access_token":  "<short-lived JWT, 1h>",
  "refresh_token": "<long-lived JWT, 30d>",
  "expires_in":    3600,
  "token_type":    "Bearer"
}
```

Send the `refresh_token` to `/auth/refresh` with `Authorization: Bearer <refresh_token>` to receive a new token pair.

### API Key (`auth.apikey`)

Checks the `X-API-Key` header (or `?api_key=` query param) against the list of valid keys defined in `API_KEYS`. Uses timing-safe comparison. Best for third-party integrations and webhooks.

---

## License

MIT — [Vancil](https://github.com/Vancil)
