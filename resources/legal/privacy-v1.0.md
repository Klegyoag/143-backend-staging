# 143 Privacy Policy

**Version:** 1.0  
**Effective date:** 26 August 2026

This Privacy Policy explains how the 143 mobile application and backend services ("143") handle information. The data controller or responsible operator is the company or individual identified as the operator in the app's About/Legal section ("we", "us", or "our").

143 is designed as a private digital space for two linked users. Privacy-sensitive features are intended to use explicit permission, limited access, revocable sharing, and secure defaults.

## 1. Information we process
Depending on the features you use, 143 may process:

- **Account data:** username, password hash, recovery-code verification hash, display name, profile photo, bio, language, timezone, account status, and security settings.
- **Couple-space data:** partner linkage, relationship settings, anniversary/special dates, couple profile, themes, preferences, and invite status.
- **Communications and content:** messages, reactions, voice messages, calls/signaling metadata, memories, letters, notes, Time Capsules, photos, videos, files, albums, calendar items, goals, wishlists, game responses, journeys, and other content you intentionally create.
- **Location information:** one-time or temporary live location when you explicitly use a location-sharing feature or a feature that requires nearby suggestions.
- **Device and technical data:** push tokens, linked-device identifiers, app version, platform, IP/network metadata, request identifiers, security/audit events, crash/error data where enabled, and session information.
- **Subscription data:** plan, payment/subscription identifiers, status, receipts, and payment-provider event data. Full card or PayPal credentials are handled by the payment provider rather than stored by 143.
- **AI settings and request metadata:** whether AI is enabled, allowed data scopes, provider request identifiers, and limited operational metadata. 143 is designed not to automatically expose unrestricted private chats to AI services.

## 2. Username, password, and recovery code
143 uses username-and-password authentication. Passwords are stored using one-way password hashing. A recovery code is generated for password recovery and shown to you once. The backend stores a protected hash used to verify a supplied code, not the readable recovery code itself.

After password recovery or manual recovery-code regeneration, the previous recovery code is invalidated and a new code must be saved and confirmed.

## 3. Why we process information
We process information to:

- create and secure accounts;
- link two users into a private couple space;
- provide chat, memories, calls, Together Room, calendar, games, goals, mood, location, notifications, widgets, backups, and other requested features;
- synchronize data across authorized devices;
- generate user-requested exports and restore encrypted backups;
- provide paid-plan entitlements and reconcile payments;
- detect abuse, enforce authorization, prevent fraud, rate-limit attacks, and maintain audit/security records;
- provide optional AI suggestions when enabled and requested;
- respond to support requests; and
- comply with legal obligations.

Where applicable, the lawful basis may include performance of a contract, legitimate interests in operating and securing the service, consent for optional/sensitive processing, and compliance with law. The applicable basis depends on jurisdiction and feature.

## 4. Couple content and access
Content within a couple space is normally accessible to the two authorized members of that space according to feature rules. Some features intentionally hide information until a condition is met, such as both partners answering a daily question or reconnection prompt.

Backend authorization checks are designed to prevent one couple from reading another couple's data. No authorization system can guarantee perfect security, so users should still protect credentials and devices.

## 5. End-to-end encryption
For features marked as end-to-end encrypted, encryption/decryption may occur on user devices. The backend may store ciphertext, public identity keys, signed prekeys, and one-time prekeys needed to establish encrypted sessions. Private encryption keys should remain on the user's device.

Metadata required to route encrypted communications, prevent abuse, and operate the service may still be processed by the backend even when message content is end-to-end encrypted.

## 6. Media and Cloudflare R2
Private photos, videos, documents, voice media, exports, and encrypted backups may be stored in Cloudflare R2 or another configured S3-compatible private object store. Buckets are intended to remain private. Mobile clients receive short-lived signed URLs for authorized uploads/downloads rather than permanent public object URLs.

The 143 app may also maintain a private local cache on your device for speed and offline use. Local cached data is controlled by the mobile operating system and app implementation. Sensitive data should be stored in app-private storage rather than public media folders.

## 7. Location
143 processes precise location only when a location feature is intentionally used and the operating system grants permission. Temporary sharing sessions can have an expiry and a stop-sharing control. Location may be used for live sharing, destination/arrival features, travel plans, or nearby date suggestions.

Location should not be considered emergency-grade. Device and network conditions can make it delayed or inaccurate.

## 8. Biometrics
Fingerprint, Face ID, and similar biometric checks used to unlock the app are performed by Android or iOS security frameworks. 143 does not require raw biometric templates to be uploaded to the backend.

## 9. AI processing
The 143 AI Assistant is optional and disabled unless enabled by the user. AI requests may be sent to OpenAI when the feature is configured. Users can choose allowed context scopes. Private chats are not intended to be automatically provided as unrestricted AI context.

We minimize stored AI request logs and avoid storing raw private prompt context in operational AI logs where not required. OpenAI processes information according to the applicable service terms and privacy commitments for the configured API account.

AI features must not be treated as medical, psychological, legal, financial, or relationship diagnosis.

## 10. Payments and subscriptions
Subscription payments may be handled by PayPal, app stores, or another approved payment provider. 143 may receive identifiers, status changes, billing amounts/currency, receipts, and webhook events needed to confirm entitlement. Payment providers process payment credentials under their own privacy terms.

## 11. Other service providers
To operate 143 we may use infrastructure and service providers such as hosting/database providers, Cloudflare R2, push-notification providers, payment providers, music/video providers, map providers, TURN/STUN services, and OpenAI. Providers receive only the information reasonably required for their function, subject to technical and contractual controls available for that provider.

## 12. Retention
We keep information only as long as reasonably needed for the purposes described here, to provide active services, satisfy user-requested backups, maintain security/audit records, reconcile subscriptions, resolve disputes, or comply with law.

When an account is deleted, data associated solely with that account is scheduled for deletion or anonymization subject to retention requirements. Shared content may require special handling because another partner may also have rights or an independent copy. Encrypted backups are retained according to backup controls and retention settings.

## 13. Security
Security measures may include HTTPS-only networking, private object storage, hashed passwords/recovery codes, authorization checks, short-lived access tokens, refresh-token revocation, rate limiting, audit logs, encrypted backups, E2EE-capable key infrastructure, signed upload/download URLs, and session/device revocation.

No electronic service can guarantee absolute security. Report suspected compromise through support and revoke affected sessions/devices promptly.

## 14. Your controls and rights
Depending on your jurisdiction and feature availability, you may be able to:

- access and edit profile/account information;
- manage privacy and notification settings;
- control location sharing and permissions;
- enable/disable AI and permitted context scopes;
- revoke linked devices and sessions;
- export user/couple data;
- delete content;
- leave/unlink a couple space; and
- delete your account.

Applicable law may also provide rights to access, correct, delete, restrict, object to certain processing, withdraw consent, or receive portable data. Requests can be made through in-app support.

## 15. Children
143 is not intended for users below the minimum age allowed by applicable law, app-store rules, or the product's eligibility requirements. We do not knowingly design the couple features for children who cannot legally consent to the service.

## 16. International processing
Service providers may process data in countries other than the one where you live. Where required, appropriate transfer mechanisms and safeguards should be used by the operator and relevant providers.

## 17. Legal requests and safety
We may preserve or disclose limited information when required by valid law, court order, or a lawful request, or when reasonably necessary to protect the rights, safety, and security of users, the service, or others, subject to applicable law.

## 18. Changes to this policy
We may update this Privacy Policy as 143 changes. Material revisions are versioned, and the backend can require acceptance of a new active version before protected features continue.

## 19. Contact
Privacy questions and rights requests can be submitted through 143's in-app **Contact Support** feature or through the operator contact details shown in the app's About/Legal section.
