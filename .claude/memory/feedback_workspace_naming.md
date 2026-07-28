---
name: feedback-workspace-naming
description: "Rasmo workspaces are named generically (w1, w2, w3) — never after the feature/branch"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 67199b14-3da4-4438-b19a-b64cff9bc41b
---

Workspace folders under `rasmo/` are named `w1`, `w2`, `w3`, ... Never named after the feature or branch (no `erasmus-aka-15-organization/`, no `feature-participants/`).

**Why:** Rejected earlier ad-hoc worktree scheme (`erasmus-aka-15-organization/`, `erasmus-aka-32-project/`) where folder names encoded the branch. Simon wanted "workspace-1 or some shit like that". Generic slot names mean folders are interchangeable and don't rot when branches merge/rename. Herd URLs still get context via `rasmo-<ws>.test` prefix.

**How to apply:** When creating a new workspace with `.workspace/new`, use `w<N>` (next free number). Never propose branch-based folder names. Do propose descriptive branch names as the 2nd arg (`./.workspace/new w1 feature/foo`). Related: [[project-rasmo-layout]].
