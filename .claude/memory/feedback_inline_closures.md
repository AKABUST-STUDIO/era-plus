---
name: feedback_inline_closures
description: "For non-trivial closure bodies, use a full function(){} closure inline — not extracted to a helper method, not crammed into an arrow-fn."
metadata:
  node_type: memory
  type: feedback
---

When configuring Filament actions / forms / tables (or any similar builder chain) and a closure body needs multiple statements or would be hard to read as a one-liner:

- **Don't** extract the body into a separate static/private helper method (e.g. `self::modalHeading($record)`).
- **Don't** cram it into a single-expression arrow function `fn () => new HtmlString(... Blade::render(...) ...)`.
- **Do** use a full multi-statement closure inline: `function (Model $record): Htmlable { $x = ...; return new HtmlString(...); }`.

**Why:** the user pushed back twice in quick succession — first rejecting a helper extraction ("do not fuckkkking use a separate method"), then rejecting the arrow-fn one-liner I tried next ("use full fuction nnnnnot inline"). The wanted middle ground is a proper multi-statement closure kept where it's used.

**How to apply:**
- One clean expression → `fn () => ...` is fine.
- More than ~2 chained calls or intermediate variables needed → promote to a full `function (...): Type { ... }` closure inline in the config chain.
- Only extract to a helper if there's genuine reuse (2+ call sites).

Relates to [[feedback_extract_filament_pieces]] — extract Tables/Schemas/Actions into class-level files (that's structural), but individual closure bodies stay inline (this rule).
