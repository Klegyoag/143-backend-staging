# Authentication & Recovery Flow

143 intentionally has **no email/phone/social/OTP sign-in**. Authentication is username + password.

## Registration
`POST /auth/register` → account state `recovery_setup_pending` → one-time readable recovery code → `POST /auth/recovery/confirm` → accept legal documents → normal access.

Readable recovery codes are not persisted; only a password-hashed verification value is stored.

## Forgot password
`POST /auth/password/reset/recovery-code` with username, saved recovery code, new password and confirmation. All existing sessions are invalidated, the old recovery code is consumed, and a new one-time recovery code is returned. The user must login and confirm the new code.

## Regenerate while logged in
`POST /auth/recovery-code/regenerate` requires the current password. It invalidates the current recovery code and returns a new code once. Normal protected APIs are blocked until the new code is confirmed.
