<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthRecoveryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_confirm_recovery_and_become_active(): void
    {
        $username = 'test_' . Str::lower(Str::random(10));
        $password = 'Test143!Pass2026';

        $register = $this->postJson(
            '/api/v1/auth/register',
            [
                'username' => $username,
                'password' => $password,
                'password_confirmation' => $password,
                'display_name' => '143 Automated Test',
                'device_name' => 'PHPUnit',
            ],
            [
                'Idempotency-Key' => 'phpunit-register-' . Str::uuid(),
            ]
        );

        $register
            ->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.recovery_code_shown_once', true)
            ->assertJsonPath('data.next_step', 'confirm_recovery_code');

        $accessToken = $register->json('data.access_token');
        $recoveryCode = $register->json('data.recovery_code');

        $this->assertNotEmpty($accessToken);
        $this->assertNotEmpty($recoveryCode);

        $confirm = $this
            ->withToken($accessToken)
            ->postJson(
                '/api/v1/auth/recovery/confirm',
                [
                    'recovery_code' => $recoveryCode,
                ],
                [
                    'Idempotency-Key' => 'phpunit-recovery-' . Str::uuid(),
                ]
            );

        $confirm
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.confirmed', true)
            ->assertJsonPath(
                'data.next_step',
                'accept_legal_documents'
            );

        $status = $this
            ->withToken($accessToken)
            ->getJson('/api/v1/auth/recovery/status');

        $status
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.confirmed', true)
            ->assertJsonPath('data.account_state', 'active');

        $this->assertDatabaseHas('users', [
            'username' => $username,
            'account_state' => 'active',
        ]);
    }
}