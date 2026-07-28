---
name: feedback_filament_actions_not_raw_html
description: "Clickables are Filament Actions written inline in the Blade view, one @if guard each — no raw <a>/<button>, no collector class"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 7a6be3dc-57de-4e4d-b8ae-c4d516749fc1
---

Never hand-roll `<a>`/`<button>` markup with Tailwind class strings. Write a `Filament\Actions\Action` **inline in the Blade view**, one explicit block per action, each wrapped in its own `@if` guard.

**Why:** two rounds of correction on the auth OAuth buttons. First "use filament actions please" (they were raw `<a>` tags with a copy-pasted class string). Then, when the actions were extracted to an `OAuthProviderActions` collector class that the render hook passed in: "dont do it like this, remove the class, hardcode the three actions in the blade file, have a visible method for each checking itself" — then they rewrote it themselves and said "this is what youve shouldve done, take note".

**The shape they want** (`resources/views/filament/auth/login-container-footer.blade.php` is the calibration):

```blade
@if (filled(config('services.google.client_id')))
    {!! \Filament\Actions\Action::make('oauth_google')
        ->label(__('filament-panels::auth/pages/login.form.actions.oauth.google.label'))
        ->icon('selfhst-google')
        ->url(route('auth.oauth.redirect', ['provider' => 'google']))
        ->color('gray')
        ->outlined()
        ->extraAttributes(['class' => 'w-full'])
        ->toHtml() !!}
@endif
```

Explicit and repeated beats a loop over a config array or a class that builds the list. Don't extract to `app/...` and pass via the `renderHook` closure — they reverted exactly that. This is the one place inline PHP in Blade is wanted, so it narrows [[feedback_no_inline_php_in_blade]] and [[feedback_class_components]]: those still hold for component *logic*, not for composing Filament Actions in a view.

**Gotcha — `->visible()` does not work with `toHtml()`.** A hidden Action still renders: a `fi-disabled` button, label showing, `href` stripped. Filament only honors `isVisible()` when actions go through an `Actions` container/schema. So the condition must be an `@if` around the block (or `->filter(fn ($a) => $a->isVisible())`), never `->visible()` alone.

Other notes: a `url()`-only Action renders standalone, no Livewire component needed (Filament does this itself — `Filament\Auth\Pages\Login::registerAction()` + `$this->registerAction->toHtml()`). On a page class, an action method `fooAction(): Action` resolves via `$this->fooAction`. Labels always via trans keys, package namespace when a package owns the surface — see [[feedback_translate_ui_strings]] and [[feedback_vendor_lang_namespace]].
