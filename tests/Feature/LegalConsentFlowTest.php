<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegalConsentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_and_privacy_acceptance_unlocks_protected_api(): void
    {
        $this->seed();

        $username = 'legal_' . Str::lower(Str::random(10));
        $password = 'Test143!Pass2026';

        $register = $this->postJson(
            '/api/v1/auth/register',
            [
                'username' => $username,
                'password' => $password,
                'password_confirmation' => $password,
                'display_name' => 'Legal Flow Test',
                'device_name' => 'PHPUnit',
            ],
            [
                'Idempotency-Key' => 'legal-register-' . Str::uuid(),
            ]
        );

        $register->assertCreated();

        $accessToken = $register->json('data.access_token');
        $recoveryCode = $register->json('data.recovery_code');

        $this->withToken($accessToken)
            ->postJson(
                '/api/v1/auth/recovery/confirm',
                ['recovery_code' => $recoveryCode],
                [
                    'Idempotency-Key' =>
                        'legal-recovery-' . Str::uuid(),
                ]
            )
            ->assertOk()
            ->assertJsonPath('data.confirmed', true);

        // Protected feature must still be locked
        // until legal documents are accepted.
        $this->withToken($accessToken)
            ->getJson('/api/v1/xp')
            ->assertStatus(428);

        $legal = $this->getJson('/api/v1/legal/documents');

        $legal
            ->assertOk()
            ->assertJsonPath('ok', true);

        $documents = collect($legal->json('data'));

        $termsId = $documents->firstWhere('type', 'terms')['id'];
        $privacyId = $documents->firstWhere('type', 'privacy')['id'];

        $this->assertNotEmpty($termsId);
        $this->assertNotEmpty($privacyId);

        foreach ([$termsId, $privacyId] as $documentId) {
            $this->withToken($accessToken)
                ->postJson(
                    '/api/v1/consents',
                    [
                        'legal_document_id' => $documentId,
                        'accepted' => true,
                        'metadata' => [
                            'source' => 'phpunit',
                        ],
                    ],
                    [
                        'Idempotency-Key' =>
                            'legal-consent-' . Str::uuid(),
                    ]
                )
                ->assertOk()
                ->assertJsonPath('ok', true);
        }

        // Both legal documents accepted:
        // protected API should now unlock.
        $this->withToken($accessToken)
            ->getJson('/api/v1/xp')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.xp', 0);

        $this->assertDatabaseCount('user_consents', 2);
    }
}