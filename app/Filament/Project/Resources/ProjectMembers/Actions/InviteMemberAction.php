<?php

namespace App\Filament\Project\Resources\ProjectMembers\Actions;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Facades\AuthenticationService;
use App\Facades\ProjectService;
use App\Filament\Project\Resources\ProjectMembers\Components\RoleSelect;
use App\Models\ActivityLog;
use App\Models\Organization\OrganizationUser;
use App\Models\ProjectUser;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class InviteMemberAction
{
    public static function make(): CreateAction
    {
        return CreateAction::make()
            ->label(__('member.invite.action'))
            ->icon('lucide-user-plus')
            ->modalIcon('lucide-user-plus')
            ->modalHeading(__('member.invite.heading'))
            ->modalDescription(__('member.invite.description'))
            ->modalWidth(Width::ExtraSmall)
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->modalFooterActionsAlignment(Alignment::End)
            ->modalSubmitActionLabel(__('member.invite.action'))
            ->createAnother(false)
            ->successNotificationTitle(__('notifications.invitation_sent'))
            ->schema([
                Grid::make([])
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        RoleSelect::make()
                            ->columnSpan(3)
                            ->prefix(__('member.invite.role')),
                        TextInput::make('email')
                            ->hiddenLabel()
                            ->columnSpan(4)
                            ->placeholder(__('member.invite.email'))
                            ->prefixIcon('lucide-mail')
                            ->email()
                            ->required(),
                    ]),
            ])
            ->using(function (array $data, CreateAction $action): ProjectUser {
                $project = ProjectService::current();
                $organization = $project->organization;

                $user = User::query()->where('email', $data['email'])->first()
                    ?? AuthenticationService::createUser($data['email']);

                if ($project->users()->whereKey($user->getKey())->exists()) {
                    Notification::make()->title(__('notifications.already_member'))->warning()->send();

                    $action->halt();
                }

                $role = $project->roles()->findOrFail((int) $data['role']);

                if (! $organization->users()->whereKey($user->getKey())->exists()) {
                    $organizationRole = $organization->defaultMemberRole();

                    OrganizationUser::create([
                        'organization_id' => $organization->getKey(),
                        'user_id' => $user->getKey(),
                        'role_id' => $organizationRole?->id,
                    ]);

                    if ($organizationRole instanceof Role) {
                        $organizationRoleLabel = OrganizationRole::tryFrom($organizationRole->name)?->getLabel() ?? $organizationRole->name;

                        $user->sendOrganizationInvitationMailable($organization, $organizationRole, auth()->user());

                        ActivityLog::record(
                            $organization,
                            "Invited {$user->email} as {$organizationRoleLabel}",
                            eventType: 'organization.member.invited',
                            target: $user,
                            data: ['email' => $user->email, 'role' => $organizationRole->name],
                        );
                    }
                }

                $membership = ProjectUser::create([
                    'project_id' => $project->getKey(),
                    'user_id' => $user->getKey(),
                    'role_id' => $role->id,
                ]);

                $roleLabel = ProjectRole::tryFrom($role->name)?->getLabel() ?? $role->name;

                ActivityLog::record(
                    $organization,
                    "Invited {$user->email} as {$roleLabel}",
                    project: $project,
                    eventType: 'project.member.invited',
                    target: $user,
                    data: ['email' => $user->email, 'role' => $role->name],
                );

                return $membership;
            });
    }
}
