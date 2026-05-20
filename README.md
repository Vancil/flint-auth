# flint-auth

JWT authentication middleware for the [Flint framework](https://github.com/Vancil/flint). Issues short-lived access tokens and long-lived refresh tokens using [`firebase/php-jwt`](https://github.com/firebase/php-jwt).

---

## Installation

```bash
composer require vancil/flint-auth
```

---

## Quick Start

Run the installer to scaffold everything automatically:

```bash
php flint auth:install
```

The installer will:
- Generate a `JWT_SECRET` and write it to your `.env`
- Create `config/auth.php`
- Register `FlintAuth` in `config/app.php`
- Generate a starter `AuthController` with `login()` and `refresh()` methods

---

## Manual Setup

Add `FlintAuth` to the `packages` array in `config/app.php`:

```php
'packages' => [
    \Vancil\FlintAuth\FlintAuth::class,
],
```

Add to your `.env`:

```env
JWT_SECRET=your-secret-key
JWT_ALGORITHM=HS256
```

Add `config/auth.php`:

```php
return [
    'jwt_secret'    => env('JWT_SECRET', ''),
    'jwt_algorithm' => env('JWT_ALGORITHM', 'HS256'),
];
```

---

## Usage

```php
$router->post('/auth/login',   [AuthController::class, 'login']);
$router->post('/auth/refresh', [AuthController::class, 'refresh'], ['auth.refresh']);

$router->group(['middleware' => ['auth.jwt']], function ($router) {
    $router->get('/profile', [ProfileController::class, 'show']);
    $router->put('/profile', [ProfileController::class, 'update']);
});
```

---

## Middleware

| Alias | Purpose |
|-------|---------|
| `auth.jwt` | Validates a short-lived access token. Rejects refresh tokens. |
| `auth.refresh` | Validates a long-lived refresh token. Use only on the refresh endpoint. |

---

## Token Flow

**Login** — POST `/auth/login` returns a token pair:

```json
{
  "access_token":  "<JWT, 1h>",
  "refresh_token": "<JWT, 30d>",
  "expires_in":    3600,
  "token_type":    "Bearer"
}
```

**Authenticated requests** — send the access token:

```
Authorization: Bearer <access_token>
```

**Refresh** — when the access token expires, send the refresh token to `/auth/refresh`:

```
Authorization: Bearer <refresh_token>
```

A new token pair is returned. The refresh token rotates on every use.

---

## Accessing the Authenticated User

After a successful request, the decoded JWT claims are available anywhere via `Auth::user()`:

```php
use Vancil\FlintAuth\Auth;

class ProfileController
{
    public function show(): Response
    {
        $claims = Auth::user();

        return Response::json([
            'sub'   => $claims->sub,
            'email' => $claims->email,
        ]);
    }
}
```

`Auth::check()` returns `true` if a user has been authenticated on the current request.

---

## License

MIT — [Vancil](https://github.com/Vancil)
