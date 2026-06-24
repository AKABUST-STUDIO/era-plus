---
name: feedback_translate_ui_strings
description: All user-facing UI strings must use trans keys, never hardcoded literals
metadata:
  type: feedback
---

Never hardcode user-facing strings in Blade/views/components. Always use `__('...')` trans keys.

**Why:** user flagged a hardcoded `tooltip="Open project"` — "always account for translations". App is multilingual.

**How to apply:** lang_path is `resources/lang` (not root `lang/`); default/fallback locale `en`. Menu strings live in `resources/lang/en/menu.php`. Reference in Blade as `:prop="__('menu.x.y')"`. Add the key to every locale when introducing a string.
