---
name: feedback_class_components
description: Prefer class-based components over hand-written Blade markup
metadata:
  type: feedback
---

Prefer class components over Blade components. Build UI by composing component classes in PHP, not by hand-writing Blade view markup.

**Why:** user directive — "always prefer to write class components instead of using blade components".

**How to apply:** On Filament pages, define the content as a schema in the PHP class (`Section`, `Toggle`, `Text`, etc. from `Filament\Schemas\Components` / `Filament\Forms\Components`) and render it with `{{ $this->form }}` / `{{ $this->schema }}`, instead of writing HTML or `<x-...>` markup in the `.blade.php`. For standalone reusable UI, write a class-based Blade component (`app/View/Components` + class) rather than an anonymous Blade-file component. See [[feedback_icons]] and [[feedback_translate_ui_strings]].
