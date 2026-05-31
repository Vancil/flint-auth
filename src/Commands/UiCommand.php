<?php
declare(strict_types=1);

namespace Vancil\FlintAuth\Commands;

use Flint\Console\Command;

class UiCommand extends Command
{
    private string $stubsPath;
    private string $basePath;

    public function signature(): string
    {
        return 'ui';
    }

    public function description(): string
    {
        return 'Scaffold a UI preset: php flint ui bootstrap|vue|react [--auth]';
    }

    public function handle(array $args): void
    {
        $this->basePath  = $this->app->basePath;
        $this->stubsPath = dirname(__DIR__) . '/Stubs';

        $preset   = null;
        $withAuth = false;

        foreach ($args as $arg) {
            if ($arg === '--auth') {
                $withAuth = true;
            } elseif (in_array($arg, ['bootstrap', 'vue', 'react'], true)) {
                $preset = $arg;
            }
        }

        if ($preset === null) {
            $this->error('Usage: php flint ui bootstrap|vue|react [--auth]');
            exit(1);
        }

        $this->ensureDirectories($preset);
        $this->publishPreset($preset);

        if ($withAuth) {
            $this->publishAuthMigrations();
            $this->publishUserModel();
            $this->publishAuthControllers($preset);
            $this->publishAuthViews($preset);
            $this->appendAuthRoutes($preset);
            $this->ensureAuthConfig();
            $this->writeEnvDefaults();
            $this->printNextSteps($preset);
        } else {
            $this->info("Preset [{$preset}] installed.");
        }
    }

    private function ensureDirectories(string $preset): void
    {
        $dirs = [
            'resources/views',
            'resources/views/layouts',
            'resources/views/auth',
            'resources/views/emails',
            'resources/js',
            'storage/views',
            'storage/logs',
            'app/Controllers/Auth',
            'app/Controllers/Api',
            'routes',
        ];

        foreach ($dirs as $dir) {
            $path = $this->basePath . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        // .gitignore for compiled view cache
        $gitignore = $this->basePath . '/storage/views/.gitignore';
        if (!file_exists($gitignore)) {
            file_put_contents($gitignore, "*\n!.gitignore\n");
        }
    }

    private function publishPreset(string $preset): void
    {
        if ($preset === 'bootstrap') {
            // Bootstrap uses server-rendered Ember views; no JS build step
            $this->copyStub(
                "views/bootstrap/resources/css/app.css",
                "resources/css/app.css"
            );
            return;
        }

        // Vue / React: copy Vite boilerplate
        $files = [
            "package.json",
            "vite.config.js",
        ];

        foreach ($files as $file) {
            $this->copyStub("{$preset}/{$file}", $file);
        }

        $this->info("Vite + " . ucfirst($preset) . " scaffold installed.");
        $this->line("  Run: npm install && npm run dev");
    }

    private function publishAuthMigrations(): void
    {
        $migrations = [
            'create_users_table',
            'create_password_reset_tokens_table',
            'create_email_verifications_table',
        ];

        $destDir = $this->basePath . '/database/migrations';
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        foreach ($migrations as $migration) {
            // Skip if a migration with this name already exists
            $existing = glob($destDir . "/*_{$migration}.php");
            if (!empty($existing)) {
                $this->warn("Migration already exists: {$migration} — skipping.");
                continue;
            }

            $timestamp = date('Y_m_d_His');
            $dest      = "{$destDir}/{$timestamp}_{$migration}.php";
            $this->copyStub("migrations/{$migration}.php", "database/migrations/" . basename($dest));
            $this->info("Migration created: database/migrations/" . basename($dest));
        }
    }

    private function publishUserModel(): void
    {
        $models = [
            'models/User.php'                 => 'app/Models/User.php',
            'models/PasswordResetToken.php'   => 'app/Models/PasswordResetToken.php',
            'models/EmailVerification.php'    => 'app/Models/EmailVerification.php',
        ];

        foreach ($models as $stub => $dest) {
            if (file_exists($this->basePath . '/' . $dest)) {
                $this->warn(basename($dest) . " already exists — skipping.");
                continue;
            }
            $this->copyStub($stub, $dest);
            $this->info("Model created: {$dest}");
        }
    }

    private function publishAuthControllers(string $preset): void
    {
        if ($preset === 'bootstrap') {
            $controllers = [
                'Auth/LoginController.php',
                'Auth/RegisterController.php',
                'Auth/ForgotPasswordController.php',
                'Auth/ResetPasswordController.php',
                'Auth/EmailVerificationController.php',
            ];

            foreach ($controllers as $controller) {
                $dest = "app/Controllers/{$controller}";
                if (file_exists($this->basePath . '/' . $dest)) {
                    $this->warn("Controller already exists: {$dest} — skipping.");
                    continue;
                }
                $this->copyStub("controllers/{$controller}", $dest);
                $this->info("Controller created: {$dest}");
            }
        } else {
            $dest = 'app/Controllers/Api/AuthApiController.php';
            if (!file_exists($this->basePath . '/' . $dest)) {
                $this->copyStub('controllers/Api/AuthApiController.php', $dest);
                $this->info("Controller created: {$dest}");
            }
        }
    }

    private function publishAuthViews(string $preset): void
    {
        if ($preset === 'bootstrap') {
            $views = [
                'views/bootstrap/layouts/app.ember'           => 'resources/views/layouts/app.ember',
                'views/bootstrap/auth/login.ember'            => 'resources/views/auth/login.ember',
                'views/bootstrap/auth/register.ember'         => 'resources/views/auth/register.ember',
                'views/bootstrap/auth/forgot-password.ember'  => 'resources/views/auth/forgot-password.ember',
                'views/bootstrap/auth/reset-password.ember'   => 'resources/views/auth/reset-password.ember',
                'views/bootstrap/auth/verify-email.ember'     => 'resources/views/auth/verify-email.ember',
                'views/bootstrap/auth/dashboard.ember'        => 'resources/views/auth/dashboard.ember',
                'views/bootstrap/emails/reset-password.ember' => 'resources/views/emails/reset-password.ember',
                'views/bootstrap/emails/verify-email.ember'   => 'resources/views/emails/verify-email.ember',
            ];

            foreach ($views as $stub => $dest) {
                if (file_exists($this->basePath . '/' . $dest)) {
                    $this->warn("View already exists: {$dest} — skipping.");
                    continue;
                }
                $this->copyStub($stub, $dest);
                $this->info("View created: {$dest}");
            }
        } elseif ($preset === 'vue') {
            $jsDir = $this->basePath . '/resources/js';
            foreach (['views', 'router', 'components'] as $sub) {
                if (!is_dir("{$jsDir}/{$sub}")) {
                    mkdir("{$jsDir}/{$sub}", 0755, true);
                }
            }

            $files = [
                'vue/src/main.js'                  => 'resources/js/main.js',
                'vue/src/router/index.js'          => 'resources/js/router/index.js',
                'vue/src/views/LoginView.vue'      => 'resources/js/views/LoginView.vue',
                'vue/src/views/RegisterView.vue'   => 'resources/js/views/RegisterView.vue',
                'vue/src/views/ForgotPasswordView.vue' => 'resources/js/views/ForgotPasswordView.vue',
                'vue/src/views/ResetPasswordView.vue'  => 'resources/js/views/ResetPasswordView.vue',
                'vue/src/views/DashboardView.vue'  => 'resources/js/views/DashboardView.vue',
            ];

            foreach ($files as $stub => $dest) {
                $this->copyStub($stub, $dest);
                $this->info("Created: {$dest}");
            }
        } elseif ($preset === 'react') {
            $jsDir = $this->basePath . '/resources/js';
            foreach (['pages', 'router'] as $sub) {
                if (!is_dir("{$jsDir}/{$sub}")) {
                    mkdir("{$jsDir}/{$sub}", 0755, true);
                }
            }

            $files = [
                'react/src/main.jsx'                   => 'resources/js/main.jsx',
                'react/src/router/AppRouter.jsx'       => 'resources/js/router/AppRouter.jsx',
                'react/src/pages/LoginPage.jsx'        => 'resources/js/pages/LoginPage.jsx',
                'react/src/pages/RegisterPage.jsx'     => 'resources/js/pages/RegisterPage.jsx',
                'react/src/pages/ForgotPasswordPage.jsx' => 'resources/js/pages/ForgotPasswordPage.jsx',
                'react/src/pages/ResetPasswordPage.jsx'  => 'resources/js/pages/ResetPasswordPage.jsx',
                'react/src/pages/DashboardPage.jsx'    => 'resources/js/pages/DashboardPage.jsx',
            ];

            foreach ($files as $stub => $dest) {
                $this->copyStub($stub, $dest);
                $this->info("Created: {$dest}");
            }
        }
    }

    private function appendAuthRoutes(string $preset): void
    {
        $routesFile = $this->basePath . '/routes/web.php';

        if (!file_exists($routesFile)) {
            file_put_contents($routesFile, "<?php\n\ndeclare(strict_types=1);\n\n// Routes are loaded by the Application with \$router in scope.\n");
        }

        $existing = file_get_contents($routesFile);

        if (str_contains($existing, '// --- Auth Routes ---')) {
            $this->warn('Auth routes already appended to routes/web.php — skipping.');
            return;
        }

        $stub     = $preset === 'bootstrap' ? 'routes/auth.bootstrap.php' : 'routes/auth.api.php';
        $routeSnippet = "\n" . file_get_contents($this->stubsPath . '/' . $stub);
        file_put_contents($routesFile, $routeSnippet, FILE_APPEND);
        $this->info('Auth routes appended to routes/web.php');
    }

    private function ensureAuthConfig(): void
    {
        $configDir  = $this->basePath . '/config';
        $configFile = $configDir . '/auth.php';

        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        if (file_exists($configFile)) {
            return;
        }

        $content = <<<'PHP'
<?php

return [
    'guard' => 'session',
    'model' => \App\Models\User::class,
];
PHP;

        file_put_contents($configFile, $content . "\n");
        $this->info('Config created: config/auth.php');
    }

    private function writeEnvDefaults(): void
    {
        $envFile = $this->basePath . '/.env';

        if (!file_exists($envFile)) {
            return;
        }

        $env = file_get_contents($envFile);

        $defaults = [
            'SESSION_COOKIE'   => 'flint_session',
            'SESSION_LIFETIME' => '7200',
            'MAIL_DRIVER'      => 'log',
            'MAIL_FROM_ADDRESS' => 'hello@example.com',
            'MAIL_FROM_NAME'   => 'Flint',
        ];

        $appended = false;
        foreach ($defaults as $key => $value) {
            if (!str_contains($env, $key . '=')) {
                $env     .= "{$key}={$value}\n";
                $appended = true;
            }
        }

        if ($appended) {
            file_put_contents($envFile, $env);
            $this->info('.env defaults written (SESSION_*, MAIL_*)');
        }
    }

    private function printNextSteps(string $preset): void
    {
        $this->line('');
        $this->info('Auth scaffolding complete!');
        $this->line('');
        $this->line('Next steps:');
        $this->line('  1. Run migrations:     php flint migrate');
        $this->line('  2. Start dev server:   php -S localhost:8000 -t public');

        if ($preset !== 'bootstrap') {
            $this->line('  3. Install JS deps:    npm install && npm run dev');
        }

        $this->line('  Visit: http://localhost:8000/register');
        $this->line('');
        $this->warn('Note: Configure MAIL_DRIVER=smtp in .env to send real emails (forgot password / verification).');
    }

    private function copyStub(string $stubRelativePath, string $destRelativePath): void
    {
        $src  = $this->stubsPath . '/' . $stubRelativePath;
        $dest = $this->basePath . '/' . $destRelativePath;

        if (!file_exists($src)) {
            $this->warn("Stub not found: {$src}");
            return;
        }

        $dir = dirname($dest);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        copy($src, $dest);
    }
}
