---
name: feedback-never-commit-unasked
description: "Never run git commit (or push) without explicit permission, even when work is finished and green"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 61b11d1d-aec0-490c-9977-08e66f2a3b5a
  modified: 2026-07-21T12:11:30.214Z
---

Do not commit or push. Finish the work, run the tests, and leave everything staged-free in the working tree for the user to review.

**Why:** the user reviews diffs themselves and decides what lands; an unrequested commit takes that away.

**How to apply:** end a task by reporting what changed and what passed; only run `git commit`/`git push` when the user asks for it in that turn.
