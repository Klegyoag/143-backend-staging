<?php
namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRecoveryConfirmed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->recovery_confirmed_at) {
            return ApiResponse::problem(
                'Recovery code confirmation required',
                'Save and confirm the one-time recovery code before using protected 143 features.',
                428,
                'recovery_confirmation_required'
            );
        }
        return $next($request);
    }
}
