<?php
use App\Http\Controllers\Api\ApiGatewayController;
use Illuminate\Support\Facades\Route;

$recoveryBypass = [
    'get_consents',
    'post_consents',
    'post_auth_recovery_confirm',
    'get_auth_recovery_status',
    'post_auth_recovery_code_regenerate',
    'post_auth_logout',
    'post_auth_logout_all',
    'delete_account',
];

foreach (config('api_contract') as $endpoint) {
    $route = Route::match([$endpoint['method']], ltrim($endpoint['path'], '/'), [ApiGatewayController::class, 'handle'])
        ->name('api.'.$endpoint['operation'])
        ->defaults('_operation', $endpoint['operation'])
        ->defaults('_handler', $endpoint['handler'])
        ->defaults('_resource', $endpoint['resource'])
        ->defaults('_module', $endpoint['module']);

    $middleware = ['throttle:api'];
    if (! $endpoint['public']) {
        $middleware[] = 'auth:sanctum';
        if (! in_array($endpoint['operation'], $recoveryBypass, true)) $middleware[] = 'recovery.confirmed';
        if (! $endpoint['legal_bypass']) $middleware[] = 'legal.accepted';
    }
    if (in_array($endpoint['method'], ['POST','PUT','PATCH','DELETE'], true)) $middleware[] = 'idempotency';
    if ($endpoint['handler'] === 'auth') $middleware[] = 'throttle:auth';
    if (in_array($endpoint['handler'], ['billing','media'], true)) $middleware[] = 'throttle:sensitive';
    $route->middleware(array_unique($middleware));
}
