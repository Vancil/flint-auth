# flint-auth

Authentication middleware for the [Flint framework](https://github.com/Vancil/flint). Supports Bearer token, JWT, and API key strategies.

---

## Installation

```bash
composer require vancil/flint-auth
```

---

## Setup

Register the middleware aliases in your application. Add the following to your `Application` subclass or directly in `public/index.php` after boot:

```php
use Vancil\FlintAuth\FlintAuth;

$app = new Flint\Application(BASE_PATH);
$app->boot();

FlintAuth::register($app->make(Flint\Router::class));
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

// JWT
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
| `auth.apikey` | The matched API key string |

---

## Strategies

### Bearer (`auth.bearer`)

Compares the `Authorization: Bearer <token>` header against `APP_SECRET` using a timing-safe comparison. Best for internal service-to-service calls.

### JWT (`auth.jwt`)

Validates a signed JWT from the `Authorization: Bearer <token>` header using [`firebase/php-jwt`](https://github.com/firebase/php-jwt). Verifies the signature, expiry, and algorithm. Decoded claims are passed to `Auth::user()`.

Generate a token in your auth controller:

```php
use Firebase\JWT\JWT;

$token = JWT::encode([
    'sub'   => $user['id'],
    'email' => $user['email'],
    'iat'   => time(),
    'exp'   => time() + 3600,
], config('auth.jwt_secret'), config('auth.jwt_algorithm'));

return Response::json(['token' => $token]);
```

### API Key (`auth.apikey`)

Checks the `X-API-Key` header (or `?api_key=` query param) against the list of valid keys defined in `API_KEYS`. Uses timing-safe comparison. Best for third-party integrations and webhooks.

---

## License

MIT — [Vancil](https://github.com/Vancil)
