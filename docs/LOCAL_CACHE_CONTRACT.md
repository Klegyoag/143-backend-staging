# Local Device Cache Contract

The mobile client may cache API data and R2 media locally for speed/offline use. Sensitive couple content must remain in app-private storage.

- Never place private media in public Downloads/Pictures folders by default.
- Store access/refresh tokens and E2EE private keys only in Android Keystore-backed / iOS Keychain-backed secure storage.
- Cache only authorized couple-space data. Purge it on logout, account deletion, partner unlink, or session revocation as appropriate.
- Use `/sync/state` and `/sync/reconcile` after reconnecting.
- Treat signed R2 URLs as temporary; cache object bytes, not the signed URL as a permanent locator.
- Respect server-side delete/revoke signals and revalidate sensitive content after long offline periods.
