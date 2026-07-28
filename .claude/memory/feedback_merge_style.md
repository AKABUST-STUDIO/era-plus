---
name: feedback-merge-style
description: "Simon prefers linear ff-merge for feature branches, keeps branches after merging, pushes immediately"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 67199b14-3da4-4438-b19a-b64cff9bc41b
---

When merging feature branches into `main` on rasmo:
- **Style**: linear ff-merge. First branch ff's cleanly; subsequent branches get rebased onto new main, then ff-merged. No merge commits, no squash.
- **Cleanup**: keep the branches (both local and remote). Don't propose auto-delete after merge.
- **Push**: push `main` to origin immediately after all merges complete.

**Why:** Solo dev repo, linear history is easier to read in Tower. Kept branches serve as reference points / lightweight tags for what a feature contained.

**How to apply:** For any multi-branch merge into main here, use ff-merge + rebase pattern:
```
git merge --ff-only <branch1>
git checkout <branch2> && git rebase main && git checkout main
git merge --ff-only <branch2>
git push origin main
```
Warn about conflict-prone files before rebasing (`git diff --name-only main..branchA` ∩ `git diff --name-only main..branchB`). Don't switch branches inside a worktree that has dirty conflicting files — use a temporary `git worktree add /tmp/...` for the rebase instead.
