---
name: feedback-enum-namespace-per-model
description: "Enums live in a per-model sub-namespace, e.g. App\\Enums\\Project\\ErasmusSector, never flat in App\\Enums"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 61b11d1d-aec0-490c-9977-08e66f2a3b5a
  modified: 2026-07-21T12:11:13.024Z
---

Every enum belongs in a sub-namespace named after the model it describes: `App\Enums\Project\*`, `App\Enums\FinanceEntry\*`, `App\Enums\Organization\*`, `App\Enums\ProjectTask\*`, `App\Enums\Subscription\*`, `App\Enums\SupportTicket\*`. Class names keep their existing model prefix (`ProjectRole`, not `Project\Role`) so they don't collide with model imports such as `App\Models\Role`.

**Why:** flat `App\Enums\` gets unnavigable as the domain grows; grouping by model makes ownership obvious.

**How to apply:** when adding an enum, create/place it under `app/Enums/<Model>/`. When touching a flat one, move it and rewrite references across `app/ tests/ database/ config/ resources/ routes/` in the same change.
