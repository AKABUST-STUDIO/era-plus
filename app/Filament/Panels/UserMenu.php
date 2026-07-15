<?php

namespace App\Filament\Panels;

use Closure;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;

class UserMenu
{
    /**
     * @return array<int|string, Action|Closure>
     */
    public static function items(): array
    {
        return [
            'profile' => fn (Action $action): Action => $action
                ->icon('lucide-settings')
                ->label(function (): HtmlString {
                    $user = filament()->auth()->user();

                    return new HtmlString(
                        '<span class="fi-user-menu-profile-name">'.e(filament()->getUserName($user)).'</span>'
                        .'<span class="fi-user-menu-profile-email">'.e($user->email).'</span>'
                    );
                })
                ->url('/profile'),
            // Action::make('feedback')
            //     ->label('Feedback')
            //     ->icon('lucide-smile')
            //     ->url('#')
            //     ->sort(-1),
            'theme' => fn (Action $action): Action => $action->name('theme'),
            // Action::make('home')
            //     ->label('Home Page')
            //     ->icon('lucide-house')
            //     ->url('#'),
            // Action::make('changelog')
            //     ->label('Changelog')
            //     ->icon('lucide-pencil')
            //     ->url('#'),
            // Action::make('help')
            //     ->label('Help')
            //     ->icon('lucide-globe')
            //     ->url('#'),
            // Action::make('docs')
            //     ->label('Docs')
            //     ->icon('lucide-book-open')
            //     ->url('#'),
            'logout' => fn (Action $action): Action => $action->icon('lucide-log-out')
                ->color('danger'),
        ];
    }
}
