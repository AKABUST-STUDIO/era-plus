---
name: feedback-always-use-lang-keys
description: "All user-facing strings go through lang keys (__('emails.x.y')), never hardcoded English in PHP or Blade."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 6e26296d-8c74-4ad8-8f30-ccc7c4d75286
  modified: 2026-07-20T15:27:26.490Z
---

Always use lang keys for user-facing strings — subjects, headings, button labels, validation messages, notification bodies. Never hardcode English inline, not even in a "quick" notification or mailable. Add the keys to `resources/lang/en/*.php` following the existing nested array style (`'section' => ['subject' => ..., 'heading' => ...]`) with `:app`, `:code`, `:email` style placeholders.

**Why:** the app is multi-locale and hardcoded strings silently escape translation; the AKA-100 auth branch already accumulated a batch of them that had to be swept up later.

**How to apply:** when writing or editing any class or Blade view that produces text a user reads, reach for `__()` first. If a nearby file hardcodes a string, convert it rather than matching it. Related: [[feedback-no-code-comments]], [[project-auth-refactor-branch]].
