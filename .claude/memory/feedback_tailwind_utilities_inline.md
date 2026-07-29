---
name: feedback_tailwind_utilities_inline
description: "Single-use styling goes as Tailwind utilities on the blade element — do not invent a named class in theme.css for a one-off."
metadata:
  node_type: memory
  type: feedback
---

Nuance to [[feedback_css_in_theme_not_blade]]:

- **Rule that still holds:** never use `<style>` blocks in blade files. All standalone CSS lives in `resources/css/filament/app/theme.css`.
- **New rule this session:** don't invent a named class + `@apply` block in `theme.css` for a *single-use* element when Tailwind utility classes on the blade element would do the same job.

**Why:** the user reacted sharply ("why are you writing manual css when you could use tailwind classes in blade file") when I created `.fi-language-menu-trigger` / `.fi-language-menu-current-flag` in theme.css for a single element in one Livewire view. Adding named classes to a global stylesheet has a real cost — every future reader has to jump to theme.css to see what the class does.

**How to apply:**
- **One element, one use** → Tailwind utilities directly on the blade element (`class="w-full cursor-pointer h-4 w-6 rounded-sm shadow-sm"`).
- **Reused across ≥2 elements OR overriding a Filament `.fi-*` class you don't own** → extract to `theme.css` with a semantic class name (this is the original theme.css rule).
- **Never** an inline `<style>` block or a dynamic named class stitched in blade.

If you're already inside `theme.css` adjusting a Filament default, stay there — don't wrap it in a bespoke component just to move it out.
