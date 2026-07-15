# Memory Index

- [Writing style](feedback_writing_style.md) — terse, dense, opinionated; no scaffolding bullets, no narrated thinking
- Prefer `public const` class constants over repeated magic-string literals (e.g. panel IDs); reference them everywhere.
- [Translate UI strings](feedback_translate_ui_strings.md) — never hardcode user-facing text; use `__()` trans keys, app is multilingual
- [No code comments](feedback_no_code_comments.md) — no explanatory prose in code; only type-carrying PHPDoc
- [Icons](feedback_icons.md) — Lucide not Heroicons; settings sub-pages declare no nav icon
- [Class components](feedback_class_components.md) — build UI from component classes/schemas, not hand-written Blade markup
- [No short variable names](feedback_no_short_variable_names.md) — never `$r`/`$q`/`$e`/`$org`; full descriptive names everywhere, including closure params
