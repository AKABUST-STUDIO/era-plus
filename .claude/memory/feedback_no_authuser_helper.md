---
name: no-authuser-helper
description: "Never introduce a `$this->authUser()` helper or similar wrapper around auth() — always call `auth()->user()` directly at the callsite."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 1640a466-71aa-483d-87d8-35df6b558f35
---

Never wrap `auth()->user()` behind a `authUser()`/`user()`/`currentUser()` helper method. Call `auth()->user()` directly at every callsite.

**Why:** The wrapper adds an extra hop, hides which auth guard is in play, and can silently return a stale object if cached. Simon flagged it explicitly on AKA-18. Consistent with [[feedback_no_defensive_wrappers]].

**How to apply:** In Livewire/Filament pages, actions, closures — write `auth()->user()`. Do not extract to `private function authUser(): User`. If PHPStan complains about nullable, narrow at the site with `$user = auth()->user(); assert($user);` — do not hide behind a helper.
