---
name: feedback-shared-deps
description: "Share vendor/node_modules between rasmo workspaces via APFS clone (cp -c), NOT symlinks"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 67199b14-3da4-4438-b19a-b64cff9bc41b
---

`.workspace/new` duplicates `main/vendor` and `main/node_modules` into new workspaces with `cp -c -R` (APFS copy-on-write clone). Each workspace ends up with a real, independent `vendor/` and `node_modules/`.

**Why:** Simon wanted "common composer/npm" originally. Symlinking `.shared/vendor` into each worktree was tried and abandoned: Composer's generated `autoload_static.php` uses `__DIR__ . '/../..' . '/app'`, and PHP resolves `__DIR__` via realpath — so a symlinked `vendor/` resolves outside the workspace and misses `app/Support/helpers.php`. APFS clone gives the "shared feel" (instant creation, ~zero disk cost) without the autoloader breakage. Cost: if a workspace's `composer install`/`npm install` runs, only that workspace's copy mutates.

**How to apply:** Never re-propose symlinked vendor/node_modules for this repo. If Simon adds a package on one workspace, run install in each workspace that needs it — don't try to "propagate" via symlinks or hardlinks. Related: [[project-rasmo-layout]].
