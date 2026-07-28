---
name: project-verified-means-user-participable
description: "In the participants domain, \"verified\" means the participable is a User (claimed their account), not that email_verified_at is set."
metadata: 
  node_type: memory
  type: project
  originSessionId: 1c0e5a17-e251-4040-a65c-c6cc8d422df5
  modified: 2026-07-28T08:17:36.760Z
---

Across the participants domain in this project, **"verified" means the participable is an `App\Models\User`** — i.e. the person has claimed / upgraded from a plain `Participant` record into a full user account. It does **not** mean `email_verified_at !== null`.

**Why:** Erasmo's onboarding flow is: coordinator adds someone as a `Participant` (no login), that person later accepts an invite and their pivot's `participable_type` switches to `User`. That switch is the "verified" milestone the UI surfaces (badge in the picker option view, `verified` column on the list). User called this out twice with rising anger when I defaulted to the generic Laravel `email_verified_at` meaning.

**How to apply:** Any "verified" indicator on `ProjectParticipant`, participant cards, picker options, table columns, or exports should compute as `$record->participable_type === App\Models\User::class` (or `$participable instanceof User` when the model is loaded). If you're tempted to check `email_verified_at`, that's a bug — the sending-org's `Organization` vs `ParticipantOrganization` split follows the same "verified = platform-registered class" pattern.
