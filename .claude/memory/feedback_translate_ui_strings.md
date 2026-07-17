---
name: feedback_translate_ui_strings
description: All user-facing UI strings must use trans keys, never hardcoded literals
metadata:
  type: feedback
---

Never hardcode user-facing strings in Blade/views/components/PHP. Always use `__('...')` trans keys. Write them translated the first time — do not defer.

**Why:** corrected repeatedly, escalating each time — "always account for translations" (a hardcoded `tooltip="Open project"`), then "use translations please, remember that", then "i told you to use translation keys everywhere moron" after newly-written auth pages shipped hardcoded English. App is multilingual.

**How to apply:** lang_path is `resources/lang` (not root `lang/`); default/fallback locale `en`. Reference in Blade as `:prop="__('menu.x.y')"`. Add the key to every locale when introducing a string.

Covers every user-facing string, not just labels: headings, section descriptions, helper text, placeholders, select options, notification titles, action labels, validation messages, and the first arg of `Section::make()` / `Step::make()` / `Stat::make()`.

- Per-domain app files: `forms.php`, `settings.php`, `user.php`, `organization.php`, `navigation.php`, `menu.php`, `sidebar.php`; `notifications.php` is flat and holds every `Notification::make()->title()`. For strings a package owns, use its namespace instead — see [[feedback_vendor_lang_namespace]].
- Check the lang file first — keys often already exist and the code just failed to wire them.
- Static props can't hold `__()`; override `getNavigationLabel()` / `getTitle()` instead.
- Placeholders over concatenation: `__('...code.helper_text', ['email' => $email])`. Pluralization via `trans_choice`.
- Not strings to translate: language endonyms (English/Eesti/Deutsch), currency symbols (`forms.common.currency_prefix`), `'—'` placeholders, `->label('')`, and `ActivityLog::record()` descriptions (persisted rows — translating at write time bakes in the writer's locale).
