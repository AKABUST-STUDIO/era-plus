# Memory Index

- [Writing style](feedback_writing_style.md) — terse, dense, opinionated; no scaffolding bullets, no narrated thinking
- Prefer `public const` class constants over repeated magic-string literals (e.g. panel IDs); reference them everywhere.
- [Translate UI strings](feedback_translate_ui_strings.md) — never hardcode user-facing text; use `__()` trans keys, app is multilingual
- [No code comments](feedback_no_code_comments.md) — no explanatory prose in code; only type-carrying PHPDoc
- [Icons](feedback_icons.md) — Lucide not Heroicons; settings sub-pages declare no nav icon
- [Class components](feedback_class_components.md) — build UI from component classes/schemas, not hand-written Blade markup
- [No short variable names](feedback_no_short_variable_names.md) — never `$r`/`$q`/`$e`/`$org`; full descriptive names everywhere, including closure params
- [Filament modal defaults](feedback_filament_modal_defaults.md) — every modal: `Width::Large`, `closeButton(false)`, footer actions `End`; create/edit also `cancelAction(false)`
- [Inline closures](feedback_inline_closures.md) — non-trivial closure bodies stay inline as `function(){}`; don't extract to a helper, don't cram into `fn () =>`
- [Tailwind utilities inline](feedback_tailwind_utilities_inline.md) — single-use styling goes as Tailwind classes on the blade element, not as named classes in `theme.css` (nuance to [[feedback_css_in_theme_not_blade]])
- [No model booted hooks](feedback_no_model_booted_hooks.md) — never generate `uuid`/`slug`/`username` in `booted()`/`creating()`; set them in the method that creates the record
- [Filament action authorize](feedback_filament_action_authorize.md) — gate actions with `->authorize()` (+ `authorizationMessage`/`authorizationTooltip`), never `visible()`/`disabled()` closures or manual `can()` checks inside `action()`
- [Filament built-in actions](feedback_filament_builtin_actions.md) — `DeleteAction`/`DeleteBulkAction`/`EditAction`/`CreateAction` + a model policy; never hand-roll `Action::make('remove')` with its own handler
