---
name: prefer-resource-over-page
description: "When a Filament surface has CRUD (create + update + list), reach for Filament\\Resources\\Resource. Only build a Page implements HasTable when the surface is non-CRUD (settings, dashboards, one-off flows)."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 1640a466-71aa-483d-87d8-35df6b558f35
  modified: 2026-07-24T10:36:18.569Z
---

Default to a Filament **Resource** whenever the feature has create/update pages. Only use a plain `Page implements HasTable` for surfaces that don't have per-record edit — settings pages, dashboards, one-off wizards.

**Why:** Simon flagged this on AKA-36 after I built `Roles` + `EditRole` as sibling `Page` classes. That reimplements what `Filament\Resources\Pages\EditRecord` ships free: `{record}` route binding, `mount(int $record)` + `resolveRecord()`, form fill from record, save+notification+redirect, DeleteAction header, breadcrumbs, `canAccess()`. Ends up with smells like a `getUrl()` shim that passes `record => 0` to survive nav registration.

**How to apply:**
- If the feature has a "create + list + edit per row" shape → **Resource**. Discovery folder is `discoverResources(in: app_path('Filament/{Panel}/Resources'))`, structure is `Resources/Foo/FooResource.php` + `Resources/Foo/Pages/{ListFoos,CreateFoo,EditFoo}.php`.
- Resource gets: automatic `{record}` binding, form/table/infolist schemas cleanly separated, per-page nav registration, built-in create/edit URL helpers, native modal/link actions.
- Plain `Page implements HasTable` stays valid for **Settings**-style surfaces where the table is one panel of a larger single-page (Members, GeneralSettings, Billing) or for dashboards/wizards.
- Related: [[feedback_child_models_namespaced_per_parent]] — the Resource folder mirrors the same nested-by-parent convention.
