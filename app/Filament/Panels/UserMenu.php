<?php

namespace App\Filament\Panels;

use App\Filament\User\Pages\Settings;
use App\Providers\Filament\UserPanelProvider;
use App\Filament\Panels\Actions\FeedbackAction;
use App\Filament\User\Resources\SupportRequests\Actions\CreateSupportRequestAction;
use App\Filament\User\Resources\SupportRequests\SupportRequestResource;
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
                ->url(fn (): string => Settings::getUrl(panel: UserPanelProvider::PANEL_ID)),
            'theme' => fn (Action $action): Action => $action->name('theme'),
            'language' => fn (Action $action): Action => $action->name('language'),
            FeedbackAction::make(),
            CreateSupportRequestAction::make('support')
                ->label(__('user.support.action'))
                ->successRedirectUrl(fn (): string => SupportRequestResource::getUrl(name: 'index', panel: 'user')),
            'logout' => fn (Action $action): Action => $action->icon('lucide-log-out')
                ->color('danger'),
        ];
    }
}
