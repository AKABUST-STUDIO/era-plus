<?php

namespace App\Filament\Organization\Resources\OrganizationUsers\Actions;

use App\Enums\Organization\OrganizationRole;
use App\Facades\AuthenticationService;
use App\Facades\OrganizationService;
use App\Filament\Organization\Resources\OrganizationUsers\Components\RoleSelect;
use App\Models\ActivityLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class InviteUserAction
{
    public static function make(): Action
    {
        return Action::make('invite')
            ->label(__('settings.users.invite.action'))
            ->icon('lucide-user-plus')
            ->modalIcon('lucide-user-plus')
            ->modalHeading(__('settings.users.invite.heading'))
            ->modalDescription(__('settings.users.invite.description'))
            ->modalWidth(Width::ExtraSmall)
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->modalFooterActionsAlignment(Alignment::End)
            ->modalSubmitActionLabel(__('settings.users.invite.action'))
            ->authorize(fn (): bool => auth()->user()->can('inviteMember', OrganizationService::current()))
            ->schema([
                Grid::make([])
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        RoleSelect::make()
                            ->columnSpan(3)
                            ->prefix(__('settings.users.invite.role')),
                        TextInput::make('email')
                            ->hiddenLabel()
                            ->columnSpan(4)
                            ->placeholder(__('settings.users.invite.email'))
                            ->prefixIcon('lucide-mail')
                            ->email()
                            ->required(),
                    ]),
            ])
            ->action(function (array $data): void {
                $organization = OrganizationService::current();

                $user = User::query()->where('email', $data['email'])->first()
                    ?? AuthenticationService::createUser($data['email']);

                if ($organization->users()->whereKey($user->getKey())->exists()) {
                    Notification::make()->title(__('notifications.already_member'))->warning()->send();

                    return;
                }

                $role = $organization->roles()->find((int) $data['role']);

                if ($role === null) {
                    Notification::make()->title(__('notifications.invalid_role'))->danger()->send();

                    return;
                }

                $organization->users()->attach($user, ['role_id' => $role->id]);

                $user->sendOrganizationInvitationMailable($organization, $role, auth()->user());

                $roleLabel = OrganizationRole::tryFrom($role->name)?->getLabel() ?? $role->name;

                ActivityLog::record(
                    $organization,
                    "Invited {$user->email} as {$roleLabel}",
                    eventType: 'organization.member.invited',
                    target: $user,
                    data: ['email' => $user->email, 'role' => $role->name],
                );

                Notification::make()->title(__('notifications.invitation_sent'))->success()->send();
            });
    }
}
