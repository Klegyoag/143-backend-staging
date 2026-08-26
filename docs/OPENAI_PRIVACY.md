# 143 AI — OpenAI Privacy Boundary

The 143 AI Assistant is optional.

## Opt-in

The user must first enable AI and select allowed context scopes through the AI settings API. Supported scopes are deliberately limited in backend code.

## Per-request permission

AI requests may include a `context` object keyed by scope. The backend compares every supplied scope against the user's stored allow-list and rejects the request if any scope is not allowed.

The backend does **not** automatically load unrestricted private chat history into OpenAI requests.

## Data minimization

Only the explicit prompt and user-approved request context are sent to the configured OpenAI Responses API endpoint. The local `ai_requests` audit entry records provider request ID and used scope names rather than copying the supplied private context into the audit record.

## Configuration

```env
OPENAI_API_KEY=
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=<configured model>
```

Keep the API key on the Laravel server. Never embed it in Android/iOS.
