---
name: reference-rasmo
description: "Rasmo repo pointers, naming quirks, and Herd site conventions"
metadata: 
  node_type: memory
  type: reference
  originSessionId: 67199b14-3da4-4438-b19a-b64cff9bc41b
---

- Repo: https://github.com/AKABUST-STUDIO/rasmo.git (origin)
- Container path: `/Users/dev/Development/akabust/rasmo/`
- Folder is `rasmo` but the Laravel app's `APP_NAME` is `Rasmo` (was `Erasmus` — user renamed). Related MySQL DB naming stayed `erasmus_<ws>` from the helper; simon may want to rename later.
- Herd sites for this repo: `rasmo-<workspace>.test` (e.g. `rasmo-main.test`, `rasmo-w1.test`). The old `rasmo.test` (from Herd parking `/Users/dev/Development/akabust/`) 404s now — container root has no `public/`.
- Container-level docs: `/Users/dev/Development/akabust/rasmo/README.md`.
- Branch prefix convention: personal feature branches are `simon/<ticket-key>-<slug>` (e.g. `simon/aka-15-organization`, `simon/aka-32-project`). Ticket keys reference an external tracker (likely Linear or JIRA project AKA).
