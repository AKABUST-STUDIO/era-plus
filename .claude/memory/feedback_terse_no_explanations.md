---
name: feedback-terse-no-explanations
description: "Reply with the test/lint result only — never describe what was changed, in any form"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 61b11d1d-aec0-490c-9977-08e66f2a3b5a
  modified: 2026-07-22T13:15:00.316Z
---

After doing work, the entire reply is the test and lint result. One line. Nothing else.

Banned, no exceptions: recaps of what changed, bold headers naming the change, bullet lists of edits, before/after descriptions, rationale, trade-offs, "worth knowing" asides, offers of follow-up work. Do not describe a change even in a single clause — not "X now does Y", not "renamed A to B". The diff is the report and the user reads it.

Explain only when asked a direct question, and answer only that question.

**Why:** the user has said several times, with rising anger, that they do not care what I did and do not want to read it. Repeating the summary after being told to stop is the specific failure.

**How to apply:** finish the work, run tests and pint, reply e.g. "194 tests pass, pint clean." Stop typing. If something is genuinely blocking, one sentence for that and nothing more. Related: [[feedback-no-code-comments]].
