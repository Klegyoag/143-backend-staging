# Cloudflare R2 Setup — 143

143 uses Cloudflare R2 as permanent private object storage and the mobile device as a local app-private cache.

## Bucket

Create a private R2 bucket dedicated to 143 testing/production. Do not expose it as a public bucket.

Create an R2 API token restricted to the required bucket and object read/write operations.

Set these Laravel variables:

```env
UPLOAD_DISK=s3
AWS_ACCESS_KEY_ID=<R2 access key>
AWS_SECRET_ACCESS_KEY=<R2 secret>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=<bucket name>
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

## Upload flow

1. Client requests `POST /api/v1/uploads/presign` with filename, MIME type and size.
2. Laravel authorizes the current couple space and creates a private object key.
3. Client uploads bytes directly to the short-lived signed R2 URL.
4. Client calls `POST /api/v1/uploads/complete`.
5. Laravel verifies that the object exists, belongs to the current couple-space prefix and has the declared size before creating the media record.

## Download flow

`GET /api/v1/media/{mediaId}` returns a temporary private URL only after couple-space authorization.

## Local mobile cache rules

The backend does not treat mobile cache as authoritative storage. Android/iOS should cache private content only in app-private directories, keep authentication/E2EE secrets in OS-protected secure storage, and purge/revalidate content when sessions, couple membership or permissions change.
