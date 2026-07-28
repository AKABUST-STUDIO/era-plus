---
name: feedback-edit-migrations-in-place
description: Schema changes edit the original create_*_table migration in place; no incremental add_x_to_y migrations
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 61b11d1d-aec0-490c-9977-08e66f2a3b5a
  modified: 2026-07-21T12:11:22.461Z
---

When a table needs new or changed columns, edit the original `create_<table>_table` migration rather than generating an `add_..._to_..._table` migration. Delete any incremental migration already created for the change.

**Why:** the app is pre-launch, so the schema is rebuilt with `migrate:fresh`; a single authoritative create migration per table is easier to read than a chain of patches.

**How to apply:** put new columns in their logical position inside the existing `Schema::create()` block, drop legacy columns outright, and tell the user to re-run `php artisan migrate:fresh` on their dev database. Related: [[feedback-enum-namespace-per-model]].
