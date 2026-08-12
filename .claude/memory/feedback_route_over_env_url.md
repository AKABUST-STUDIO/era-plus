# Derive internal URLs from route(), not env vars

For URLs that point back to THIS app (webhooks, callbacks, redirect targets), call `route('name')`. Don't add an env var the user has to keep in sync with `APP_URL`.

**Incident:** Added `GOOGLE_CALENDAR_WEBHOOK_URL` env var + `config/services.php` entry so `WebhookService::subscribe()` could pass it to Google. User: "bro make the webhook be the route from web, i shouldnt define it". Ripped out the config + env, replaced with `route('webhooks.google-calendar')` at call time, with an HTTPS guard for local dev where APP_URL is http.

**How to apply:** Env vars are for EXTERNAL services (Stripe secret, Google client ID). URLs the app itself serves come from `route()` — the framework already knows them via `APP_URL`. Only fall back to env if the app is behind a proxy/reverse-tunnel where `APP_URL` diverges from the reachable URL.