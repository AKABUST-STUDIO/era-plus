# Computed accessors over DB columns for derived state

If a value can be derived from existing columns, use a computed accessor (`getFooAttribute()`), not a migration + column + fillable.

**Incident:** Added `all_day` boolean column + migration + fillable + cast for `ProjectEvent`. User: "motherfucker, all day should be computed, not db column". Rolled back migration, made it a computed accessor over `starts_at`/`ends_at` times.

**How to apply:** Before adding a column, ask: can this be computed at read time from data already stored? Booleans that describe a shape of existing values (all-day, is-past, is-empty, has-attachments) are almost always accessors, not columns. Cost of an accessor read is cheap; a column adds migration weight, sync bugs on writes, and needs form plumbing.