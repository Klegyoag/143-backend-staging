<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_reset_recovery_and_logout_lifecycle(): void
    {
        $this->withoutMiddleware(
    \Illuminate\Routing\Middleware\ThrottleRequests::class
        );

        $username = 'life_' . Str::lower(Str::random(10));
        $password = 'Test143!Pass2026';
        $newPassword = 'New143!Pass2026';

        // Register
        $register = $this->postJson(
            '/api/v1/auth/register',
            [
                'username' => $username,
                'password' => $password,
                'password_confirmation' => $password,
                'device_name' => 'PHPUnit',
            ],
            [
                'Idempotency-Key' => 'life-register-' . Str::uuid(),
            ]
        );

        $register->assertCreated();

        $accessToken = $register->json('data.access_token');
        $refreshToken = $register->json('data.refresh_token');
        $recoveryCode = $register->json('data.recovery_code');

        $this->assertNotEmpty($accessToken);
        $this->assertNotEmpty($refreshToken);
        $this->assertNotEmpty($recoveryCode);

        // Confirm first recovery code
        $this->withToken($accessToken)
            ->postJson(
                '/api/v1/auth/recovery/confirm',
                ['recovery_code' => $recoveryCode],
                [
                    'Idempotency-Key' =>
                        'life-confirm-' . Str::uuid(),
                ]
            )
            ->assertOk()
            ->assertJsonPath('data.confirmed', true);

        // Refresh token rotation
        $refresh = $this->postJson(
            '/api/v1/auth/token/refresh',
            ['refresh_token' => $refreshToken],
            [
                'Idempotency-Key' =>
                    'life-refresh-' . Str::uuid(),
            ]
        );

        $refresh
            ->assertOk()
            ->assertJsonPath('ok', true);

        $newAccessToken = $refresh->json('data.access_token');
        $newRefreshToken = $refresh->json('data.refresh_token');

        $this->assertNotEmpty($newAccessToken);
        $this->assertNotEmpty($newRefreshToken);
        $this->assertNotEquals($refreshToken, $newRefreshToken);

        // Old refresh token must now be rejected
        $this->postJson(
            '/api/v1/auth/token/refresh',
            ['refresh_token' => $refreshToken],
            [
                'Idempotency-Key' =>
                    'life-old-refresh-' . Str::uuid(),
            ]
        )->assertUnauthorized();

        // Reset password using recovery code
        $reset = $this->postJson(
            '/api/v1/auth/password/reset/recovery-code',
            [
                'username' => $username,
                'recovery_code' => $recoveryCode,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ],
            [
                'Idempotency-Key' =>
                    'life-reset-' . Str::uuid(),
            ]
        );

        $reset
            ->assertOk()
            ->assertJsonPath('data.reset', true)
            ->assertJsonPath(
                'data.recovery_code_shown_once',
                true
            )
            ->assertJsonPath(
                'data.next_step',
                'login_then_confirm_recovery_code'
            );

        $replacementRecoveryCode =
            $reset->json('data.recovery_code');

        $this->assertNotEmpty($replacementRecoveryCode);

        $this->assertDatabaseHas('users', [
            'username' => $username,
            'account_state' => 'recovery_setup_pending',
        ]);

// Password reset must revoke old access tokens.
$this->assertNull(
    \Laravel\Sanctum\PersonalAccessToken::findToken($newAccessToken)
);

// Reset Laravel's cached auth guard between simulated HTTP requests.
$this->app['auth']->forgetGuards();

$this->withToken($newAccessToken)
    ->getJson('/api/v1/auth/recovery/status')
    ->assertUnauthorized();

        // Old password must fail
        $this->postJson('/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
        ])->assertUnprocessable();

        // New password works
        $login = $this->postJson('/api/v1/auth/login', [
            'username' => $username,
            'password' => $newPassword,
            'device_name' => 'PHPUnit',
        ]);

        $login->assertOk();

        $finalAccessToken = $login->json('data.access_token');

        $this->assertNotEmpty($finalAccessToken);

        // Confirm replacement recovery code
        $this->withToken($finalAccessToken)
            ->postJson(
                '/api/v1/auth/recovery/confirm',
                [
                    'recovery_code' =>
                        $replacementRecoveryCode,
                ],
                [
                    'Idempotency-Key' =>
                        'life-confirm-new-' . Str::uuid(),
                ]
            )
            ->assertOk()
            ->assertJsonPath('data.confirmed', true);

        $this->withToken($finalAccessToken)
            ->getJson('/api/v1/auth/recovery/status')
            ->assertOk()
            ->assertJsonPath('data.confirmed', true)
            ->assertJsonPath('data.account_state', 'active');

// Logout
$this->withToken($finalAccessToken)
    ->postJson(
        '/api/v1/auth/logout',
        [],
        [
            'Idempotency-Key' =>
                'life-logout-' . Str::uuid(),
        ]
    )
    ->assertOk()
    ->assertJsonPath('data.logged_out', true);

// Token must actually be deleted.
$this->assertNull(
    \Laravel\Sanctum\PersonalAccessToken::findToken($finalAccessToken)
);

// Clear cached authenticated user before next simulated request.
$this->app['auth']->forgetGuards();

// Logged-out token cannot be reused.
$this->withToken($finalAccessToken)
    ->getJson('/api/v1/auth/recovery/status')
    ->assertUnauthorized();
    }
}