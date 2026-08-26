# Security Model — 143 Backend

## Account access

- Username/password only.
- Passwords are hashed by Laravel's password cast.
- Recovery codes are cryptographically random and shown once; only password-hashed verification values are stored.
- Normal protected routes require confirmed recovery setup and current legal acceptance.
- Password recovery revokes existing access/refresh sessions and rotates the recovery code.
- Access tokens are short-lived; refresh tokens are random, stored only as SHA-256 hashes and rotated on refresh.

## Authorization

- Couple-owned core resources are scoped to the authenticated user's current couple space.
- Couple membership is capped at two users by service logic/transactions.
- Realtime channels are private and authorize user or couple membership.
- Private media temporary URLs are issued only after authorization.

## Sensitive client data

- Biometrics stay on the device; the backend never stores fingerprint/Face ID templates.
- E2EE private keys remain on client devices. Backend crypto endpoints transport/store public identity keys, signed prekeys and one-time prekeys.
- Local cached couple content belongs in app-private storage.

## Network/API controls

- Production assumes HTTPS at the Render edge.
- API/auth/sensitive rate limits are configured.
- Write routes use idempotency middleware.
- Request IDs, security headers and API audit logging are global middleware.
- Validation/security errors use structured problem responses.

## Provider secrets

R2, OpenAI, FCM, PayPal, Spotify and TURN secrets belong only in Render environment variables or equivalent secret storage, never in mobile applications or Git.

## Release verification

Before public production release, run dependency scanning, SAST, authorization tests, abuse/rate-limit tests, load tests, backup restoration tests and independent security review.
