<?php
declare(strict_types=1);

namespace Vancil\FlintAuth\Commands;

use Flint\Console\Command;
use Flint\Application;

class AuthInstall extends Command
{
    public function signature(): string { return 'auth:install'; }
    public function description(): string { return 'Install and configure authentication for your Flint API'; }

    public function handle(array $args): void
    {
        $strategy = $this->resolveStrategy($args);

        if (!$strategy) {
            $this->line('');
            $this->line('Which authentication strategy would you like to install?');
            $this->line('');
            $this->line('  \033[32m1)\033[0m JWT        — Signed tokens, ideal for SPAs and mobile apps');
            $this->line('  \033[32m2)\033[0m Bearer     — Simple secret token, ideal for internal services');
            $this->line('  \033[32m3)\033[0m API Key    — Key-based access, ideal for third-party integrations');
            $this->line('');
            $input = trim(readline('  Choice [1/2/3]: '));
            $strategy = match ($input) {
                '1', 'jwt'    => 'jwt',
                '2', 'bearer' => 'bearer',
                '3', 'apikey' => 'apikey',
                default       => null,
            };
        }

        if (!$strategy) {
            $this->error('Invalid strategy. Use --jwt, --bearer, or --apikey.');
            exit(1);
        }

        $this->line('');
        $this->ensurePackageRegistered();
        $this->ensureAuthConfig();

        match ($strategy) {
            'jwt'    => $this->installJwt(),
            'bearer' => $this->installBearer(),
            'apikey' => $this->installApiKey(),
        };
    }

    private function resolveStrategy(array $args): ?string
    {
        foreach ($args as $arg) {
            if ($arg === '--jwt')    return 'jwt';
            if ($arg === '--bearer') return 'bearer';
            if ($arg === '--apikey') return 'apikey';
        }
        return null;
    }

    private function installJwt(): void
    {
        $secret = bin2hex(random_bytes(32));
        $this->writeEnv('JWT_SECRET', $secret);
        $this->writeEnv('JWT_ALGORITHM', 'HS256');
        $this->generateAuthController('jwt');

        $this->info('JWT authentication installed.');
        $this->line('');
        $this->line('  \033[33mNext steps:\033[0m');
        $this->line('  1. Add to routes/web.php:');
        $this->line('');
        $this->line('     $router->post(\'/auth/login\',   [AuthController::class, \'login\']);');
        $this->line('     $router->post(\'/auth/refresh\', [AuthController::class, \'refresh\'], [\'auth.refresh\']);');
        $this->line('');
        $this->line('     $router->group([\'middleware\' => [\'auth.jwt\']], function ($router) {');
        $this->line('         $router->get(\'/profile\', [ProfileController::class, \'show\']);');
        $this->line('     });');
        $this->line('');
        $this->line('  2. In your controller, access the decoded token via:');
        $this->line('     $claims = \Vancil\FlintAuth\Auth::user();');
        $this->line('');
        $this->line("  JWT_SECRET has been written to your .env.");
    }

    private function installBearer(): void
    {
        $envPath = $this->app->basePath . '/.env';
        $secret  = '';

        if (file_exists($envPath)) {
            preg_match('/^APP_SECRET=(.+)$/m', file_get_contents($envPath), $matches);
            $secret = trim($matches[1] ?? '');
        }

        if (!$secret || $secret === 'change-me-in-production') {
            $secret = bin2hex(random_bytes(32));
            $this->writeEnv('APP_SECRET', $secret);
            $this->info('Generated new APP_SECRET and written to .env.');
        } else {
            $this->info('Using existing APP_SECRET from .env.');
        }

        $this->line('');
        $this->line('  \033[33mNext steps:\033[0m');
        $this->line('  1. Add to routes/web.php:');
        $this->line('');
        $this->line('     $router->group([\'middleware\' => [\'auth.bearer\']], function ($router) {');
        $this->line('         $router->get(\'/profile\', [ProfileController::class, \'show\']);');
        $this->line('     });');
        $this->line('');
        $this->line('  2. Clients must send: Authorization: Bearer <APP_SECRET>');
    }

    private function installApiKey(): void
    {
        $keys = [
            'key_' . bin2hex(random_bytes(16)),
            'key_' . bin2hex(random_bytes(16)),
        ];

        $this->writeEnv('API_KEYS', implode(',', $keys));
        $this->writeEnv('API_KEY_HEADER', 'X-API-Key');

        $this->info('API key authentication installed.');
        $this->line('');
        $this->line('  \033[33mNext steps:\033[0m');
        $this->line('  1. Add to routes/web.php:');
        $this->line('');
        $this->line('     $router->group([\'middleware\' => [\'auth.apikey\']], function ($router) {');
        $this->line('         $router->get(\'/webhooks\', [WebhookController::class, \'index\']);');
        $this->line('     });');
        $this->line('');
        $this->line('  2. Clients must send: X-API-Key: <key>');
        $this->line('     Or append: ?api_key=<key>');
        $this->line('');
        $this->line('  Two sample keys have been written to API_KEYS in your .env.');
        $this->line('  Add or remove keys from that list at any time.');
    }

    private function ensurePackageRegistered(): void
    {
        $configPath = $this->app->basePath . '/config/app.php';
        $contents   = file_exists($configPath) ? file_get_contents($configPath) : '';

        if (str_contains($contents, 'FlintAuth::class')) {
            return;
        }

        $contents = str_replace(
            "'packages' => [",
            "'packages' => [\n        \\Vancil\\FlintAuth\\FlintAuth::class,",
            $contents
        );

        file_put_contents($configPath, $contents);
        $this->info('Registered FlintAuth in config/app.php.');
    }

    private function ensureAuthConfig(): void
    {
        $configPath = $this->app->basePath . '/config/auth.php';

        if (file_exists($configPath)) {
            return;
        }

        $stub = <<<'PHP'
<?php

return [
    'jwt_secret'     => env('JWT_SECRET', ''),
    'jwt_algorithm'  => env('JWT_ALGORITHM', 'HS256'),
    'api_key_header' => env('API_KEY_HEADER', 'X-API-Key'),
    'api_keys'       => array_filter(explode(',', env('API_KEYS', ''))),
];
PHP;

        file_put_contents($configPath, $stub);
        $this->info('Created config/auth.php.');
    }

    private function generateAuthController(string $strategy): void
    {
        $path = $this->app->basePath . '/app/Controllers/AuthController.php';

        if (file_exists($path)) {
            $this->warn('AuthController already exists — skipping generation.');
            return;
        }

        $stub = <<<'PHP'
<?php
declare(strict_types=1);

namespace App\Controllers;

use Flint\Request;
use Flint\Response;
use Firebase\JWT\JWT;
use Vancil\FlintAuth\Auth;

class AuthController
{
    public function login(Request $request): Response
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:8',
        ]);

        // TODO: look up the user and verify the password
        // $user = User::where('email', $data['email'])->first();
        // if (!$user || !password_verify($data['password'], $user['password'])) {
        //     return Response::json(['error' => 'Invalid credentials.'], 401);
        // }

        $secret    = config('auth.jwt_secret');
        $algorithm = config('auth.jwt_algorithm');
        $now       = time();

        $accessToken = JWT::encode([
            'sub'   => 1, // $user['id']
            'email' => $data['email'],
            'type'  => 'access',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ], $secret, $algorithm);

        $refreshToken = JWT::encode([
            'sub'  => 1, // $user['id']
            'type' => 'refresh',
            'iat'  => $now,
            'exp'  => $now + (86400 * 30),
        ], $secret, $algorithm);

        return Response::json([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => 3600,
            'token_type'    => 'Bearer',
        ]);
    }

    public function refresh(Request $request): Response
    {
        $claims = Auth::user();

        $secret    = config('auth.jwt_secret');
        $algorithm = config('auth.jwt_algorithm');
        $now       = time();

        $accessToken = JWT::encode([
            'sub'  => $claims->sub,
            'type' => 'access',
            'iat'  => $now,
            'exp'  => $now + 3600,
        ], $secret, $algorithm);

        $refreshToken = JWT::encode([
            'sub'  => $claims->sub,
            'type' => 'refresh',
            'iat'  => $now,
            'exp'  => $now + (86400 * 30),
        ], $secret, $algorithm);

        return Response::json([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => 3600,
            'token_type'    => 'Bearer',
        ]);
    }
}
PHP;

        file_put_contents($path, $stub);
        $this->info('Generated app/Controllers/AuthController.php.');
    }

    private function writeEnv(string $key, string $value): void
    {
        $envPath = $this->app->basePath . '/.env';

        if (!file_exists($envPath)) {
            $this->error('.env not found. Copy .env.example to .env first.');
            exit(1);
        }

        $contents = file_get_contents($envPath);

        if (str_contains($contents, "{$key}=")) {
            $contents = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $contents);
        } else {
            $contents .= "\n{$key}={$value}";
        }

        file_put_contents($envPath, $contents);
    }
}
