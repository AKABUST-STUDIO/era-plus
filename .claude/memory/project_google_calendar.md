# Google Calendar integration

One Google Calendar per `Project`, owned by an impersonated Workspace user (`GOOGLE_CALENDAR_IMPERSONATE`) via Domain-Wide Delegation. Service account alone returns `403 forbiddenForServiceAccounts` on attendee invites — DWD is mandatory.

- Service account JSON is base64'd into `GOOGLE_CALENDAR_CREDENTIALS_B64` and materialized to `storage/app/google-calendar/service-account-credentials.json` in `AppServiceProvider::boot()`.
- Every API call in `EventService` and `CalendarService` calls `$this->client->setSubject(GoogleCalendarCredentials::impersonate())` before firing. Without this, DWD does nothing.
- Attendees list = event participants ∪ ALL project staff (`$project->users`), deduped by email. No creator-as-organizer promotion — the impersonated user is already Google's organizer.
- `ProjectEvent::all_day` is a **computed accessor**, not a DB column (see [[feedback_computed_over_db_columns]]). True when `starts_at` is 00:00 and `ends_at` is 00:00 or 23:59:59.
- Event colors auto-assigned via `crc32(event.id) % 11` → Google's 11-color palette (`App\Support\GoogleCalendarEventColor`), echoed to FullCalendar as `backgroundColor`/`borderColor`.
- Webhook URL derived from `route('webhooks.google-calendar')` at runtime, not env var (see [[feedback_route_over_env_url]]). Must be HTTPS.