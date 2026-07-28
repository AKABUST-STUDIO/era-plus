---
name: no-inline-php-in-blade
description: "Never put PHP class/method definitions inside .blade.php files (Volt-style anonymous Livewire components, `<?php new class extends Component { ... } ?>` blocks, etc.). Extract to a real PHP class."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: ab79691b-ccc1-4c91-a5ad-02417b696442
---

Never inline PHP class or component logic inside a Blade file — no Volt-style `<?php new class extends Component {} ?>` blocks at the top of `.blade.php`, no helper functions defined in a template. Templates should consume data, not declare it.

**Why:** explicit pushback — "dont ever do this bullshit, take note, do not inline php code in blade". The user prefers strict separation: PHP logic in dedicated classes (Livewire components in `app/Livewire/...`, view models, Filament pages/widgets), templates only render. Inlined component classes make navigation, IDE support, and testing harder, and they hide behavior in files devs scan as markup.

**How to apply:**
- Livewire components: create an actual class with `php artisan make:livewire …` (or hand-write in `app/Livewire/...`). The Blade file holds only the markup.
- For data a template needs, expose it via the component class's public methods/properties or pass it as `@include` / `<x-component :prop="..." />` arguments — don't open a `<?php ... ?>` tag inside the view to compute it.
- Short `@php($x = ...)` directives for view-local aliasing are still OK; the prohibition is on class/component definitions and non-trivial logic blocks.
- When you see an existing Volt-style template (often used by `livewire:make --inline` or stub generators), refactor it: move the anonymous class into a named class under `app/Livewire/…`, register the component, and reduce the Blade file to markup.
