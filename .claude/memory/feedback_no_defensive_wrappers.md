---
name: no-defensive-wrappers
description: "Don't try/catch framework calls (route(), Filament::*, $tenant->something, model accessors) that are guaranteed to resolve in normal request context. Call them directly."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: ab79691b-ccc1-4c91-a5ad-02417b696442
---

Don't wrap framework calls in try/catch or `safeXxx()` helpers when they're guaranteed to resolve at runtime — `route('filament.x.pages.y')`, `filament()->getCurrentOrDefaultPanel()`, `$tenant->organization`, model accessors. Call them directly.

**Why:** explicit pushback — "dont wrap in safe method, these should always be available". Defensive wrappers hide real bugs (missing routes, broken panel config) behind silent `null`s, add noise, and signal uncertainty about behavior that's actually deterministic. If a Filament route name or panel can be absent in the live app, that's a config/install bug, not a runtime branch to handle.

**How to apply:**
- Call `route('filament.user.pages.dashboard')` straight, not via `try { route(...) } catch { return null }`.
- Call `filament()->getCurrentOrDefaultPanel()?->getLogoutUrl()` straight.
- Test environments that lack a panel are the test's problem, not the production component's. Add the panel to test setup or test via `Livewire::test()` which boots Filament.
- The exception is genuine boundary code: external APIs (Stripe), filesystem I/O, parsing user input. Internal Laravel/Filament wiring is not a boundary.
- Reach for `??` / null-safe operator (`?->`) for nullable returns; don't add `safeXxx()` wrappers around already-nullable APIs.
