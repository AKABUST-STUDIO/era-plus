# Don't add features the user didn't ask for

Do exactly what was requested. Don't add adjacent toggles, badges, columns, or "nice to have" affordances.

**Incidents in one session:**
- Added `all_day` DB column + migration — user wanted computed, not stored.
- Added an "All day" toggle to the event form — user hadn't asked, told me to remove.
- Added status-based colored borders on calendar avatar chips — user hadn't asked, told me plain avatars only.
- Rendered attendee email INSIDE the RSVP status badge — user wanted email separate.
- Promoted creator to `organizer: true` with special dedupe path in Google payload — user said "just make sure all members are added too and thats it".

**How to apply:** When you finish the requested change, STOP. Do not throw in "and also I…". If a related improvement seems obvious, ask before implementing, or leave it out entirely.