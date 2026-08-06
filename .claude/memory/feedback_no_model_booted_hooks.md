---
name: feedback-no-model-booted-hooks
description: "Never generate attributes in a model booted()/creating hook; set them in the code path that creates the record"
metadata:
  node_type: memory
  type: feedback
  originSessionId: 3cead5a5-6613-4e3a-93b5-8a2c8f04a428
  modified: 2026-08-06T00:00:00.000Z
---

Do not add `protected static function booted()` with `static::creating()`/`saving()` hooks to models to fill in generated attributes (`uuid`, `slug`, `username`, …). Generate those values in the method that creates the record — `AuthenticationService::register()`, a page's `invite()` action, a controller — and pass them into `create()`/`firstOrCreate()` explicitly.

**Why:** hidden model events make creation implicit and hard to trace; the create path should show exactly what it writes.

**How to apply:** when a NOT NULL generated column blocks a `create()`, fix the calling method, not the model. Follow the existing shape: `$uuid = (string) Str::uuid();` then `$slug = (Str::slug($name) ?: 'user').'-'.Str::substr($uuid, 0, 8);` and pass `uuid`/`slug`/`username` in the attribute array. See [[feedback_no_defensive_wrappers]].