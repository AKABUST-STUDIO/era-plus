---
name: feedback-inline-single-use-methods
description: A helper called from exactly one place gets inlined at the call site; only extract once there are two or more callers
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 61b11d1d-aec0-490c-9977-08e66f2a3b5a
  modified: 2026-07-21T12:43:52.787Z
---

Don't extract a method (or closure-returning helper) that has a single caller — write the logic inline where it is used, even if it is several lines. Extract only when a second call site appears.

**Why:** a one-use helper adds a name and a jump for no reuse; the logic reads better next to the thing it configures.

**How to apply:** before adding a `protected static function foo()`, count the call sites. One → inline it in the closure/argument. Two or more → extract. Applies to Filament schema helpers as much as to model/service code. Related: [[feedback-no-defensive-wrappers]].
