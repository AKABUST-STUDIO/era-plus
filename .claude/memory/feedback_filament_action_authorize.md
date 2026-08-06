---
name: feedback-filament-action-authorize
description: "Gate Filament actions with ->authorize(), never with visible()/disabled() closures or manual can()/cannot() checks inside action()"
metadata:
  node_type: memory
  type: feedback
  originSessionId: 3cead5a5-6613-4e3a-93b5-8a2c8f04a428
  modified: 2026-08-06T00:00:00.000Z
---

Every Filament action that needs a permission check uses `->authorize()`. Do not gate with `->visible(fn () => auth()->user()->can(...))`, do not pair `->disabled()` with `->tooltip()`, and do not re-check with `can()`/`cannot()` + a danger `Notification` inside the `action()` body.

**Why:** one mechanism instead of three, and it is not just cosmetic — `mountAction()` returns early when `isDisabled()`, and `isDisabled()` is `! isAuthorized()` once `authorize()` is set, so an unauthorized action cannot be mounted or executed even via a crafted Livewire request. The manual in-body check is dead weight.

**How to apply:**
- Ability resolved from a policy on the action's own model: `->authorize('update')`.
- Ability on another model or with extra arguments (e.g. `inviteMember`/`removeMember` on `OrganizationPolicy` taking `[$organization, $user]`): pass a closure — `->authorize(fn (OrganizationUser $record): bool => auth()->user()->can('removeMember', [$organization, $record->user]))`.
- To keep the button visible-but-disabled with an explanation, add `->authorizationMessage(__('...'))` then `->authorizationTooltip()`. The message fallback is required when the policy returns plain `false`, otherwise Filament hides the action instead of disabling it. `->authorizationNotification()` shows the message as a notification instead.
- Bulk actions over a policy method on the record's own model: `->authorizeIndividualRecords('delete')`. When the ability lives elsewhere or takes extra arguments, filter with `can()` inside the `action()` closure — `authorizeIndividualRecords()` only accepts a policy-method name.
- Deleting the manual guard also deletes its lang key; drop keys such as `notifications.cannot_invite` when nothing else uses them.

Related: [[feedback_no_defensive_wrappers]], [[feedback_row_actions_gate_by_logic]].
