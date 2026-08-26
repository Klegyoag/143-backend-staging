# 143 Backend — Final Build Manifest

**Package:** `143-Laravel-Backend-FINAL-v2.0`  
**Packaged:** 26 August 2026  
**Backend:** Laravel 13 / PHP 8.3+  
**Primary database:** PostgreSQL  
**Target hosting:** Render  
**Private object storage:** Cloudflare R2  
**Realtime:** Laravel Reverb + Redis-compatible backend  
**AI provider:** OpenAI  
**Client targets:** Android + iOS

## Contract inventory

- 351 HTTP operations
- 265 distinct HTTP paths
- 53 unique realtime event contracts
- 94 PostgreSQL tables after both migrations
- 36 mobile-feature modules mapped to backend implementation boundaries
- API prefix: `/api/v1`

## Approved authentication override

The original mobile specification's email, phone, OTP, Google and Apple authentication requirements were intentionally replaced by the approved 143 flow:

1. Username + password registration/login.
2. Backend generates a cryptographically random recovery code.
3. Recovery code is shown once; only a secure hash is retained server-side.
4. User must confirm that the recovery code was saved before normal product access.
5. Current Terms and Privacy Policy must be accepted before normal product access.
6. Forgot-password uses username + saved recovery code and rotates the recovery code afterward.

## Included deployment/runtime components

- Laravel API service
- Render Blueprint (`render.yaml`)
- Dockerfile
- PostgreSQL migrations and seeders
- Redis-compatible cache/queues/sessions
- Queue worker
- Minute scheduler
- Reverb WebSocket service
- Cloudflare R2 S3-compatible private media adapter
- FCM push adapter
- PayPal subscription/webhook adapter
- Spotify integration boundary
- WebRTC STUN/TURN configuration boundary
- OpenAI Responses API integration boundary with granular consent scopes
- E2EE public key/prekey transport and encrypted backup/migration records
- Versioned Terms & Privacy documents for development/testing
- OpenAPI 3.1 JSON contract
- Postman collection
- Frontend handoff documentation

## Dependency note

`vendor/` is intentionally not included. The Docker build installs Composer dependencies. A `composer.lock` could not be generated in the packaging environment because Composer/network dependency resolution was unavailable there. Before a public production launch, generate and commit a lock file in a normal connected development/CI environment and rerun Laravel runtime tests.
