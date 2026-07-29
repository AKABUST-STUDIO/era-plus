<?php

namespace Tests\Feature;

use App\Http\Middleware\SetUserLocale;
use App\Livewire\LanguageSwitcher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LanguageSwitcherTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_switcher_persists_locale_on_user_and_sets_app_locale(): void
    {
        Livewire::test(LanguageSwitcher::class)
            ->call('setLocale', 'es');

        $this->assertSame('es', $this->user->fresh()->locale);
        $this->assertSame('es', app()->getLocale());
    }

    public function test_switcher_ignores_unsupported_locale(): void
    {
        Livewire::test(LanguageSwitcher::class)
            ->call('setLocale', 'zz');

        $this->assertNull($this->user->fresh()->locale);
    }

    public function test_middleware_applies_user_locale(): void
    {
        $this->user->update(['locale' => 'fr']);

        app()->setLocale('en');

        $request = Request::create('/');
        $request->setUserResolver(fn (): User => $this->user->fresh());

        (new SetUserLocale)->handle($request, fn (): Response => new Response);

        $this->assertSame('fr', app()->getLocale());
    }

    public function test_middleware_ignores_missing_or_invalid_locale(): void
    {
        app()->setLocale('en');

        $request = Request::create('/');
        $request->setUserResolver(fn (): ?User => null);

        (new SetUserLocale)->handle($request, fn (): Response => new Response);

        $this->assertSame('en', app()->getLocale());
    }
}
