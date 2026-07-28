---
name: prefer-geturl-over-route
description: "For Filament page/resource URLs, call `SomePage::getUrl(panel: ..., tenant: ...)` instead of `route('filament.x.pages.y')`. Class refs survive renames; route-name strings don't."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: ab79691b-ccc1-4c91-a5ad-02417b696442
---

For Filament page/resource URLs, always call `SomePage::getUrl()` (or `SomeResource::getUrl()`) from the class itself instead of `route('filament.{panel}.pages.{slug}')`.

**Why:** explicit pushback — "do not use route helper, prefer getUrl from class, take note". `route()` with a Filament route name is a magic string: it silently breaks when a page is renamed, its slug changes, its panel is renamed, or when the slug auto-derivation rules shift (e.g. registering vs not registering Dashboard::class flips between `filament.x.pages.dashboard` and `filament.x.home`). `Page::getUrl()` is a typed reference: rename refactors update it, IDE jump-to-source works, and the panel argument is explicit.

**How to apply:**
- Same panel: `Activity::getUrl()`.
- Different panel: `Activity::getUrl(panel: UserPanelProvider::PANEL_ID)`.
- Tenant-scoped panel: `Billing::getUrl(tenant: $organization, panel: SettingsPanelProvider::PANEL_ID)`.
- The exception: Filament's auto-generated routes that have no backing class — login (`filament.x.auth.login`), logout (`filament.x.auth.logout`), home redirect (`filament.x.home`). Use the panel's own helpers — `$panel->getLoginUrl()`, `$panel->getLogoutUrl()`, `$panel->getUrl()`.
- Skip raw `route()` calls in views, Livewire components, mailables, notifications — wherever a Filament target is intended.
