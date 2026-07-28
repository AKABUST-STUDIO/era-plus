---
name: project-polymorphic-select-composite-keys
description: "Filament Selects that pick a polymorphic target use `type:id` composite string values (p:12, u:5, o:3, po:7) with a `resolve()` static on the component class."
metadata: 
  node_type: memory
  type: project
  originSessionId: 1c0e5a17-e251-4040-a65c-c6cc8d422df5
  modified: 2026-07-28T08:17:50.382Z
---

When a Filament `Select` binds to a polymorphic FK on a pivot (e.g. `project_participant.participable_*` or `project_participant.sending_organization_*`), the Select's stored value is a **composite string** of the form `{type_prefix}:{id}`, and the component class exposes a static `resolve(?string $ref): Model|null` that decodes it. The handler on the consuming page (list action, edit page) uses that resolver — no other file ever pattern-matches the prefix.

**Why:** A polymorphic pivot has no single FK; the raw ID is ambiguous (participant #5 vs user #5). Also, we want the Select to search across multiple tables (participants + users, or platform orgs + freeform partner orgs) in one dropdown, and morph relationships can't be filtered/queried by Filament's `->relationship()` idiom. The composite scheme keeps all of that in one Select without a second field.

**How to apply:**

- Prefixes in use today:
  - `p:{id}` → `App\Models\Project\Participant`
  - `u:{id}` → `App\Models\User`
  - `o:{id}` → `App\Models\Organization` (platform-registered org)
  - `po:{id}` → `App\Models\Project\ParticipantOrganization` (freeform partner org)
- Live inside the Select component classes under `App\Filament\Project\Resources\ProjectParticipants\Components\{Participable,SendingOrganization}Select.php` — each has a `public static function resolve(?string $ref): ...|null` that splits on `:` and matches on prefix.
- Page action handlers decode via `ParticipableSelect::resolve($data['participable_id'])` / `SendingOrganizationSelect::resolve($data['sending_organization_id'])` — never re-parse the prefix themselves.
- Quick-add creates a fresh row of the *default* type (Participant / ParticipantOrganization), then re-emits the state as `{prefix}:{newId}` so the same resolver picks it up on submit.
- When a search returns items from N tables, ordering can be per-type (participants first, then users, etc.) — no global sort across the composite results.

See also [[project-verified-means-user-participable]] — the type-prefix distinction is what "verified" is derived from.
