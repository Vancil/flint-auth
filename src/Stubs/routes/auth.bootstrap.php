
// --- Auth Routes ---
use App\Controllers\Auth\EmailVerificationController;
use App\Controllers\Auth\ForgotPasswordController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegisterController;
use App\Controllers\Auth\ResetPasswordController;
use Flint\Response;

$router->get('/login',    [LoginController::class, 'showForm']);
$router->post('/login',   [LoginController::class, 'login']);
$router->post('/logout',  [LoginController::class, 'logout']);

$router->get('/register',  [RegisterController::class, 'showForm']);
$router->post('/register', [RegisterController::class, 'register']);

$router->get('/forgot-password',  [ForgotPasswordController::class, 'showForm']);
$router->post('/forgot-password', [ForgotPasswordController::class, 'sendLink']);

$router->get('/reset-password/{token}', [ResetPasswordController::class, 'showForm']);
$router->post('/reset-password',        [ResetPasswordController::class, 'reset']);

$router->get('/email/verify',         [EmailVerificationController::class, 'show']);
$router->get('/email/verify/{token}', [EmailVerificationController::class, 'verify']);
$router->post('/email/verify/resend', [EmailVerificationController::class, 'resend']);

$router->group(['middleware' => ['auth']], function ($router) {
    $router->get('/dashboard', fn() => Response::view('auth.dashboard'));
});
