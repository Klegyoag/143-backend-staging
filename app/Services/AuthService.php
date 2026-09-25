<?php
namespace App\Services;

use App\Models\{RecoveryCode,RefreshToken,User};
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function handle(string $operation, Request $request)
    {
        return match ($operation) {
            'post_auth_register' => $this->register($request),
            'post_auth_login' => $this->login($request),
            'post_auth_recovery_confirm' => $this->confirmRecovery($request),
            'get_auth_recovery_status' => $this->recoveryStatus($request),
            'post_auth_password_reset_recovery_code' => $this->resetWithRecovery($request),
            'post_auth_recovery_code_regenerate' => $this->regenerateRecoveryCode($request),
            'post_auth_password_change' => $this->changePassword($request),
            'post_auth_token_refresh' => $this->refresh($request),
            'post_auth_logout' => $this->logout($request),
            'post_auth_logout_all' => $this->logoutAll($request),
            'delete_account' => $this->deleteAccount($request),
            default => ApiResponse::problem('Unsupported auth operation', $operation, 501, 'not_implemented'),
        };
    }

    private function register(Request $request)
    {
        $data = $request->validate([
            'username' => ['required','string','min:3','max:30','regex:/^[A-Za-z0-9._-]+$/'],
            'password' => ['required','string','min:10','max:200','confirmed'],
            'display_name' => ['nullable','string','max:100'],
            'device_name' => ['nullable','string','max:100'],
        ]);

        $username = Str::lower(trim($data['username']));
        if (User::where('username', $username)->exists()) {
            throw ValidationException::withMessages(['username' => ['That username is already taken.']]);
        }

        return DB::transaction(function () use ($data, $username) {
            $user = User::create([
                'username' => $username,
                'password' => $data['password'],
                'display_name' => $data['display_name'] ?? $username,
                'recovery_confirmed_at' => null,
                'account_state' => 'recovery_setup_pending',
            ]);

            $recoveryCode = $this->replaceRecoveryCode($user);
            $tokens = $this->issue($user, $data['device_name'] ?? 'mobile');

            return ApiResponse::created(array_merge($tokens, [
                'recovery_code' => $recoveryCode,
                'recovery_code_shown_once' => true,
                'next_step' => 'confirm_recovery_code',
            ]));
        });
    }

    private function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required','string','max:30'],
            'password' => ['required','string','max:200'],
            'device_name' => ['nullable','string','max:100'],
        ]);

        $user = User::where('username', Str::lower(trim($data['username'])))->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['username' => ['Invalid username or password.']]);
        }
        if ($user->account_state === 'disabled') {
            return ApiResponse::problem('Account unavailable', 'This account is currently unavailable.', 403, 'account_unavailable');
        }

        return ApiResponse::ok($this->issue($user, $data['device_name'] ?? 'mobile'));
    }

    private function confirmRecovery(Request $request)
    {
        $data = $request->validate(['recovery_code' => ['required','string','max:64']]);
        $user = $request->user();
        $code = RecoveryCode::where('user_id', $user->id)->whereNull('used_at')->latest()->first();

        if (! $code || ! Hash::check(Str::upper(trim($data['recovery_code'])), $code->code_hash)) {
            return ApiResponse::problem('Invalid recovery code', 'The recovery code is invalid.', 422, 'invalid_recovery_code');
        }

        $user->update(['recovery_confirmed_at' => now(), 'account_state' => 'active']);
        return ApiResponse::ok(['confirmed' => true, 'next_step' => 'accept_legal_documents']);
    }

    private function recoveryStatus(Request $request)
    {
        return ApiResponse::ok([
            'confirmed' => (bool) $request->user()->recovery_confirmed_at,
            'account_state' => $request->user()->account_state,
        ]);
    }

    private function resetWithRecovery(Request $request)
    {
        $data = $request->validate([
            'username' => ['required','string','max:30'],
            'recovery_code' => ['required','string','max:64'],
            'password' => ['required','string','min:10','max:200','confirmed'],
        ]);

        $user = User::where('username', Str::lower(trim($data['username'])))->first();
        $code = $user ? RecoveryCode::where('user_id', $user->id)->whereNull('used_at')->latest()->first() : null;

        if (! $user || ! $code || ! Hash::check(Str::upper(trim($data['recovery_code'])), $code->code_hash)) {
            return ApiResponse::problem('Recovery failed', 'The username or recovery code is invalid.', 422, 'invalid_recovery_credentials');
        }

        return DB::transaction(function () use ($user, $data, $code) {
            $code->update(['used_at' => now()]);
            $user->update([
                'password' => $data['password'],
                'recovery_confirmed_at' => null,
                'account_state' => 'recovery_setup_pending',
            ]);
            $user->tokens()->delete();
            RefreshToken::where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $newCode = $this->replaceRecoveryCode($user);

            return ApiResponse::ok([
                'reset' => true,
                'recovery_code' => $newCode,
                'recovery_code_shown_once' => true,
                'next_step' => 'login_then_confirm_recovery_code',
            ]);
        });
    }

    private function regenerateRecoveryCode(Request $request)
    {
        $data = $request->validate(['current_password' => ['required','string','max:200']]);
        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            return ApiResponse::problem('Wrong password', 'Current password is incorrect.', 422, 'wrong_password');
        }

        $plain = $this->replaceRecoveryCode($user);
        $user->update(['recovery_confirmed_at' => null, 'account_state' => 'recovery_setup_pending']);
        return ApiResponse::ok([
            'recovery_code' => $plain,
            'recovery_code_shown_once' => true,
            'next_step' => 'confirm_recovery_code',
        ]);
    }

    private function replaceRecoveryCode(User $user): string
    {
        RecoveryCode::where('user_id', $user->id)->whereNull('used_at')->update(['used_at' => now()]);
        $plain = $this->makeRecoveryCode();
        RecoveryCode::create(['user_id' => $user->id, 'code_hash' => Hash::make($plain)]);
        return $plain;
    }

    private function makeRecoveryCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $groups = [];
        for ($g = 0; $g < 5; $g++) {
            $part = '';
            for ($i = 0; $i < 4; $i++) $part .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            $groups[] = $part;
        }
        return '143-'.implode('-', $groups);
    }

    private function issue(User $user, string $device): array
    {
        $access = $user->createToken($device, ['mobile'], now()->addMinutes(config('143.access_token_minutes')))->plainTextToken;
        $plain = Str::random(80);
        RefreshToken::create([
            'user_id' => $user->id,
            'device_name' => $device,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDays(config('143.refresh_token_days')),
        ]);

        return [
            'user' => $user->only(['id','username','display_name','bio','photo_path','timezone','locale','account_state','recovery_confirmed_at']),
            'access_token' => $access,
            'token_type' => 'Bearer',
            'expires_in' => config('143.access_token_minutes') * 60,
            'refresh_token' => $plain,
            'setup' => [
                'recovery_confirmed' => (bool) $user->recovery_confirmed_at,
                'legal_acceptance_required' => true,
            ],
        ];
    }

    public function issueForLinkedDevice(User $user, string $device): array
    {
        return $this->issue($user, $device);
    }

    private function refresh(Request $request)
    {
        $data = $request->validate(['refresh_token' => ['required','string']]);
        $row = RefreshToken::where('token_hash', hash('sha256', $data['refresh_token']))
            ->whereNull('revoked_at')->where('expires_at', '>', now())->first();
        if (! $row) return ApiResponse::problem('Invalid refresh token', 'The refresh token is invalid, revoked, or expired.', 401, 'invalid_refresh_token');

        $row->update(['revoked_at' => now()]);
        return ApiResponse::ok($this->issue(User::findOrFail($row->user_id), $row->device_name ?: 'mobile'));
    }

    private function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required','string'],
            'password' => ['required','string','min:10','max:200','confirmed'],
        ]);
        if (! Hash::check($data['current_password'], $request->user()->password)) {
            return ApiResponse::problem('Wrong password', 'Current password is incorrect.', 422, 'wrong_password');
        }
        $request->user()->update(['password' => $data['password']]);
        return ApiResponse::ok(['changed' => true]);
    }

    private function logout(Request $request)
    {
        optional($request->user()->currentAccessToken())->delete();
        return ApiResponse::ok(['logged_out' => true]);
    }

    private function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();
        RefreshToken::where('user_id', $request->user()->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
        return ApiResponse::ok(['logged_out_all' => true]);
    }

    private function deleteAccount(Request $request)
    {
        $data = $request->validate(['password' => ['required','string']]);
        if (! Hash::check($data['password'], $request->user()->password)) {
            return ApiResponse::problem('Wrong password', 'Password confirmation failed.', 422, 'wrong_password');
        }
        $user = $request->user();
        $user->tokens()->delete();
        RefreshToken::where('user_id', $user->id)->delete();
        RecoveryCode::where('user_id', $user->id)->delete();
        $user->delete();
        return ApiResponse::ok(['deleted' => true]);
    }
}
