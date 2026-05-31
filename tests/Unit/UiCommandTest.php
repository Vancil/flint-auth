<?php
declare(strict_types=1);

namespace Tests\Unit;

use Flint\Application;
use PHPUnit\Framework\TestCase;
use Vancil\FlintAuth\Commands\UiCommand;

class UiCommandTest extends TestCase
{
    private string $dir;
    private UiCommand $command;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/flint-auth-test-' . uniqid('', true);
        mkdir($this->dir);
        mkdir($this->dir . '/routes');
        mkdir($this->dir . '/database/migrations', 0755, true);
        file_put_contents($this->dir . '/routes/web.php', "<?php\n\ndeclare(strict_types=1);\n");
        file_put_contents($this->dir . '/.env', "APP_NAME=Test\nAPP_SECRET=secret\n");

        $this->command = new UiCommand(new Application($this->dir));
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->dir);
    }

    // --- Preset: bootstrap ---

    public function test_bootstrap_creates_required_directories(): void
    {
        ob_start();
        $this->command->handle(['bootstrap']);
        ob_end_clean();

        $this->assertDirectoryExists($this->dir . '/resources/views');
        $this->assertDirectoryExists($this->dir . '/resources/views/layouts');
        $this->assertDirectoryExists($this->dir . '/storage/views');
        $this->assertDirectoryExists($this->dir . '/storage/logs');
    }

    public function test_bootstrap_auth_publishes_user_model(): void
    {
        ob_start();
        $this->command->handle(['bootstrap', '--auth']);
        ob_end_clean();

        $this->assertFileExists($this->dir . '/app/Models/User.php');
        $this->assertStringContainsString('class User extends Model', file_get_contents($this->dir . '/app/Models/User.php'));
    }

    public function test_bootstrap_auth_publishes_all_three_models(): void
    {
        ob_start();
        $this->command->handle(['bootstrap', '--auth']);
        ob_end_clean();

        $this->assertFileExists($this->dir . '/app/Models/User.php');
        $this->assertFileExists($this->dir . '/app/Models/PasswordResetToken.php');
        $this->assertFileExists($this->dir . '/app/Models/EmailVerification.php');
    }

    public function test_bootstrap_auth_publishes_three_migrations(): void
    {
        ob_start();
        $this->command->handle(['bootstrap', '--auth']);
        ob_end_clean();

        $migrations = glob($this->dir . '/database/migrations/*.php');
        $this->assertCount(3, $migrations);
    }

    public function test_bootstrap_auth_migration_names_are_correct(): void
    {
        ob_start();
        $this->command->handle(['bootstrap', '--auth']);
        ob_end_clean();

        $migrations = glob($this->dir . '/database/migrations/*.php');
        $names      = array_map(fn($f) => basename($f), $migrations);

        $this->assertTrue(count(preg_grep('/create_users_table/', $names)) === 1);
        $this->assertTrue(count(preg_grep('/create_password_reset_tokens_table/', $names)) === 1);
        $this->assertTrue(count(preg_grep('/create_email_verifications_table/', $names)) === 1);
    }

    public function test_bootstrap_auth_publishes_auth_controllers(): void
    {
        ob_start();
        $this->command->handle(['bootstrap', '--auth']);
        ob_end_clean();

        $this->assertFileExists($this->dir . '/app/Controllers/Auth/LoginController.php');
        $this->assertFileExists($this->dir . '/app/Controllers/Auth/RegisterController.php');
        $this->assertFileExists($this->dir . '/app/Controllers/Auth/ForgotPasswordController.php');
        $this->assertFileExists($this->dir . '/app/Controllers/Auth/ResetPasswordController.php');
        $this->assertFileExists($this->dir . '/app/Controllers/Auth/EmailVerificationController.php');
    }

    public function test_bootstrap_auth_publishes_ember_views(): void
    {
        ob_start();
        $this->command->handle(['bootstrap', '--auth']);
        ob_end_clean();

        $this->assertFileExists($this->dir . '/resources/views/layouts/app.spark.php');
        $this->assertFileExists($this->dir . '/resources/views/auth/login.spark.php');
        $this->assertFileExists($this->dir . '/resources/views/auth/register.spark.php');
        $this->assertFileExists($this->dir . '/resources/views/auth/forgot-password.spark.php');
        $this->assertFileExists($this->dir . '/resources/views/auth/reset-password.spark.php');
        $this->assertFileExists($this->dir . '/resources/views/auth/verify-email.spark.php');
        $this->assertFileExists($this->dir . '/resources/views/auth/dashboard.spark.php');
        $this->assertFileExists($this->dir . '/resources/views/emails/reset-password.spark.php');
        $this->assertFileExists($this->dir . '/resources/views/emails/verify-email.spark.php');
    }

    public function test_bootstrap_auth_appends_routes_to_web_php(): void
    {
        ob_start();
        $this->command->handle(['bootstrap', '--auth']);
        ob_end_clean();

        $routes = file_get_contents($this->dir . '/routes/web.php');
        $this->assertStringContainsString('// --- Auth Routes ---', $routes);
        $this->assertStringContainsString('LoginController', $routes);
        $this->assertStringContainsString('/forgot-password', $routes);
    }

    public function test_auth_routes_are_not_appended_twice(): void
    {
        ob_start();
        $this->command->handle(['bootstrap', '--auth']);
        $this->command->handle(['bootstrap', '--auth']);
        ob_end_clean();

        $routes = file_get_contents($this->dir . '/routes/web.php');
        $this->assertSame(1, substr_count($routes, '// --- Auth Routes ---'));
    }

    public function test_existing_user_model_is_not_overwritten(): void
    {
        mkdir($this->dir . '/app/Models', 0755, true);
        file_put_contents($this->dir . '/app/Models/User.php', '<?php // existing');

        ob_start();
        $this->command->handle(['bootstrap', '--auth']);
        ob_end_clean();

        $this->assertSame('<?php // existing', file_get_contents($this->dir . '/app/Models/User.php'));
    }

    public function test_existing_migrations_are_not_duplicated(): void
    {
        // Pre-create one migration as if it was already published
        file_put_contents(
            $this->dir . '/database/migrations/2024_01_01_000000_create_users_table.php',
            '<?php // existing'
        );

        ob_start();
        $this->command->handle(['bootstrap', '--auth']);
        ob_end_clean();

        $userMigrations = glob($this->dir . '/database/migrations/*create_users_table*');
        $this->assertCount(1, $userMigrations);
        $this->assertSame('<?php // existing', file_get_contents($userMigrations[0]));
    }

    public function test_bootstrap_auth_writes_auth_config(): void
    {
        ob_start();
        $this->command->handle(['bootstrap', '--auth']);
        ob_end_clean();

        $this->assertFileExists($this->dir . '/config/auth.php');
        $this->assertStringContainsString('session', file_get_contents($this->dir . '/config/auth.php'));
    }

    public function test_bootstrap_auth_writes_env_defaults(): void
    {
        ob_start();
        $this->command->handle(['bootstrap', '--auth']);
        ob_end_clean();

        $env = file_get_contents($this->dir . '/.env');
        $this->assertStringContainsString('SESSION_COOKIE=', $env);
        $this->assertStringContainsString('MAIL_DRIVER=', $env);
    }

    public function test_env_defaults_not_duplicated_if_keys_already_present(): void
    {
        file_put_contents($this->dir . '/.env', "APP_NAME=Test\nSESSION_COOKIE=my_session\nMAIL_DRIVER=smtp\n");

        ob_start();
        $this->command->handle(['bootstrap', '--auth']);
        ob_end_clean();

        $env = file_get_contents($this->dir . '/.env');
        $this->assertSame(1, substr_count($env, 'SESSION_COOKIE='));
        $this->assertSame(1, substr_count($env, 'MAIL_DRIVER='));
    }

    // --- Preset: vue ---

    public function test_vue_creates_vite_config(): void
    {
        ob_start();
        $this->command->handle(['vue']);
        ob_end_clean();

        $this->assertFileExists($this->dir . '/vite.config.js');
        $this->assertFileExists($this->dir . '/package.json');
    }

    public function test_vue_auth_publishes_vue_components(): void
    {
        ob_start();
        $this->command->handle(['vue', '--auth']);
        ob_end_clean();

        $this->assertFileExists($this->dir . '/resources/js/main.js');
        $this->assertFileExists($this->dir . '/resources/js/views/LoginView.vue');
        $this->assertFileExists($this->dir . '/resources/js/views/RegisterView.vue');
        $this->assertFileExists($this->dir . '/resources/js/views/DashboardView.vue');
    }

    public function test_vue_auth_publishes_api_controller(): void
    {
        ob_start();
        $this->command->handle(['vue', '--auth']);
        ob_end_clean();

        $this->assertFileExists($this->dir . '/app/Controllers/Api/AuthApiController.php');
    }

    public function test_vue_package_json_includes_vue_dependency(): void
    {
        ob_start();
        $this->command->handle(['vue']);
        ob_end_clean();

        $pkg = json_decode(file_get_contents($this->dir . '/package.json'), true);
        $this->assertArrayHasKey('vue', $pkg['dependencies']);
    }

    // --- Preset: react ---

    public function test_react_creates_vite_config(): void
    {
        ob_start();
        $this->command->handle(['react']);
        ob_end_clean();

        $this->assertFileExists($this->dir . '/vite.config.js');
        $this->assertFileExists($this->dir . '/package.json');
    }

    public function test_react_auth_publishes_react_pages(): void
    {
        ob_start();
        $this->command->handle(['react', '--auth']);
        ob_end_clean();

        $this->assertFileExists($this->dir . '/resources/js/main.jsx');
        $this->assertFileExists($this->dir . '/resources/js/pages/LoginPage.jsx');
        $this->assertFileExists($this->dir . '/resources/js/pages/RegisterPage.jsx');
        $this->assertFileExists($this->dir . '/resources/js/pages/DashboardPage.jsx');
    }

    public function test_react_package_json_includes_react_dependency(): void
    {
        ob_start();
        $this->command->handle(['react']);
        ob_end_clean();

        $pkg = json_decode(file_get_contents($this->dir . '/package.json'), true);
        $this->assertArrayHasKey('react', $pkg['dependencies']);
    }

    // --- Helpers ---

    private function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            is_dir($full) ? $this->removeDir($full) : unlink($full);
        }
        rmdir($path);
    }
}
