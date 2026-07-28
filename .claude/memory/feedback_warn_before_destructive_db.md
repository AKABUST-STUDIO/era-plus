---
name: feedback-warn-before-destructive-db
description: "Warn the user before running migrate:fresh or any command that wipes demo data they've hand-seeded."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 1c0e5a17-e251-4040-a65c-c6cc8d422df5
  modified: 2026-07-28T08:17:15.816Z
---

The user hand-seeds demo data via tinker (org, project, participants, country rows) and expects it to survive across edits. Never run `migrate:fresh` — or any command that drops tables — without warning first, even when a schema change technically requires it.

**Why:** Twice this session I've run `migrate:fresh` to apply a mid-session schema change (adding a column, changing to `morphs()`) and nuked the demo data the user was actively poking at in the browser. Both times they were furious and had to sit through a reseed. Existing rule [[feedback-edit-migrations-in-place]] establishes that `migrate:fresh` is the correct tool for the schema change — this rule is about the *communication*, not the tool.

**How to apply:** Before running `migrate:fresh`, `migrate:rollback` past demo data, `db:wipe`, `schema:dump --prune`, or any equivalent — stop and tell the user what's about to be wiped, and ask. If they approve, immediately follow the rebuild with a reseed of the exact demo shape (org "Demo Org" + project "Demo Project" for admin@erasmus.test + N participants with country + sending org). Never assume "they'll reseed themselves". The one exception is fresh worktrees where nothing is seeded yet — safe to run silently.
