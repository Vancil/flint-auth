# flint-auth

JWT authentication middleware for the [Flint framework](https://github.com/Vancil/flint). Provides register, login, refresh, and logout out of the box using [`firebase/php-jwt`](https://github.com/firebase/php-jwt). Refresh tokens are stored in the database for true revocation.

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
- Publish migrations for `users` and `refresh_tokens` tables
- Generate `app/Models/User.php`
- Generate `app/Controllers/AuthController.php` with all four auth methods

Then run your migrations:

```bash
php flint migrate
```

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

## Routes

Wire up the four auth endpoints and protect your routes:

```php
$router->post('/auth/register', [AuthController::class, 'register']);
$router->post('/auth/login',    [AuthController::class, 'login']);
$router->post('/auth/refresh',  [AuthController::class, 'refresh'], ['auth.refresh']);
$router->post('/auth/logout',   [AuthController::class, 'logout'],  ['auth.refresh']);

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
| `auth.refresh` | Validates a long-lived refresh token against the database. Use on refresh and logout routes. |

---

## Token Flow

**Register / Login** — returns a token pair:

```json
{
  "access_token":  "<JWT, 15 min>",
  "refresh_token": "<JWT, 30 days>",
  "expires_in":    900,
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

A new token pair is returned. The old refresh token is revoked and replaced.

**Logout** — send the refresh token to `/auth/logout`. The token is immediately revoked in the database.

```
Authorization: Bearer <refresh_token>
```

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
        ]);
    }
}
```

`Auth::check()` returns `true` if a user has been authenticated on the current request.

---

## Database

`auth:install` publishes two migrations:

| Table | Purpose |
|-------|---------|
| `users` | Stores registered users (`name`, `email`, `password`) |
| `refresh_tokens` | Tracks active refresh tokens by `jti` for revocation |

---

## License

MIT — [Vancil](https://github.com/Vancil)
