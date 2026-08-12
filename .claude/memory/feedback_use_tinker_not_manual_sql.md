# Run one-off DB ops via `artisan tinker`, don't dump SQL

For local DB fixes (nulling a column, cleaning stale rows, backfilling), execute via `php -d memory_limit=512M artisan tinker --execute='...'`. Don't hand the user raw SQL and ask them to paste it into a client.

**Incident:** Needed to null `google_calendar_id` on `projects` + clear `google_event_id` on `project_events` after switching to DWD-impersonated calendars. I dumped `UPDATE ... SET ... WHERE ...` SQL statements for the user to run manually. User: "dude do it through fucking tinkerwell". Ran the same operations via tinker in one command.

**How to apply:**
- Default to tinker for any local schema/data touch-up. It uses the app's connection, respects env, no context switch.
- Prefix with `-d memory_limit=512M` on this machine — the default `memory_limit` for CLI blows up when Filament/blade-icons boot.
- For destructive ops, still show what you're about to run and get consent, but run it yourself.