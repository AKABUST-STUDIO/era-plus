<?php

namespace App\Filament\Project\Resources\ProjectParticipants\Schemas;

use App\Filament\Forms\Components\ParticipantCombobox;
use App\Filament\Project\Resources\ProjectParticipants\Components\CountrySelect;
use App\Filament\Project\Resources\ProjectParticipants\Components\SendingOrganizationSelect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;

class ProjectParticipantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::sections());
    }

    /**
     * @return array<int, Component>
     */
    public static function sections(): array
    {
        return [
            Hidden::make('participable_avatar_url')->dehydrated(false),
            Hidden::make('_participant_link'),

            View::make('filament.forms.components.participant-link-watcher'),

            self::personSection(),
            self::sendingOrgSection(),
        ];
    }

    protected static function personSection(): Component
    {
        return Section::make(__('participant.sections.participant'))
            ->label(__('participant.sections.participant_description'))
            ->icon('lucide-circle-user-round')
            ->iconSize(IconSize::Medium)
            ->contained(false)
            ->components([
                Grid::make(['default' => 4])
                    ->components([
                        Group::make([
                            View::make('components.participant-avatar')
                                ->viewData(fn (Get $get): array => [
                                    'url' => (string) $get('participable_avatar_url'),
                                    'tooltip' => __('participant.fields.avatar_readonly_tooltip'),
                                ]),
                        ])->columnSpan(['default' => 1])
                            ->extraAttributes(['class' => '!flex h-full items-center justify-center']),

                        Group::make([
                            ParticipantCombobox::make('participable.name')
                                ->autofocus()
                                ->label(__('participant.fields.name'))
                                ->placeholder(__('participant.fields.name_placeholder'))
                                ->searchColumn('name')
                                ->valueAttribute('name')
                                ->confirmWhenAnyFilled([
                                    'participable.email',
                                    'participable.phone',
                                    'participable.date_of_birth',
                                    'participable_avatar_url',
                                ])
                                ->required(),

                            ParticipantCombobox::make('participable.email')
                                ->label(__('participant.fields.email'))
                                ->placeholder(__('participant.fields.email_placeholder'))
                                ->searchColumn('email')
                                ->valueAttribute('email')
                                ->confirmWhenAnyFilled([
                                    'participable.name',
                                    'participable.phone',
                                    'participable.date_of_birth',
                                    'participable_avatar_url',
                                ])
                                ->required(),
                        ])->columnSpan(['default' => 3]),
                    ]),

                Grid::make(['default' => 2])
                    ->components([
                        TextInput::make('participable.phone')
                            ->label(__('participant.fields.phone'))
                            ->placeholder(__('participant.fields.phone_placeholder'))
                            ->tel()
                            ->maxLength(40)
                            ->live(onBlur: true),
                        DatePicker::make('participable.date_of_birth')
                            ->label(__('participant.fields.date_of_birth'))
                            ->placeholder(__('participant.fields.date_of_birth_placeholder'))
                            ->native(false)
                            ->live(),
                    ]),
            ]);
    }

    protected static function sendingOrgSection(): Component
    {
        return Section::make(__('participant.sections.sending'))
            ->label(__('participant.sections.sending_description'))
            ->icon('lucide-clipboard-list')
            ->contained(false)
            ->iconSize(IconSize::Medium)
            ->components([
                CountrySelect::make()
                    ->label(__('participant.fields.country'))
                    ->required()
                    ->columnSpanFull(),

                SendingOrganizationSelect::make()
                    ->hiddenLabel(false)
                    ->label(__('participant.fields.sending_organization'))
                    ->required(),
            ]);
    }
}
