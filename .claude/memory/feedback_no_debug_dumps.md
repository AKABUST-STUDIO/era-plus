---
name: feedback-no-debug-dumps
description: "Never leave dd(), dump(), info(), ray() calls in code — not in closures, not \"just to check\", nowhere."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 1c0e5a17-e251-4040-a65c-c6cc8d422df5
  modified: 2026-07-28T08:17:06.968Z
---

Never leave `dd()`, `dump()`, `info()`, `ray()`, or any other debug/log-inspection call in code — not in a `->state()`/`->visible()`/`->afterStateUpdated()` closure, not in a Filament schema, not "just for a second to check something".

**Why:** These leak into rendered pages spectacularly. A stray `dump($record)` in a Filament table's `->state()` closure sprayed a var_dumper block into every card on the participants list; user was rightly furious. Debug tools also swallow returns (`info()` returns void → truthy checks lie), so they can silently break unrelated logic.

**How to apply:** If you need to inspect state during development, do it in tinker/PHPUnit/an actual xdebug session — never touching the render path. When reviewing your own edits, grep for `dd(`/`dump(`/`info(` before reporting done. Related to [[feedback-no-defensive-wrappers]] — same "don't leak scaffolding into production" spirit.
