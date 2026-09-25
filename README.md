> **FINAL SOURCE PACKAGE v2.0 — packaged 26 August 2026.** See `docs/VALIDATION_REPORT.md`, `docs/BUILD_MANIFEST.md`, and `CHECKSUMS.sha256` for the packaging audit. Provider credentials are intentionally not included.

# 143 Backend — Laravel + PostgreSQL

Backend package for the **new standalone 143 mobile application**. It is designed for an online testing/staging deployment on Render with one central PostgreSQL database, Redis-compatible cache/queues, Laravel Reverb realtime, Cloudflare R2 private object storage, and optional OpenAI features.

## Fixed implementation baseline

- Source mobile baseline: **514 capabilities across 36 modules**
- API contract after the approved auth redesign: **356 HTTP operations across 270 paths**
- Realtime contract: **53 event types**
- PostgreSQL schema after migrations: **94 tables**
- API prefix: `/api/v1`
- Client targets: Android + iOS
- Authentication: **username + password only**
- Recovery: one-time readable recovery code shown after registration/reset/regeneration; only its hash is stored
- Normal product access is blocked until recovery-code confirmation and current legal-document acceptance
- Permanent private files: Cloudflare R2 through Laravel's S3-compatible filesystem adapter
- Local device storage/cache is a frontend concern governed by `docs/LOCAL_CACHE_CONTRACT.md`
- AI: optional OpenAI integration with explicit opt-in and per-scope context permission

The original specification's email/phone/social/OTP sign-in requirements were intentionally replaced by the user-approved username/password + mandatory recovery-code flow. No email/SMS provider is required for account recovery.

## Main backend components

- Laravel 13 application API
- PostgreSQL primary datastore
- Laravel Sanctum access tokens + hashed rotating refresh tokens
- Redis-compatible cache, queues, rate limiting and realtime support
- Laravel Reverb private user/couple channels
- Cloudflare R2 presigned private uploads/downloads
- Background worker for exports, push delivery and queued work
- Scheduler for Time Capsules, scheduled love messages, surprises, reminders, expiry handling and achievements
- FCM HTTP v1 push adapter (Android and iOS via Firebase/APNs configuration)
- PayPal subscription adapter + verified webhook processing
- Spotify OAuth/profile-playlist integration boundary
- WebRTC signaling/ICE server configuration boundary
- End-to-end-encryption key bundle/prekey transport boundary
- Encrypted backup/device-migration records
- OpenAI Responses API adapter with user-controlled scopes
- JSON and real PDF data export (`dompdf/dompdf`)
- OpenAPI 3.1 contract + Postman collection
- Render Blueprint + Docker deployment package

## Required onboarding/auth sequence

1. `POST /api/v1/auth/register`
2. Show the returned recovery code once and require the user to save it.
3. `POST /api/v1/auth/recovery/confirm`
4. `GET /api/v1/legal/documents`
5. `POST /api/v1/consents` for the active Terms and Privacy Policy.
6. Enter the normal 143 product.

Forgot password:

1. User enters username + saved recovery code + new password.
2. `POST /api/v1/auth/password/reset/recovery-code`
3. Existing sessions are revoked.
4. Old recovery code is invalidated.
5. A new recovery code is displayed once.
6. User logs in and confirms the new recovery code before normal access resumes.

See `docs/AUTH_RECOVERY_FLOW.md`.

## Frontend handoff

Give the mobile frontend developer:

- `docs/openapi.json`
- `docs/API_CATALOG.md`
- `docs/143.postman_collection.json`
- `docs/FRONTEND_HANDOFF.md`
- `config/realtime_events.php`

Normal API success envelopes follow this shape:

```json
{"ok":true,"data":{},"meta":{},"trace_id":"..."}
```

Validation/security failures use an `application/problem+json` style body.

## Render deployment

The repository includes `render.yaml` defining:

- `143-api` — Laravel HTTPS API web service
- `143-worker` — queue worker
- `143-reverb` — realtime WebSocket service
- `143-scheduler` — minute scheduler
- `143-cache` — Redis-compatible key-value service
- `143-postgres` — central PostgreSQL database

Detailed deployment instructions and the required shared secrets are in `docs/RENDER_DEPLOYMENT.md`.

## Cloudflare R2

R2 is configured through the `s3` disk. Set:

```env
UPLOAD_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=auto
AWS_BUCKET=
AWS_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

The media flow is:

`/uploads/presign` → client PUT directly to R2 → `/uploads/complete` → private temporary download URLs.

See `docs/R2_SETUP.md`.

## OpenAI privacy boundary

143 AI is disabled until the user enables it in AI settings. The client may send only context scopes that the user explicitly enabled. The backend does **not** automatically read private chat history for AI requests. Provider request IDs and used scope names are logged; private conversation content is not copied into the AI audit record.

See `docs/OPENAI_PRIVACY.md`.

## Project legal documents

Versioned project-specific draft Terms and Privacy Policy are included at:

- `resources/legal/terms-v1.0.md`
- `resources/legal/privacy-v1.0.md`

They are wired into the legal acceptance system and are suitable for development/testing. They are **not a substitute for review by qualified legal counsel before a public commercial launch**.

## Local Docker development (optional)

The target is online Render testing, but a local Docker stack is retained for development.

```bash
cp .env.docker.example .env
docker compose up -d --build
docker compose exec app php artisan migrate --seed
```

## Static verification

Run:

```bash
php scripts/validate_backend.php
```

It verifies contract counts, domain-resource implementation coverage, realtime-event counts, auth override, database table inventory, OpenAPI/Postman counts, required deployment/docs files and PHP syntax.

Full Laravel/PHPUnit runtime tests require Composer dependencies:

```bash
composer install
php artisan test
```

## Before a live deployment

Provider-dependent features require real credentials in Render environment variables: R2, OpenAI, FCM, PayPal, Spotify and TURN (and a maps key if nearby suggestions are enabled). Keep all provider secrets out of Git.

For a public production release, also complete device-level QA, security/load testing, backup/restore drills, privacy/legal review, App Store/Play Store purchase-policy review, and provider-policy review for synchronized media features.
