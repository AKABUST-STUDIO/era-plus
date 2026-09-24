<?php

namespace App\Filament\Panels;

use App\Filament\Panels\Actions\FeedbackAction;
use App\Filament\User\Pages\Settings;
use App\Filament\User\Resources\SupportTickets\Actions\CreateSupportTicketAction;
use App\Filament\User\Resources\SupportTickets\SupportTicketResource;
use App\Providers\Filament\UserPanelProvider;
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
                        '<span class="block font-semibold">'.e(filament()->getUserName($user)).'</span>'
                        .'<span class="block text-xs leading-4 text-gray-500">'.e($user->email).'</span>'
                    );
                })
                ->url(fn (): string => Settings::getUrl(panel: UserPanelProvider::PANEL_ID)),
            'theme' => fn (Action $action): Action => $action->name('theme'),
            'language' => fn (Action $action): Action => $action->name('language'),
            // FeedbackAction::make(),
            CreateSupportTicketAction::make('support')
                ->label(__('user.support.action'))
                ->successRedirectUrl(fn (): string => SupportTicketResource::getUrl(name: 'index', panel: 'user')),
            'logout' => fn (Action $action): Action => $action->icon('lucide-log-out')
                ->color('danger'),
        ];
    }
}
