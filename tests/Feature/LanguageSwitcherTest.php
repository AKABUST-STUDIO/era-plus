<?php

use App\Http\Middleware\SetUserLocale;
use App\Livewire\LanguageSwitcher;
use App\Models\User;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('switcher persists locale on user and sets app locale', function (): void {
    Livewire::test(LanguageSwitcher::class)
        ->call('setLocale', 'es');

    $this->assertSame('es', $this->user->fresh()->locale);
    $this->assertSame('es', app()->getLocale());
});

test('switcher ignores unsupported locale', function (): void {
    Livewire::test(LanguageSwitcher::class)
        ->call('setLocale', 'zz');

    $this->assertNull($this->user->fresh()->locale);
});

test('middleware applies user locale', function (): void {
    $this->user->update(['locale' => 'fr']);

    app()->setLocale('en');

    $request = Request::create('/');
    $request->setUserResolver(fn (): User => $this->user->fresh());

    (new SetUserLocale)->handle($request, fn (): Response => new Response);

    $this->assertSame('fr', app()->getLocale());
});

test('middleware ignores missing or invalid locale', function (): void {
    app()->setLocale('en');

    $request = Request::create('/');
    $request->setUserResolver(fn (): ?User => null);

    (new SetUserLocale)->handle($request, fn (): Response => new Response);

    $this->assertSame('en', app()->getLocale());
});
