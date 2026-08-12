# Add helper methods on morph parents that delegate to children

When several polymorphic morph targets share a method with the same shape (e.g. `avatarUrl()` on both `User` and `Participant`), put a helper on the pivot/parent model that resolves through the morph.

**Incident:** Widget code was reaching into `$attendee->projectParticipant->participable->avatarUrl()` conditionally checking `method_exists($participable, 'avatarUrl')`. User: "set avatar url on project participant model which gets avatar url depending on the users morph model". Added `ProjectParticipant::avatarUrl()` that returns `$this->participable->avatarUrl()` (with UI-Avatars fallback). Now call sites are `$attendee->projectParticipant->avatarUrl()`.

**How to apply:** Any time a caller does `->participable->methodOnBothTargets()`, promote it to a method on the pivot/parent that delegates. Removes repeated null-safe navigation and `method_exists` guards. Same pattern works for `email()`, `name()`, `displayLabel()` when all morph targets support it.