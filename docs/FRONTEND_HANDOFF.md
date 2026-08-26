# Frontend Handoff

## Base URL
`https://<render-service>.onrender.com/api/v1`

## Required onboarding/auth sequence
1. `POST /auth/register` with username/password/password_confirmation.
2. Display the returned `recovery_code` **once** and force the user to save it.
3. `POST /auth/recovery/confirm`.
4. Load `GET /legal/documents`, then `POST /consents` for the active Terms and Privacy Policy.
5. Enter the normal app.

On login, inspect `data.setup.recovery_confirmed`. If false, route to the recovery confirmation screen. Protected APIs may return HTTP 428 with `recovery_confirmation_required` or `legal_acceptance_required`.

## Tokens
Send `Authorization: Bearer <access_token>`. Use `/auth/token/refresh` with the refresh token before/after expiry. Store tokens in OS-protected secure storage.

## Media
Use `/uploads/presign`, upload directly to Cloudflare R2 using the returned signed URL, then call `/uploads/complete`. Keep sensitive cached media only in app-private storage.

## Realtime
Subscribe to private `user.{userId}` and `couple.{coupleSpaceId}` channels through Laravel Reverb. Event names are listed in `config/realtime_events.php`.

## Errors
Validation/security failures use an `application/problem+json` style body. Use `code` for programmatic routing.

## Reference files
- `openapi.json`
- `API_CATALOG.md`
- `143.postman_collection.json`
- `AUTH_RECOVERY_FLOW.md`
- `LOCAL_CACHE_CONTRACT.md`
