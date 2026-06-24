---
name: feedback_no_code_comments
description: No explanatory/narrative comments in code — only type-carrying PHPDoc
metadata:
  type: feedback
---

Don't write explanatory comments in code. No "what this does / why" prose above methods.

**Why:** user reaction to a docblock describing a method's behavior — "dont leave your stupid fucking comments". Reinforces CLAUDE.md ("Never use comments within the code itself unless there is something very complex going on").

**How to apply:** Keep only structural PHPDoc that carries type info IDE/static analysis needs — `@return Collection<int, Project>`, `@param array{...}`, facade `@method`. Drop everything else. Self-documenting names over comments. See [[feedback_writing_style]].
