---
name: row-actions-gate-by-logic
description: "Filament row actions should apply to the auth user too — never hide by identity. Gate destructive/state-changing actions with domain logic (sole admin, active dependency, etc.) and disable+tooltip instead of hiding."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 1640a466-71aa-483d-87d8-35df6b558f35
---

Row actions in Filament tables must be available for the current auth user too. Don't hide the action just because the row is the auth user.

**Why:** Simon flagged this on AKA-18 users table. Hiding self-actions blocks legit workflows (self-removal, self-demotion). Instead, prevent illegal states via domain rules, not by identity.

**How to apply:**
- Show the action for every row, including the auth user's.
- Gate with `->disabled(fn (Model $r) => $this->wouldBreakInvariant($r))` and `->tooltip(fn (Model $r) => $this->wouldBreakInvariant($r) ? __('...') : null)`.
- Handle the sole-admin invariant, sole-owner invariant, last-active invariant, etc. at the domain layer — not by hiding rows.
- Related: [[feedback_filament_actions_not_raw_html]] on `->visible()` semantics for `toHtml()` — this rule is about record-level actions inside a table, where `disabled` and `visible` behave normally.
