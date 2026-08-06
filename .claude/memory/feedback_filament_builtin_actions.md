---
name: feedback-filament-builtin-actions
description: "Use Filament's built-in CreateAction/EditAction/DeleteAction/DeleteBulkAction instead of hand-rolling Action::make('remove') with a custom handler"
metadata:
  node_type: memory
  type: feedback
  originSessionId: 3cead5a5-6613-4e3a-93b5-8a2c8f04a428
  modified: 2026-08-06T00:00:00.000Z
---

Never hand-roll an action that Filament ships. A row action that deletes the record is `DeleteAction::make()`, not `Action::make('remove')->requiresConfirmation()->action(fn ($record) => ...)`. Same for create/edit/bulk-delete. Configure the built-in (`label()`, `icon()`, modal options) instead of rebuilding it.

**Why:** the built-ins already carry the confirmation modal, success notification, record resolution, redirect handling and — inside a resource — automatic policy authorization. A custom clone re-implements all of it and drifts.

**How to apply:**
- Give the model a policy so the built-ins authorize themselves: `DeleteAction` → `delete()`, `DeleteBulkAction` → `deleteAny()` + `authorizeIndividualRecords('delete')`, `EditAction` → `update()`. Follow the shape of `RolePolicy`: full action set (`viewAny`, `view`, `update`, `updateAny`, `delete`, `deleteAny`) resolving granular `{action}_{resource}` permissions from [[feedback_no_defensive_wrappers]]-free helpers, and register the resource in `PermissionRegistry::resources()` so the roles page lists the new permissions.
- Domain side effects hang off `->after()` (e.g. `ActivityLog::record(...)` after the delete), not off a replacement `action()`.
- The action name changes: tests target `TestAction::make('delete')`, not the old custom name.
- Only write a bare `Action::make()` for genuinely custom operations (e.g. `changeRole`), and gate those with [[feedback_filament_action_authorize]].