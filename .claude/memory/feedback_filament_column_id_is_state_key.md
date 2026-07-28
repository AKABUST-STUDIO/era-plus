---
name: filament-column-id-is-state-key
description: "In Filament v5 tables the `make('id')` value IS the state lookup path on the record — badges/values silently disappear when the ID doesn't resolve, even if `->state()` is set."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 1640a466-71aa-483d-87d8-35df6b558f35
---

In a Filament v5 table column, the string passed to `Column::make('...')` is the attribute/relation path Filament uses to resolve state from the record. If that path doesn't exist on the model, the column can render blank even when you set `->state(...)` explicitly — the ID acts as a display gate, not just a key.

**Why:** Simon lost time on the AKA-18 users table because YOU/role/2FA badges didn't render — the columns had `make('you_badge')`, `make('pivot.role_id')` with `->state()` overrides, but Filament still hid them. Root cause was the column ID.

**How to apply:**
- Make the column ID a real attribute path on the record whenever possible (`make('name')`, `make('pivot.role_id')`, `make('two_factor_confirmed_at')`).
- If the column is synthetic (badge indicator, computed label, "YOU" chip), use `getStateUsing(fn ($record) => ...)` returning `null` to hide the cell for that row, non-null to show it — don't rely on `->visible()` closures inside Stack/Split layouts, and don't set static `->state('label')` expecting it to show.
- Verify in the browser (Playwright / manual) whenever changing column IDs — tests don't always catch missing badges.
