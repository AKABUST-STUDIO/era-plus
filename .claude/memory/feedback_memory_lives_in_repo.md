---
name: feedback-memory-lives-in-repo
description: Write memories to the repo's .claude/memory/ (tracked in git), not Claude's private ~/.claude/projects/*/memory/
metadata:
  type: feedback
---

Memory files live in the project at `<workspace>/.claude/memory/` (tracked in git). Do not write memories to Claude Code's private `~/.claude/projects/<slug>/memory/`. On rasmo, all six private slug directories are symlinked to `main/.claude/memory/`, so either path resolves to the same tracked location — but always reason about the repo path.

**Why:** Memories are durable, versioned, and shared across machines/worktrees only when they're in the repo. Private-dir memories are invisible when the project folder is renamed (this happened when erasmus/ → rasmo/ orphaned the entire memory set), and they can't be reviewed via diff/PR.

**How to apply:** When creating or updating a memory in this repo, write to `.claude/memory/<slug>.md` in the current worktree and update `.claude/memory/MEMORY.md`. Never create files under `~/.claude/projects/*/memory/`. If you notice a private-dir memory exists, migrate it to the repo. Related: [[user-profile]].
