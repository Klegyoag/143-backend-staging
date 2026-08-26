# Provider Credential Checklist

Core Laravel/PostgreSQL behavior can deploy without enabling every external integration, but these values are required to test the corresponding provider-backed feature.

| Provider | Required values | Used for |
|---|---|---|
| Cloudflare R2 | access key, secret, bucket, endpoint | private media, backups, exports |
| OpenAI | API key, model | optional 143 AI |
| Firebase/FCM | project ID, service account JSON | Android/iOS push |
| PayPal | sandbox/live client credentials, webhook ID, plan IDs | subscriptions |
| Spotify | client ID/secret, redirect URI | Spotify identity/playlist integration |
| Maps provider | API key | nearby/date/location enrichment |
| TURN | URL, username, credential | reliable WebRTC connectivity |

Do not send production secrets in chat or commit them to the repository. Add them directly to Render environment variables.
