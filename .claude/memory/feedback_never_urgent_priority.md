---
name: feedback-never-urgent-priority
description: Never set Urgent priority on Linear issues; High is the ceiling.
metadata: 
  node_type: memory
  type: feedback
  originSessionId: b3ac3138-d776-416f-a7d9-306cc8a4df84
  modified: 2026-07-23T07:50:30.586Z
---

Never use the Urgent priority (1) when creating or updating Linear issues. Use High (2) as the top level, Medium (3), or Low (4).

**Why:** Nothing in this backlog is genuinely urgent — marking things Urgent flattens the signal.

**How to apply:** When a ticket feels like a blocker or a prerequisite, express that with a blockedBy relation and High priority, not with Urgent. See [[project-auth-refactor-branch]] for the AKA ticket context.
