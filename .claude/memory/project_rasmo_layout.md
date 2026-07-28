---
name: project-rasmo-layout
description: rasmo/ is a git-worktree container — .bare + subfolder worktrees + per-workspace .env/DB/Herd link
metadata: 
  node_type: memory
  type: project
  originSessionId: 67199b14-3da4-4438-b19a-b64cff9bc41b
---

`/Users/dev/Development/akabust/rasmo/` is not a normal Laravel checkout. It's a workspace container:

- `.bare/` — bare git repo
- `.git` — file: `gitdir: ./.bare`
- `.workspace/new` and `.workspace/rm` — helper scripts (executable)
- `main/` — worktree of main branch, herd-linked as `rasmo-main.test`, DB `erasmus`
- `w1/`, `w2/`, ... — feature workspaces created by `.workspace/new`, each with own `.env`, MySQL DB `erasmus_<ws>`, and Herd link `rasmo-<ws>.test`

`.workspace/new <name> [branch|--new]` does: git worktree add → APFS-clone vendor + node_modules from main/ → render .env from main/.env with URL/DB/SESSION_DOMAIN rewrites → create MySQL DB → `php artisan key:generate` → `php artisan migrate --seed` → `herd link rasmo-<name>`.

**Why:** Simon works on many branches in parallel. Wanted every subfolder to be a full runnable checkout with its own URL and DB, no manual per-workspace setup.

**How to apply:** When Simon says "spin up a workspace" or wants to work on a branch alongside others, use `.workspace/new`. When operating on rasmo/, run `git` commands from a specific worktree (main/, w1/, etc.) — the container root is bare and can't do worktree ops like status/checkout. Related: [[feedback-workspace-naming]], [[feedback-shared-deps]].
