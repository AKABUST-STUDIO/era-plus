<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public function setLocale(string $locale): void
    {
        $locales = config('app.locales');

        if (! array_key_exists($locale, $locales)) {
            return;
        }

        $user = auth()->user();

        if ($user instanceof User) {
            $user->update(['locale' => $locale]);
        }

        app()->setLocale($locale);

        $this->js('window.location.reload()');
    }

    public function render(): View
    {
        $user = auth()->user();
        $locales = config('app.locales');
        $current = ($user instanceof User ? $user->locale : null) ?? app()->getLocale();

        return view('livewire.language-switcher', [
            'locales' => $locales,
            'current' => $current,
            'currentFlag' => $locales[$current]['flag'] ?? 'gb',
            'currentName' => $locales[$current]['name'] ?? $current,
        ]);
    }
}
