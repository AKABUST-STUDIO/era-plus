<?php

namespace App\Filament\Project\Resources\ProjectParticipants\Actions;

use App\Filament\Project\Resources\ProjectParticipants\Components\ParticipableSelect;
use App\Filament\Project\Resources\ProjectParticipants\Components\SendingOrganizationSelect;
use App\Filament\Project\Resources\ProjectParticipants\Schemas\ProjectParticipantForm;
use App\Models\Project;
use App\Models\Project\Participant;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;

class AddParticipantAction
{
    public static function make(string $name = 'add'): Action
    {
        return Action::make($name)
            ->label(__('participant.actions.add'))
            ->icon('lucide-user-plus')
            ->iconPosition(IconPosition::After)
            ->modalCloseButton(false)
            ->modalHeading(false)
            ->modalSubmitActionLabel(__('participant.actions.add'))
            ->modalWidth(Width::ExtraLarge)
            ->modalCancelAction(false)
            ->modalFooterActionsAlignment(Alignment::End)
            ->schema(ProjectParticipantForm::sections())
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('createAnother', ['another' => true])
                    ->label(__('participant.actions.add_another'))
                    ->color('gray'),
            ])
            ->action(function (array $data, array $arguments, Action $action, Schema $schema): void {
                $project = Filament::getTenant();

                if (! $project instanceof Project) {
                    Notification::make()
                        ->title(__('participant.add.no_project_title'))
                        ->body(__('participant.add.no_project_body'))
                        ->danger()
                        ->send();

                    return;
                }

                $created = DB::transaction(function () use ($data, $project): bool {
                    $participableRef = $data['participable_id'] ?? null;

                    if (is_string($participableRef) && str_starts_with($participableRef, 'pending:')) {
                        $participable = Participant::create(array_merge(
                            $data['_pending_participable'] ?? [],
                            array_filter($data['participable'] ?? [], fn ($value): bool => filled($value)),
                        ));
                    } else {
                        $participable = ParticipableSelect::resolve($participableRef);
                    }

                    $sendingOrganization = SendingOrganizationSelect::resolve($data['sending_organization_id'] ?? null);

                    if ($participable === null || $sendingOrganization === null) {
                        return false;
                    }

                    $project->addParticipant(
                        $participable,
                        (int) $data['country_id'],
                        $sendingOrganization,
                    );

                    return true;
                });

                if (! $created) {
                    Notification::make()
                        ->title(__('participant.add.failed_title'))
                        ->body(__('participant.add.failed_body'))
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('participant.add.added_title'))
                    ->success()
                    ->send();

                if ($arguments['another'] ?? false) {
                    $schema->fill();
                    $schema->dispatchClientSideStateReset();
                    $action->halt();
                }
            });
    }
}
