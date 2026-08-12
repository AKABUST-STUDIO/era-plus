# Check framework built-ins before hand-rolling

Before writing custom widget state, custom Blade markup, or custom lifecycle plumbing, grep the framework source for an existing API. Filament, Livewire, and Saade FullCalendar almost always ship the primitive you're about to reinvent.

**Incidents in one session:**
- Hand-rolled `$lastCreateData` widget property + `mountUsing` guard to preserve form data across "Create & create another". Filament ships `->preserveFormDataWhenCreatingAnother(fn (array $data) => $data)`. Removed the widget property.
- Rendered attendee list with raw `<span class="badge">` + Tailwind color match statement. User: "use filament avatar and filament badge component". Swapped to `<x-filament::avatar>` and `<x-filament::badge :color="$status->getColor()">`.
- Built calendar avatar chips with `<img>` tags directly. User told me to use Filament avatar there too. Solved by rendering `<x-filament::avatar>` server-side in `fetchEvents` → HTML string → passed in `extendedProps` (see [[feedback_saade_fullcalendar_quirks]] for the server-side-render-then-inject pattern).

**How to apply:** Before writing custom logic against Filament/Livewire/Saade, `grep -rn` the vendor source for the noun. Look at the class you're extending's `setUp()`/`configure()` for existing methods. Especially for: form data preservation, modal actions, badge/avatar rendering, action authorization, event hooks.