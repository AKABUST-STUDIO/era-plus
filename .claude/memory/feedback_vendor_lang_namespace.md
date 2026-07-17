---
name: feedback_vendor_lang_namespace
description: For strings a package owns, override its published vendor lang file and use the package trans namespace — never a parallel app-side file
metadata:
  type: feedback
---

When overriding a Filament page or view, put strings in the package's own published lang file and reference the package namespace — `__('filament-panels::auth/pages/login.form.email.label')`. Never create an app-side lang file (`resources/lang/en/auth.php`) for strings Filament already owns.

**Why:** user rejected a hand-rolled `resources/lang/en/auth.php` — "delete our own auth translation file and modify the vendor login and registration file to be used", "try to use filament-panels::auth/pages/ translation key namespace example". The vendor tree is already published to `resources/lang/vendor/filament-panels/en/`, so a parallel app file duplicates an existing namespace and breaks what the auth Blade views already do.

**How to apply:**
- Check `vendor/filament/filament/resources/lang/en/` for an existing group before inventing keys; published copies live at `resources/lang/vendor/filament-panels/en/<same path>`.
- Reuse the vendor key when the string matches (`login.form.actions.authenticate.label` = 'Sign in'); change the vendor file's *value* when our copy differs; add new sub-keys under the vendor's existing shape for our own flow (`login.code.heading`, `login.form.code.helper_text`, `register.consent.*`).
- Laravel `array_replace_recursive`s the published file over the vendor one (`FileLoader::loadNamespaceOverrides`), so omitted keys fall back — but keep published files as full copies, matching how the tree was published.
- Check the base page before overriding: `Filament\Auth\Pages\Register::getSubheading()` already renders `register.actions.login.before` + `$this->loginAction`, so setting that key beats a custom override.
- App-side files (`forms.php`, `settings.php`, `user.php`, `navigation.php`, `notifications.php`) stay correct for strings no package owns.

Renders via render hook: keys belong to the group of the page the hook fires on (`consent.blade.php` is on `AUTH_REGISTER_FORM_AFTER` → `register.consent.*`). See [[feedback_translate_ui_strings]] and [[feedback_class_components]].