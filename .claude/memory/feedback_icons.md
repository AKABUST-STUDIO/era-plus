---
name: feedback_icons
description: Icon conventions — Lucide not Heroicons, and no icons on settings sub-pages
metadata:
  type: feedback
---

Use Lucide icons, never Heroicons. Settings sub-pages must not declare a navigation icon.

**Why:** user directives — "use lucide icons instead of heroicons" and "sub pages shouldnt use icons".

**How to apply:** `mallardduck/blade-lucide-icons` is installed. Reference as the string `'lucide-<name>'` in Filament (`->icon('lucide-settings')`, `NavigationItem::make()->icon('lucide-...')`) or `<x-filament::icon icon="lucide-..." />` / `<x-lucide-... />` in Blade — not the `Heroicon` enum. Settings sub-page classes (`app/Filament/Organization/Settings/Pages/*`) omit `$navigationIcon` entirely. The top-level "Settings" entry still has an icon (Lucide). See [[feedback_translate_ui_strings]] for the parallel labels rule.
