---
name: feedback-extract-filament-pieces
description: "Extract chunky Filament components (Selects, Actions) into their own factory classes under Components/ or Actions/ subfolders."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 1c0e5a17-e251-4040-a65c-c6cc8d422df5
  modified: 2026-07-28T08:17:28.784Z
---

When a Filament Select, Action, form section, or similar UI piece grows past ~30 lines of chained config *or* carries non-trivial closure bodies (search callback, resolve helper, quick-add creation), extract it into its own factory class under a `Components/` or `Actions/` subfolder inside the resource directory.

**Why:** User prefers small, focused files. `ProjectParticipantForm` was ~400 lines with every Select's search/create/render logic inline — user had me pull each Select into `Components/{Participable,Sending,Country}Select.php`, each exposing a static `make()` factory returning the configured `Select`. Same pattern applied to `Actions/{Add,Import,Export}ParticipantAction.php`. The pages/forms now do `AddParticipantAction::make()` and stay under 100 lines.

**How to apply:**

- Namespace: `App\Filament\Project\Resources\{Resource}\Components\...` for form components / selects, `Actions\...` for buttons/modals, `Schemas\...` for form/table schema classes.
- Shape: `public static function make(string $name = '<default>'): Select|Action` returning the fully-configured Filament component.
- Cross-file APIs (like a Select's `resolve(?string $ref): Model|null` decoder used by both the form and the page's action handler) become **public static methods** on the extracted class — that's the only reason to keep them as helpers (2+ call sites across files). Related to [[feedback-inline-single-use-methods]]: extracting *within* the class is still governed by the single-use rule; extraction *to a new class* is a cohesion decision, not a reuse one.
- Don't extract single small Selects that are one `->options()` + one `->required()` chain. Threshold is roughly: has its own search body, quick-add path, custom option rendering, or state marker plumbing.
