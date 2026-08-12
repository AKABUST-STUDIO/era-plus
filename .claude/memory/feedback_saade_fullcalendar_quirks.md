# Saade FullCalendar gotchas

`saade/filament-fullcalendar` has two non-obvious behaviors that cause hours of debugging if you don't know them:

**1. `ViewAction::setUp()` overrides `modalFooterActions()`** to `[...getCachedFormActions(), $action->getModalCancelAction()]`. So calling `->modalCancelAction(false)` on a Saade ViewAction crashes with `Call to a member function getName() on null` at `CanOpenModal.php:379` — `getModalCancelAction()` returns null and it's appended to the footer array anyway. To fully control the ViewAction footer, override `->modalFooterActions()` yourself with the actions you want.

**2. `eventContent()` returns a JS function string that runs client-side.** Blade is not available in that context — you can't put `<x-filament::avatar>` inside the JS string. Pattern that works: render the Blade partial server-side inside `fetchEvents()`, pass the resulting HTML string in `extendedProps` (e.g. `'avatarHtml' => view(...)->render()`), and have the JS just concatenate `a.avatarHtml` into the event template.

Related: [[project_google_calendar]], [[feedback_prefer_framework_builtins]].