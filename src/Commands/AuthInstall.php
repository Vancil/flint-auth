<?php
declare(strict_types=1);

namespace Vancil\FlintAuth\Commands;

use Flint\Console\Command;
use Flint\Application;

class AuthInstall extends Command
{
    public function signature(): string { return 'auth:install'; }
    public function description(): string { return 'Install and configure JWT authentication for your Flint API'; }

    public function handle(array $args): void
    {
        $this->line('');
        $this->ensurePackageRegistered();
        $this->ensureAuthConfig();
        $this->publishMigrations();
        $this->generateUserModel();
        $this->installJwt();
    }

    private function installJwt(): void
    {
        $secret = bin2hex(random_bytes(32));
        $this->writeEnv('JWT_SECRET', $secret);
        $this->writeEnv('JWT_ALGORITHM', 'HS256');
        $this->generateAuthController();

        $this->info('JWT authentication installed.');
        $this->line('');
        $this->line('  \033[33mNext steps:\033[0m');
        $this->line('  1. Run migrations:');
        $this->line('     php flint migrate');
        $this->line('');
        $this->line('  2. Add to routes/web.php:');
        $this->line('');
        $this->line('     $router->post(\'/auth/register\', [AuthController::class, \'register\']);');
        $this->line('     $router->post(\'/auth/login\',    [AuthController::class, \'login\']);');
        $this->line('     $router->post(\'/auth/refresh\',  [AuthController::class, \'refresh\'], [\'auth.refresh\']);');
        $this->line('     $router->post(\'/auth/logout\',   [AuthController::class, \'logout\'],  [\'auth.refresh\']);');
        $this->line('');
        $this->line('     $router->group([\'middleware\' => [\'auth.jwt\']], function ($router) {');
        $this->line('         $router->get(\'/profile\', [ProfileController::class, \'show\']);');
        $this->line('     });');
        $this->line('');
        $this->line('  3. Access the authenticated user in your controllers:');
        $this->line('     $claims = \Vancil\FlintAuth\Auth::user();');
        $this->line('');
        $this->line('  JWT_SECRET has been written to your .env.');
    }

    private function publishMigrations(): void
    {
        $migPath = $this->app->basePath . '/database/migrations';

        if (!is_dir($migPath)) {
            mkdir($migPath, 0755, true);
        }

        $timestamp = date('Y_m_d_His');

        if (!glob("{$migPath}/*_create_users_table.php")) {
            $file = "{$migPath}/{$timestamp}_001_create_users_table.php";
            file_put_contents($file, $this->usersMigrationStub());
            $this->info('Published migration: create_users_table');
        }

        if (!glob("{$migPath}/*_create_refresh_tokens_table.php")) {
            $file = "{$migPath}/{$timestamp}_002_create_refresh_tokens_table.php";
            file_put_contents($file, $this->refreshTokensMigrationStub());
            $this->info('Published migration: create_refresh_tokens_table');
        }
    }

    private function usersMigrationStub(): string
    {
        return <<<'PHP'
<?php

use Flint\Schema;
use Flint\Blueprint;

return new class {
    public function up(PDO $pdo): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(PDO $pdo): void
    {
        Schema::dropIfExists('users');
    }
};
PHP;
    }

    private function refreshTokensMigrationStub(): string
    {
        return <<<'PHP'
<?php

use Flint\Schema;
use Flint\Blueprint;

return new class {
    public function up(PDO $pdo): void
    {
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('jti')->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(PDO $pdo): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
PHP;
    }

    private function generateUserModel(): void
    {
        $dir  = $this->app->basePath . '/app/Models';
        $path = "{$dir}/User.php";

        if (file_exists($path)) {
            $this->warn('User model already exists — skipping generation.');
            return;
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $stub = <<<'PHP'
<?php
declare(strict_types=1);

namespace App\Models;

use Flint\Model;

class User extends Model
{
    protected string $table    = 'users';
    protected array  $fillable = ['name', 'email', 'password', 'is_active'];
    protected array  $hidden   = ['password'];
    protected array  $casts    = ['is_active' => 'bool'];
}
PHP;

        file_put_contents($path, $stub);
        $this->info('Generated app/Models/User.php.');
    }

    private function generateAuthController(): void
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

use App\Models\User;
use Firebase\JWT\JWT;
use Flint\QueryBuilder;
use Flint\Request;
use Flint\Response;
use Vancil\FlintAuth\Auth;

class AuthController
{
    public function register(Request $request): Response
    {
        $data = $request->validate([
            'name'     => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8',
        ]);

        if (User::where('email', $data['email'])->first()) {
            return Response::json(['error' => 'Email already taken.'], 422);
        }

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);

        return Response::json($this->issueTokenPair($user->id), 201);
    }

    public function login(Request $request): Response
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $data['email'])->firstModel();

        if (!$user || !password_verify($data['password'], $user->password)) {
            return Response::json(['error' => 'Invalid credentials.'], 401);
        }

        return Response::json($this->issueTokenPair($user->id));
    }

    public function refresh(Request $request): Response
    {
        $claims = Auth::user();

        // Rotate: delete the old refresh token before issuing a new pair
        (new QueryBuilder('refresh_tokens'))
            ->where('jti', $claims->jti)
            ->delete();

        return Response::json($this->issueTokenPair($claims->sub));
    }

    public function logout(Request $request): Response
    {
        $claims = Auth::user();

        (new QueryBuilder('refresh_tokens'))
            ->where('jti', $claims->jti)
            ->delete();

        return Response::noContent();
    }

    private function issueTokenPair(int $userId): array
    {
        $secret    = config('auth.jwt_secret');
        $algorithm = config('auth.jwt_algorithm');
        $now       = time();
        $jti       = bin2hex(random_bytes(16));

        $accessToken = JWT::encode([
            'sub'  => $userId,
            'type' => 'access',
            'iat'  => $now,
            'exp'  => $now + 900,
        ], $secret, $algorithm);

        $refreshToken = JWT::encode([
            'sub'  => $userId,
            'type' => 'refresh',
            'jti'  => $jti,
            'iat'  => $now,
            'exp'  => $now + (86400 * 30),
        ], $secret, $algorithm);

        (new QueryBuilder('refresh_tokens'))->insert([
            'user_id'    => $userId,
            'jti'        => $jti,
            'expires_at' => date('Y-m-d H:i:s', $now + (86400 * 30)),
            'created_at' => date('Y-m-d H:i:s', $now),
            'updated_at' => date('Y-m-d H:i:s', $now),
        ]);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => 900,
            'token_type'    => 'Bearer',
        ];
    }
}
PHP;

        file_put_contents($path, $stub);
        $this->info('Generated app/Controllers/AuthController.php.');
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
    'jwt_secret'    => env('JWT_SECRET', ''),
    'jwt_algorithm' => env('JWT_ALGORITHM', 'HS256'),
];
PHP;

        file_put_contents($configPath, $stub);
        $this->info('Created config/auth.php.');
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
