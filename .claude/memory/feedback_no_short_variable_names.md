---
name: feedback_no_short_variable_names
description: never use short/single-letter variable names like $r, $q, $e; always full descriptive names
metadata:
  type: feedback
---

Never use short or single-letter variable names — `$r`, `$q`, `$e`, `$org`, etc. Use the full descriptive name (`$organization`, `$query`, `$exception`). Applies everywhere, including closure params in Filament/Eloquent callbacks (`fn (Builder $query) => ...`, not `fn (Builder $q)`).

**Why:** direct instruction — "dont ever fucking use short variable names like this". Ties to existing convention [[feedback_writing_style]] on descriptive naming in CLAUDE.md (`isRegisteredForDiscounts`, not `discount()`).

**How to apply:** when writing or editing, name every variable/closure param in full. When told about one instance, sweep the codebase for the same pattern and fix all of them, not just the flagged line.
