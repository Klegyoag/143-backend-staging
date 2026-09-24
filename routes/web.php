<?php
use Illuminate\Support\Facades\Route;
Route::get('/', fn () => response()->json(['service'=>'143 API','version'=>'v1']));

if (app()->environment('production')) {
    Route::get('/_diagnostics/auth', function (Illuminate\Http\Request $request) {
        $token = $request->bearerToken();
        $record = is_string($token) && $token !== ''
            ? Laravel\Sanctum\PersonalAccessToken::findToken($token)
            : null;

        return response()->json([
            'authorization_header_present' => $request->hasHeader('Authorization'),
            'bearer_token_present' => is_string($token) && $token !== '',
            'token_record_found' => $record !== null,
            'tokenable_found' => $record?->tokenable !== null,
            'supports_api_tokens' => $record?->tokenable !== null
                && in_array(Laravel\Sanctum\HasApiTokens::class, class_uses_recursive(get_class($record->tokenable))),
            'token_expired' => $record?->expires_at?->isPast() ?? false,
        ]);
    });
}
