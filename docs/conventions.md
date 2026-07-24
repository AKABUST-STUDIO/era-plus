# Project conventions

Working notes on how we build features here. Each rule has a *why* — read it before overriding.

## Filament: Resource vs Page

- **CRUD surface (list + create + edit)** → build a `Filament\Resources\Resource`. Folder structure `Resources/Foo/FooResource.php` + `Resources/Foo/Pages/{ListFoos,CreateFoo,EditFoo}.php`. You get `{record}` route binding, `resolveRecord()`, form/table separation, auto save + notification + redirect, `DeleteAction` header, breadcrumbs, and `canAccess()` for free.
- **Non-CRUD surface** (settings pages, dashboards, one-off wizards) → `Page implements HasTable` with a hand-rolled table. Members / GeneralSettings / Billing all follow this because the table is one panel of a larger page.
- If you catch yourself writing `getUrl(['record' => 0])` shims or a custom `mount(int $record)`, you've built a Page where a Resource belongs — flip it.

## Filament tables

- **Sort dropdown:** use `->defaultSort($column, $dir)` + `->defaultSortOptionLabel(__('...'))`. Do not build a custom `<select>` in the Blade view.
- **Filters:** v5 defaults to *deferred* filters with an Apply button. If we want auto-apply, call `->deferFilters(false)`.
- **Row actions:** always show for every row, including the auth user's own row. Gate destructive/state-changing actions with `->disabled(fn ($r) => $breaksInvariant)` + `->tooltip(fn ($r) => $breaksInvariant ? __('...') : null)`. Never hide the action by identity (`->visible(fn ($r) => ! $r->is(auth()->user()))`).
- **Bulk-select:** mirror the row-action gate with `->checkIfRecordIsSelectableUsing(...)` so invariant-breaking rows can't be selected in bulk either.
- **Empty state:** every table gets `->emptyStateIcon()`, `->emptyStateHeading()`, `->emptyStateDescription()`. When the table has tabs, the copy branches on the active tab.
- **Synthetic columns:** `TextColumn::make('anything')` is a state-lookup path. If the column has no matching attribute, use `->getStateUsing()` and return `null` to hide, not `->visible()` or a static `->state()`.

## Pivots & Livewire

- **Livewire drops pivot data on rehydration**, so `$record->pivot->foo` in `getStateUsing` closures returns null. Fix: give the relation a **custom Pivot model** with `->using(CustomPivot::class)->as('member')` on both sides, then access `$record->member->foo`. Add `withPivot('...')` for every column the pivot needs to hydrate. Do not paper over this with in-memory maps or caches.
- **Custom Pivot models** live under the owner's namespace (e.g. `App\Models\Organization\OrganizationMember`), set `$table` explicitly, and can declare their own relations (`role()`) that Filament columns eager-load through.

## Auth & invites

- **The app is OTP-only.** Users sign in with an email one-time password. Do not generate `Password::broker(...)->createToken(...)` reset URLs, and do not send "set your password" links. Invites deep-link to the login page with `?email=` prefilled.
- **New users on invite:** use `User::query()->firstOrCreate(['email' => ...], ['name' => EmailUsername::toDisplayName($email), 'password' => Str::random(64)])`. The random password is inert — the DB column is NOT NULL but no login path consumes it.

## Policies & services

- **Domain rules live in services**, e.g. `OrganizationService::isSoleAdmin($org, $user)`. Policies delegate to them, so the same rule is one function everywhere.
- **Policies orchestrate**: e.g. `OrganizationPolicy::changeMemberRole($user, $org, $target)` combines `ProjectAccess::administersOrganization($user, $org)` with the domain guard.
- **Policy attachment:** `#[UsePolicy(OrganizationPolicy::class)]` attribute on the model, no `AuthServiceProvider::$policies` array.
- **Gate calls in Livewire actions:** `auth()->user()->can(...)` at every callsite. Do not wrap in helpers. Do not `abort(403)` from a Livewire action — the user already loaded the page. Send a `Notification::make()->danger()` and return.

## Naming

- **User-facing label and PHP class name stay aligned.** If the page shows "Members" in the sidebar, the class is `Members`, the slug is `members`, the view is `members.blade.php`. Rename in one commit with `git mv` so the history stays clean.

## String helpers

Before hand-rolling a string transform, grep `app/Support/`. Current helpers:

- `EmailUsername::toDisplayName($email)` — turns `first.last@x.com` into `First Last` for seed display names on invite.

## Small process

- **Verify UI changes in the browser.** Type-checkers and PHPUnit prove correctness, not visual fitness. For every page-level change, screenshot with Playwright before declaring done.
- **When a rough edge shows up in multiple places, open an umbrella Linear ticket** first (e.g. AKA-151 "UI") and file the specific work as a child.
