---
name: feedback-model-namespace-per-parent
description: Child models live in App\Models\<Parent>\ and drop the parent prefix from the class name (ProjectSector became App\Models\Project\ErasmusSector)
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 61b11d1d-aec0-490c-9977-08e66f2a3b5a
  modified: 2026-07-22T12:00:45.869Z
---

Models that belong to a parent model go in a sub-namespace named after that parent, and the class name drops the redundant parent prefix: `App\Models\ProjectSector` became `App\Models\Project\ErasmusSector`. Domain naming wins over Laravel's table-derived naming — set `protected $table` explicitly when the class name no longer matches the table (`project_sectors`).

**Why:** mirrors the enum convention in [[feedback-enum-namespace-per-model]]; `Project\ErasmusSector` reads better than `ProjectSector` and matches the Erasmus domain vocabulary.

**How to apply:** when a model exists only as a child of another, put it under `app/Models/<Parent>/`. If its name collides with an enum of the same name, alias the enum import (`use App\Enums\Project\ErasmusSector as ErasmusSectorEnum;`) rather than renaming either.
