<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QrLinkedDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_approval_links_and_can_revoke_a_browser(): void
    {
        $this->seed();
        $password = 'Test143!Pass2026';
        $register = $this->postJson('/api/v1/auth/register', [
            'username' => 'qr_'.Str::lower(Str::random(10)),
            'password' => $password,
            'password_confirmation' => $password,
            'device_name' => 'Phone',
        ], ['Idempotency-Key' => (string) Str::uuid()]);
        $register->assertCreated();
        $phoneToken = $register->json('data.access_token');
        $this->withToken($phoneToken)->postJson('/api/v1/auth/recovery/confirm', [
            'recovery_code' => $register->json('data.recovery_code'),
        ], ['Idempotency-Key' => (string) Str::uuid()])->assertOk();

        $documents = collect($this->getJson('/api/v1/legal/documents')->json('data'));
        foreach (['terms', 'privacy'] as $type) {
            $this->withToken($phoneToken)->postJson('/api/v1/consents', [
                'legal_document_id' => $documents->firstWhere('type', $type)['id'],
                'accepted' => true,
            ], ['Idempotency-Key' => (string) Str::uuid()])->assertOk();
        }

        $create = $this->withHeader('Authorization', '')->postJson('/api/v1/linked-devices/qr-sessions', [
            'device_name' => 'Test Browser', 'platform' => 'web', 'browser' => 'PHPUnit',
        ], ['Idempotency-Key' => (string) Str::uuid()]);
        $create->assertCreated();
        $session = $create->json('data');
        $id = $session['session_id'];
        parse_str(parse_url($session['qr_payload'], PHP_URL_QUERY), $query);

        $this->getJson("/api/v1/linked-devices/qr-sessions/$id?browser_secret=".Str::random(64))
            ->assertNotFound();
        $this->withToken($phoneToken)->getJson("/api/v1/linked-devices/qr-sessions/$id/preview?approval_token=".$query['token'])
            ->assertOk()->assertJsonPath('data.device_name', 'Test Browser');
        $this->withToken($phoneToken)->postJson("/api/v1/linked-devices/qr-sessions/$id/approve", [
            'approval_token' => $query['token'], 'approving_device_name' => 'Phone',
        ], ['Idempotency-Key' => (string) Str::uuid()])->assertOk();

        $status = $this->withHeader('Authorization', '')->getJson("/api/v1/linked-devices/qr-sessions/$id?browser_secret=".$session['browser_secret']);
        $status->assertOk()->assertJsonPath('data.status', 'approved');
        $exchange = $this->postJson("/api/v1/linked-devices/qr-sessions/$id/exchange", [
            'browser_secret' => $session['browser_secret'],
            'exchange_grant' => $status->json('data.exchange_grant'),
        ], ['Idempotency-Key' => (string) Str::uuid()]);
        $exchange->assertOk();
        $webToken = $exchange->json('data.access_token');
        $this->assertNotEmpty($webToken);
        $this->postJson("/api/v1/linked-devices/qr-sessions/$id/exchange", [
            'browser_secret' => $session['browser_secret'],
            'exchange_grant' => $status->json('data.exchange_grant'),
        ], ['Idempotency-Key' => (string) Str::uuid()])->assertStatus(422);

        $devices = $this->withToken($phoneToken)->getJson('/api/v1/linked-devices');
        $devices->assertOk();
        $deviceId = $devices->json('data.0.id');
        $this->withToken($phoneToken)->deleteJson("/api/v1/linked-devices/$deviceId", [], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', explode('|', $webToken, 2)[1]),
        ]);
    }
}
