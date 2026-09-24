<?php

namespace App\Filament\Project\Resources\ProjectParticipants\Actions;

use App\Filament\Project\Resources\ProjectParticipants\Schemas\ProjectParticipantForm;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\Project\ParticipantOrganization;
use App\Models\Project\ProjectParticipant;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AddParticipantAction
{
    public static function make(string $name = 'add'): CreateAction
    {
        return CreateAction::make($name)
            ->model(ProjectParticipant::class)
            ->label(__('participant.actions.add'))
            ->icon('lucide-user-plus')
            ->iconPosition(IconPosition::After)
            ->modalCloseButton(false)
            ->modalHeading(__('participant.modal.heading'))
            ->modalDescription(__('participant.modal.description'))
            ->modalSubmitActionLabel(__('participant.actions.add'))
            ->modalWidth(Width::ExtraLarge)
            ->modalCancelAction(false)
            ->modalFooterActionsAlignment(Alignment::End)
            ->schema(ProjectParticipantForm::sections())
            ->createAnotherAction(fn (Action $action): Action => $action
                ->label(__('participant.actions.add_another'))
                ->color('gray'))
            ->beforeFormValidated(function ($livewire): void {
                \Livewire\store($livewire)->set('forceRender', true);
            })
            ->before(function (CreateAction $action, array $data, $livewire): void {
                $project = Filament::getTenant();

                if (! $project instanceof Project) {
                    return;
                }

                $linked = self::resolveLinkedParticipable($data);

                if ($linked === null) {
                    return;
                }

                $alreadyAttached = ProjectParticipant::query()
                    ->where('project_id', $project->id)
                    ->where('participable_type', $linked->getMorphClass())
                    ->where('participable_id', $linked->getKey())
                    ->exists();

                if (! $alreadyAttached) {
                    return;
                }

                $livewire->dispatch('participant-already-attached', name: $linked->name);

                $action->halt();
            })
            ->using(function (array $data, CreateAction $action): ?ProjectParticipant {
                $project = Filament::getTenant();

                if (! $project instanceof Project) {
                    Notification::make()
                        ->title(__('participant.add.no_project_title'))
                        ->body(__('participant.add.no_project_body'))
                        ->danger()
                        ->send();

                    $action->cancel();

                    return null;
                }

                $participable = self::resolveOrCreateParticipable($data);

                if ($participable === null) {
                    $action->cancel();

                    return null;
                }

                $sendingOrganization = self::resolveOrCreateSendingOrganization($data);

                if ($sendingOrganization === null) {
                    Notification::make()
                        ->title(__('participant.add.failed_title'))
                        ->body(__('participant.add.failed_body'))
                        ->danger()
                        ->send();

                    $action->cancel();

                    return null;
                }

                $participation = DB::transaction(fn (): ProjectParticipant => $project->addParticipant(
                    $participable,
                    (int) $data['country_id'],
                    $sendingOrganization,
                ));

                if ($participable instanceof Participant && ! empty($data['_avatar_upload'])) {
                    self::attachAvatar($participable, $data['_avatar_upload']);
                }

                return $participation;
            })
            ->successNotificationTitle(__('participant.add.added_title'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function resolveOrCreateParticipable(array $data): Participant|User|null
    {
        $linked = self::resolveLinkedParticipable($data);

        if ($linked !== null) {
            return $linked;
        }

        $participableData = array_filter($data['participable'] ?? [], fn ($value): bool => filled($value));

        return Participant::create($participableData);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function resolveLinkedParticipable(array $data): Participant|User|null
    {
        $raw = $data['_participant_link'] ?? null;

        if (! is_string($raw) || blank($raw)) {
            return null;
        }

        $decoded = json_decode($raw, true);
        $ref = is_array($decoded) ? ($decoded['ref'] ?? null) : null;

        if (! is_string($ref) || ! str_contains($ref, ':')) {
            return null;
        }

        [$type, $id] = explode(':', $ref, 2);

        return match ($type) {
            'p' => Participant::find((int) $id),
            'u' => User::query()->whereNotNull('email_verified_at')->find((int) $id),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function resolveOrCreateSendingOrganization(array $data): ?Model
    {
        $ref = $data['sending_organization_id'] ?? null;

        if (! is_string($ref) || blank($ref)) {
            return null;
        }

        if (str_starts_with($ref, '__quick_add__')) {
            $name = trim(substr($ref, strlen('__quick_add__')));

            return blank($name) ? null : ParticipantOrganization::create(['name' => $name]);
        }

        if (! str_contains($ref, ':')) {
            return null;
        }

        [$type, $id] = explode(':', $ref, 2);

        return match ($type) {
            'o' => Organization::find((int) $id),
            'po' => ParticipantOrganization::find((int) $id),
            default => null,
        };
    }

    /**
     * @param  string|array<int|string, mixed>  $upload
     */
    protected static function attachAvatar(Participant $participant, string|array $upload): void
    {
        $path = is_array($upload) ? (string) reset($upload) : $upload;

        if (blank($path)) {
            return;
        }

        $absolute = storage_path('app/public/'.ltrim($path, '/'));

        if (! is_file($absolute)) {
            return;
        }

        $participant
            ->addMedia($absolute)
            ->preservingOriginal()
            ->toMediaCollection('avatar');
    }
}
