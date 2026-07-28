---
name: feedback-full-variable-names
description: "Never use short throwaway variable names like $q, $v, $r in closures — spell them out ($query, $value, $record)."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 1c0e5a17-e251-4040-a65c-c6cc8d422df5
  modified: 2026-07-28T08:16:59.311Z
---

Never use short throwaway names in closures or block scopes — `$q`, `$v`, `$r`, `$item`, `$el`. Spell them out: `$query`, `$value`, `$record`, `$participant`, `$organization`.

**Why:** Reads better in the diff, matches how the surrounding real-Laravel/Filament APIs name their own callback params (`Builder $query`, `Model $record`), and avoids the "what did I abbreviate again" tax when you come back to a file weeks later. User has enforced this multiple times, most recently on a `whereHas(fn (Builder $q) => ...)` inside a Filament Select.

**How to apply:** In every callback signature (`fn (Builder $query) => ...`, `->each(function (Participant $participant) ...)`, arrow-fn args, etc.). Applies even in tight one-liners. Related to [[feedback-inline-single-use-methods]] — inlining doesn't excuse cryptic locals.
