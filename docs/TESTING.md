# Backend Validation & Testing

## Static package validator

```bash
php scripts/validate_backend.php
```

It checks:

- 351 HTTP operations
- 265 paths
- 53 unique realtime events
- zero generic route handlers
- every domain contract resource has an implementation branch/table
- username/recovery auth replacement and absence of removed social/email/phone/OTP routes
- 94-table PostgreSQL inventory after the migration removes the legacy OTP table
- OpenAPI counts
- Postman request counts
- legal/deployment/handoff files
- PHP syntax across source files

## Framework tests

After dependencies are installed:

```bash
composer install
php artisan test
```

Feature tests verify contract/realtime counts and Laravel's health route.

## Database test

Against a disposable PostgreSQL database:

```bash
php artisan migrate:fresh --seed
```

Then exercise the Postman collection in sequence, especially auth recovery, couple linking, media authorization, chat, scheduled jobs, PayPal sandbox webhooks and AI permission rejection/acceptance.

## Provider tests

Provider-dependent features require sandbox/test credentials. Missing credentials must be treated as configuration errors rather than simulated success.
