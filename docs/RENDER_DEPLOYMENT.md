# Render Deployment — 143 Backend

The repository is packaged for a Render Blueprint deployment using `render.yaml`.

## Services created

- **143-api** — public Laravel API web service
- **143-worker** — Redis-backed Laravel queue worker
- **143-reverb** — public WebSocket service for Laravel Reverb
- **143-scheduler** — cron service invoking `php artisan schedule:run` every minute
- **143-cache** — Redis-compatible key-value service
- **143-postgres** — central PostgreSQL database

All services use the Singapore region in the supplied Blueprint so the backend/database stay geographically coherent for the chosen testing setup.

## 1. Put the backend in Git

Commit this backend directory to a private Git repository. Do not commit `.env` or provider keys.

## 2. Create the Render Blueprint

In Render, create a new Blueprint from the repository containing `render.yaml`.

Render will provision the web/worker/realtime/scheduler/cache/database resources declared in the Blueprint. The API's pre-deploy command runs migrations; the initial deploy hook seeds legal documents, games, journeys, achievements and other baseline seed data.

## 3. Generate and share one Laravel APP_KEY

Generate one value using a trusted local Laravel environment:

```bash
php artisan key:generate --show
```

Set the **same `APP_KEY`** on:

- 143-api
- 143-worker
- 143-reverb
- 143-scheduler

Do not generate a different key per service because encrypted application data/tokens must be readable consistently by all Laravel processes.

## 4. Configure Reverb credentials

Generate strong random values for:

- `REVERB_APP_KEY`
- `REVERB_APP_SECRET`

Use the same pair on `143-api` and `143-reverb`. `REVERB_APP_ID` is set to `143` by the Blueprint.

## 5. Configure Cloudflare R2

Set the same R2 values on every service that reads/writes private files. At minimum this includes `143-api` and `143-worker`:

- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_BUCKET`
- `AWS_ENDPOINT`

`AWS_ENDPOINT` should be the account's R2 S3-compatible endpoint.

## 6. Configure OpenAI

On `143-api` set:

- `OPENAI_API_KEY`
- optionally `OPENAI_MODEL`

The model is configuration-driven. The included default can be changed without code changes.

## 7. Configure push notifications

Set:

- `FCM_PROJECT_ID`
- `FCM_SERVICE_ACCOUNT_JSON`

For iOS, configure APNs credentials inside the Firebase project so FCM can deliver to iOS registration tokens.

## 8. Configure PayPal

Testing defaults to `PAYPAL_ENV=sandbox`. Add the client credentials, webhook ID and four plan IDs listed in `.env.example`.

The PayPal webhook route is server-verified before subscription state is trusted.

## 9. Optional integrations

Configure as needed:

- Spotify client credentials + redirect URI
- Maps API key
- STUN/TURN server credentials

## 10. Smoke tests

After deployment, verify:

```text
GET https://<api-host>/up
GET https://<api-host>/api/v1/bootstrap
```

Then run the registration flow from Postman:

1. register
2. confirm recovery code
3. fetch legal docs
4. accept consents
5. create a couple space

## 11. Frontend configuration

Point Android/iOS to:

```text
https://<api-host>/api/v1
```

Use the Reverb host configured for the deployment and authenticate private user/couple channels using the Laravel broadcasting auth flow.

## Important

The repository is deployment-ready, but this package cannot create or own your Render/Cloudflare/OpenAI/PayPal/Firebase accounts. Live provider-backed behavior begins only after their real credentials are placed in Render environment variables.
