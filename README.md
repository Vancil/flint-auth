# flint-auth

<p align="center">
  <a href="https://github.com/Vancil/flint-auth/actions/workflows/ci.yml"><img src="https://img.shields.io/github/actions/workflow/status/Vancil/flint-auth/ci.yml?label=tests" alt="Tests"></a>
  <a href="https://packagist.org/packages/vancil/flint-auth"><img src="https://img.shields.io/packagist/dt/vancil/flint-auth" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/vancil/flint-auth"><img src="https://img.shields.io/packagist/v/vancil/flint-auth" alt="Latest Version on Packagist"></a>
  <a href="https://packagist.org/packages/vancil/flint-auth"><img src="https://img.shields.io/packagist/l/vancil/flint-auth" alt="License"></a>
</p>

UI scaffold package for the [Flint framework](https://github.com/Vancil/flint). Scaffolds session-based authentication with your choice of Bootstrap (server-rendered), Vue, or React frontend preset.

> **v2.0 — breaking change.** The previous JWT API auth package has been replaced entirely. See the [migration guide](#migrating-from-v1) if you are upgrading.

---

## Installation

```bash
composer require vancil/flint-auth
```

Register the package in `config/app.php`:

```php
'packages' => [
    \Vancil\FlintAuth\FlintAuth::class,
],
```

---

## Quick Start

Pick a frontend preset and add `--auth` to scaffold all authentication pages:

```bash
# Bootstrap 5 — server-rendered Spark templates
php flint ui bootstrap --auth

# Vue 3 + Vite — SPA with JSON API backend
php flint ui vue --auth

# React 18 + Vite — SPA with JSON API backend
php flint ui react --auth
```

Then run your migrations and start the server:

```bash
php flint migrate
php -S localhost:8000 -t public
```

Visit `http://localhost:8000/register` to get started.

For Vue/React presets, install JS dependencies and start the dev server:

```bash
npm install && npm run dev
```

---

## What Gets Scaffolded

Running `php flint ui <preset> --auth` publishes the following into your application:

**Migrations** (`database/migrations/`)
- `create_users_table` — id, name, email, password, remember_token, email_verified_at, timestamps
- `create_password_reset_tokens_table` — email, token, created_at
- `create_email_verifications_table` — email, token, created_at

**Models** (`app/Models/`)
- `User.php`
- `PasswordResetToken.php`
- `EmailVerification.php`

**Auth pages**
| Route | Description |
|-------|-------------|
| `GET /register` | Registration form |
| `POST /register` | Create account + send verification email |
| `GET /login` | Login form |
| `POST /login` | Authenticate + start session |
| `POST /logout` | End session |
| `GET /forgot-password` | Request a password reset link |
| `POST /forgot-password` | Send reset email |
| `GET /reset-password/{token}` | Password reset form |
| `POST /reset-password` | Apply new password |
| `GET /email/verify` | "Please verify your email" notice |
| `GET /email/verify/{token}` | Verify email address |
| `POST /email/verify/resend` | Resend verification email |
| `GET /dashboard` | Authenticated dashboard (protected by `auth` middleware) |

**Config** — `config/auth.php` and session/mail defaults written to `.env`.

---

## Presets

### Bootstrap

Server-rendered HTML using Flint's **Spark** template engine (`.spark.php` files). Bootstrap 5 is loaded from CDN — no build step required.

Controllers are published to `app/Controllers/Auth/` and use `Response::view()`, `Response::back()`, and session flash for form repopulation.

### Vue

Vite + Vue 3 SPA. Auth pages are Vue single-file components that call the JSON API endpoints (`/api/auth/*`) using `fetch` with `credentials: 'include'` so the session cookie is sent automatically.

```
resources/js/
  main.js
  router/index.js
  views/LoginView.vue
  views/RegisterView.vue
  views/ForgotPasswordView.vue
  views/ResetPasswordView.vue
  views/DashboardView.vue
```

### React

Vite + React 18 SPA. Same JSON API approach as the Vue preset.

```
resources/js/
  main.jsx
  router/AppRouter.jsx
  pages/LoginPage.jsx
  pages/RegisterPage.jsx
  pages/ForgotPasswordPage.jsx
  pages/ResetPasswordPage.jsx
  pages/DashboardPage.jsx
```

---

## Auth in Controllers

The `Flint\Auth\Auth` class is available via constructor injection anywhere in your application:

```php
use Flint\Auth\Auth;
use Flint\Request;
use Flint\Response;

class DashboardController
{
    public function __construct(private readonly Auth $auth) {}

    public function index(Request $request): Response
    {
        $user = $this->auth->user();

        return Response::view('dashboard', ['user' => $user]);
    }
}
```

| Method | Description |
|--------|-------------|
| `$auth->user()` | The authenticated user object, or `null` |
| `$auth->check()` | `true` if a user is logged in |
| `$auth->id()` | The authenticated user's ID |
| `$auth->guest()` | `true` if no user is logged in |
| `$auth->login($user, $remember)` | Log a user in; optionally set a 30-day remember-me cookie |
| `$auth->logout()` | End the session and clear remember-me cookie |

---

## Protecting Routes

Use the built-in `auth` middleware alias (registered by Flint core) to restrict routes to authenticated users:

```php
$router->group(['middleware' => ['auth']], function ($router) {
    $router->get('/dashboard',  [DashboardController::class, 'index']);
    $router->get('/settings',   [SettingsController::class, 'show']);
    $router->put('/settings',   [SettingsController::class, 'update']);
});
```

Unauthenticated visitors are automatically redirected to `/login`.

---

## Mail (Password Reset & Email Verification)

Mail is handled by Flint's built-in mailer. Set your driver in `.env`:

```env
MAIL_DRIVER=log           # default — writes to storage/logs/mail.log
MAIL_DRIVER=smtp          # sends real email
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME="My App"
```

Email templates are published to `resources/views/emails/` and are plain `.spark.php` files you can customise freely.

---

## Database

Three tables are created by the scaffolded migrations:

| Table | Purpose |
|-------|---------|
| `users` | Registered users with optional email verification and remember-me support |
| `password_reset_tokens` | Single-use tokens for password reset (expire after 1 hour) |
| `email_verifications` | Single-use tokens for email address verification |

---

## Migrating from v1

v1 used JWT Bearer tokens for API authentication. v2 uses server-side sessions.

**Removed:** `JwtMiddleware`, `RefreshMiddleware`, `RefreshToken` model, `auth:install` command, `firebase/php-jwt` dependency, `JWT_SECRET`/`JWT_ALGORITHM` config.

**Replaced with:** Flint core `Session`, `Csrf`, and `Auth` classes — no extra package needed for the auth primitives themselves.

If you need to keep JWT for a pure API application, tag your v1 install and do not upgrade.

---

## License

MIT — [Vancil](https://github.com/Vancil)
