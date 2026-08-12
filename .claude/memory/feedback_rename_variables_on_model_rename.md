# Full rename sweep when a model gets renamed

When a model class is renamed (`Activity` → `ProjectEvent`), update ALL callsites: parameter names, local variables, docblocks, method signatures. Don't leave `$activity` variables paired with `ProjectEvent` type hints.

**Incident:** Rename from `Activity` to `ProjectEvent` was done but `EventService.php` still had every method parameter and local named `$activity`. User: "why are the variables stil called activity". Global replace `$activity` → `$event` then had to fix the collisions where `$event` was ALSO used for the raw Google API array (renamed those to `$remote`).

**How to apply:**
- On model rename, `grep -n '\$oldName' path/to/related/files/` and rename per file.
- Watch for variable-name collisions when the new name is already used for something else in the same scope (Google API returned arrays are `$event` too — rename them to `$remote`, `$payload`, etc.).
- Type-hinted parameters + variable names should match: `ProjectEvent $event`, not `ProjectEvent $activity`.