<?php

namespace App\Filament\Organization\Settings\Resources\OrganizationUsers\Actions;

use App\Enums\Organization\OrganizationRole;
use App\Facades\AuthenticationService;
use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Resources\OrganizationUsers\Components\RoleSelect;
use App\Models\ActivityLog;
use App\Models\Organization\OrganizationUser;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class InviteUserAction
{
    public static function make(): CreateAction
    {
        return CreateAction::make()
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
            ->createAnother(false)
            ->successNotificationTitle(__('notifications.invitation_sent'))
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
            ->using(function (array $data, CreateAction $action): OrganizationUser {
                $organization = OrganizationService::current();

                $user = User::query()->where('email', $data['email'])->first()
                    ?? AuthenticationService::createUser($data['email']);

                if ($organization->users()->whereKey($user->getKey())->exists()) {
                    Notification::make()->title(__('notifications.already_member'))->warning()->send();

                    $action->halt();
                }

                $role = $organization->roles()->findOrFail((int) $data['role']);

                $membership = OrganizationUser::create([
                    'organization_id' => $organization->getKey(),
                    'user_id' => $user->getKey(),
                    'role_id' => $role->id,
                ]);

                $user->sendOrganizationInvitationMailable($organization, $role, auth()->user());

                $roleLabel = OrganizationRole::tryFrom($role->name)?->getLabel() ?? $role->name;

                ActivityLog::record(
                    $organization,
                    "Invited {$user->email} as {$roleLabel}",
                    eventType: 'organization.member.invited',
                    target: $user,
                    data: ['email' => $user->email, 'role' => $role->name],
                );

                return $membership;
            });
    }
}
