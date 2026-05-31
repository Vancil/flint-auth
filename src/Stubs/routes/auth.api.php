
// --- Auth Routes (API) ---
use App\Controllers\Api\AuthApiController;
use Flint\Response;

// CSRF token endpoint — required by Vue/React SPA to get token for POST requests
$router->get('/api/csrf-token', fn() => Response::json(['token' => csrf_token()]));

$router->group(['prefix' => '/api'], function ($router) {
    $router->post('/auth/login',           [AuthApiController::class, 'login']);
    $router->post('/auth/register',        [AuthApiController::class, 'register']);
    $router->post('/auth/logout',          [AuthApiController::class, 'logout']);
    $router->get('/auth/user',             [AuthApiController::class, 'user']);
    $router->post('/auth/forgot-password', [AuthApiController::class, 'forgotPassword']);
    $router->post('/auth/reset-password',  [AuthApiController::class, 'resetPassword']);
});
